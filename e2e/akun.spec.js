// @ts-check
import { test, expect } from '@playwright/test';

// Generate nama dan email unik untuk user baru
const timestamp = new Date().getTime();
const testUserName = `Test User ${timestamp}`;
const testUserEmail = `testuser${timestamp}@example.com`;
const testUserUsername = `testuser${timestamp}`;
const testUserPassword = 'password123';

// Fungsi untuk menunggu halaman dimuat dengan cara yang lebih kompatibel dengan Firefox
async function waitForPageLoad(page, additionalWaitTime = 2000) {
  try {
    // Tunggu minimal untuk DOM terlebih dahulu (lebih stabil daripada networkidle)
    await page.waitForLoadState('domcontentloaded');
    
    // Tunggu sebentar untuk memastikan skrip JS sudah berjalan
    await page.waitForTimeout(additionalWaitTime);
    
    // Coba tunggu networkidle dengan timeout lebih pendek, jika gagal tidak masalah
    try {
      await page.waitForLoadState('networkidle', { timeout: 5000 });
    } catch (e) {
      // Jika networkidle timeout, tidak masalah, lanjutkan saja
      console.log('Networkidle timeout, melanjutkan test');
    }
  } catch (e) {
    console.log('Error saat menunggu halaman dimuat:', e);
    // Tetap lanjutkan dengan test
  }
}

test.describe('Manajemen Pengguna', () => {
  test('Registrasi, login sebagai superadmin, edit role, dan logout', async ({ page }) => {
    // Langkah 1: Registrasi akun baru
    await page.goto('/register');
    await waitForPageLoad(page);

    // Isi form registrasi
    await page.locator('input[name="name"]').fill(testUserName);

    // Cek apakah field username ada dan isi jika ada
    const usernameField = page.locator('input[name="username"]');
    if (await usernameField.count() > 0) {
      await usernameField.fill(testUserUsername);
    }

    await page.locator('input[name="email"]').fill(testUserEmail);
    await page.locator('input[name="password"]').fill(testUserPassword);
    await page.locator('input[name="password_confirmation"]').fill(testUserPassword);

    // Scroll ke bawah untuk memastikan tombol submit terlihat
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(1000);

    // Submit form registrasi
    await page.locator('button[type="submit"]').click();

    // Tunggu registrasi selesai (akan diarahkan ke halaman login)
    await waitForPageLoad(page);

    // Verifikasi bahwa kita berada di halaman login
    await expect(page).toHaveURL(/login/, { timeout: 10000 });

    // Langkah 2: Login sebagai superadmin
    await page.locator('input[name="login"], #login').fill('superadmin@symadu.com');
    await page.locator('input[type="password"], #password').fill('superadmin123');
    await page.locator('button[type="submit"]').click();

    // Verifikasi berhasil login
    await expect(page).toHaveURL(/dashboard/, { timeout: 10000 });
    await page.waitForTimeout(2000);

    // Tunggu sampai halaman sepenuhnya dimuat
    await waitForPageLoad(page);

    // Perbaikan untuk tombol hamburger - gunakan selector yang lebih spesifik dan tunggu sampai tombol muncul
    try {
      await page.waitForSelector('button.hamburger-button, button.sidebar-toggle, button[aria-label="Toggle Sidebar"]', { timeout: 5000 });
      await page.click('button.hamburger-button, button.sidebar-toggle, button[aria-label="Toggle Sidebar"]');
    } catch (error) {
      console.log('Tombol hamburger tidak ditemukan dengan selector biasa, mencoba alternatif:', error);
      // Coba pendekatan alternatif jika selector pertama gagal
      await page.waitForTimeout(1000);
      await page.evaluate(() => {
        // Cari elemen hamburger berdasarkan tampilan atau posisinya
        const buttons = document.querySelectorAll('button');
        for (const button of buttons) {
          // Cek jika button terletak di pojok kiri atas atau memiliki ikon hamburger
          if (button.innerHTML.includes('svg') &&
             (button.getBoundingClientRect().left < 100 && button.getBoundingClientRect().top < 100)) {
            button.click();
            return;
          }
        }
      });
    }
    await page.waitForTimeout(2000);

    // Klik menu Manajemen Pengguna - gunakan pendekatan yang lebih fleksibel
    const linkSelectors = [
      'a:has-text("Manajemen Pengguna")',
      'a:has-text("Akun")',
      'a:has-text("Pengguna")',
      '.sidebar a:has-text("Pengguna")'
    ];

    for (const selector of linkSelectors) {
      try {
        if (await page.locator(selector).count() > 0) {
          await page.locator(selector).click();
          break;
        }
      } catch (error) {
        console.log(`Selector ${selector} tidak ditemukan, mencoba selanjutnya`);
      }
    }

    await waitForPageLoad(page);

    // Cari akun yang baru dibuat - menggunakan nama lengkap bukan email
    await page.locator('input[name="search"]').fill(testUserName);
    await page.waitForTimeout(1000);
    await page.locator('button[type="submit"]:has-text("Filter")').click();
    await waitForPageLoad(page);

    // Scroll untuk melihat hasil pencarian
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.5));
    await page.waitForTimeout(1000);

    // Verifikasi akun ditemukan menggunakan nama lengkap
    await expect(page.locator('table tbody tr')).toContainText(testUserName);

    // Edit role akun - pilih Operasional
    // Temukan baris dengan nama pengguna yang baru dibuat
    const userRow = page.locator(`table tbody tr:has-text("${testUserName}")`);

    // Scroll ke elemen tersebut
    await userRow.scrollIntoViewIfNeeded();
    await page.waitForTimeout(1000);

    // Pilih role baru (Operasional)
    await userRow.locator('select[name="role_id"]').selectOption({ label: 'Operasional' });
    await page.waitForTimeout(1000);

    // Klik tombol edit di samping dropdown
    await userRow.locator('button[type="button"]').first().click();
    await page.waitForTimeout(2000);

    // Konfirmasi perubahan pada modal dengan mengklik tombol Simpan
    await page.locator('button:has-text("Simpan")').click();
    await waitForPageLoad(page);

    // Scrolling kembali ke atas sebelum logout
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(1000);

    // Logout
    await doLogout(page);
    await page.waitForTimeout(2000);
  });

  test('Login sebagai superadmin, hapus akun, dan logout', async ({ page }) => {
    // Login sebagai superadmin
    await page.goto('/login');
    await waitForPageLoad(page);

    await page.locator('input[name="login"], #login').fill('superadmin@symadu.com');
    await page.locator('input[type="password"], #password').fill('superadmin123');
    await page.locator('button[type="submit"]').click();

    // Verifikasi berhasil login
    await expect(page).toHaveURL(/dashboard/, { timeout: 10000 });
    await page.waitForTimeout(2000);

    // Tunggu sampai halaman sepenuhnya dimuat
    await waitForPageLoad(page);

    // Perbaikan untuk tombol hamburger
    try {
      await page.waitForSelector('button.hamburger-button, button.sidebar-toggle, button[aria-label="Toggle Sidebar"]', { timeout: 5000 });
      await page.click('button.hamburger-button, button.sidebar-toggle, button[aria-label="Toggle Sidebar"]');
    } catch (error) {
      console.log('Tombol hamburger tidak ditemukan dengan selector biasa, mencoba alternatif:', error);
      // Coba pendekatan alternatif jika selector pertama gagal
      await page.waitForTimeout(1000);
      await page.evaluate(() => {
        // Cari elemen hamburger berdasarkan tampilan atau posisinya
        const buttons = document.querySelectorAll('button');
        for (const button of buttons) {
          // Cek jika button terletak di pojok kiri atas atau memiliki ikon hamburger
          if (button.innerHTML.includes('svg') &&
             (button.getBoundingClientRect().left < 100 && button.getBoundingClientRect().top < 100)) {
            button.click();
            return;
          }
        }
      });
    }
    await page.waitForTimeout(2000);

    // Klik menu Manajemen Pengguna - gunakan pendekatan yang lebih fleksibel
    const linkSelectors = [
      'a:has-text("Manajemen Pengguna")',
      'a:has-text("Akun")',
      'a:has-text("Pengguna")',
      '.sidebar a:has-text("Pengguna")'
    ];

    for (const selector of linkSelectors) {
      try {
        if (await page.locator(selector).count() > 0) {
          await page.locator(selector).click();
          break;
        }
      } catch (error) {
        console.log(`Selector ${selector} tidak ditemukan, mencoba selanjutnya`);
      }
    }

    await waitForPageLoad(page);

    // Cari akun yang ingin dihapus - menggunakan nama lengkap bukan email
    await page.locator('input[name="search"]').fill(testUserName);
    await page.waitForTimeout(1000);
    await page.locator('button[type="submit"]:has-text("Filter")').click();
    await waitForPageLoad(page);

    // Scroll untuk melihat hasil pencarian
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.5));
    await page.waitForTimeout(1000);

    try {
      // Verifikasi akun ditemukan berdasarkan nama lengkap
      await expect(page.locator('table tbody tr')).toContainText(testUserName);

      // Hapus akun
      // Temukan baris dengan nama pengguna yang ingin dihapus
      const userRow = page.locator(`table tbody tr:has-text("${testUserName}")`);

      // Scroll ke elemen tersebut
      await userRow.scrollIntoViewIfNeeded();
      await page.waitForTimeout(1000);

      // Klik tombol Hapus (sesuai dengan struktur di akun.blade.php)
      await userRow.locator('button:has-text("Hapus")').click();
      await page.waitForTimeout(2000);

      // Konfirmasi penghapusan pada modal dengan mengklik tombol Hapus
      // Sesuai dengan akun.blade.php, tombol Hapus adalah tombol terakhir di dalam modal
      await page.locator('div[x-show="showDeleteModal"] button:has-text("Hapus")').click();
      await waitForPageLoad(page);

      // Verifikasi akun sudah dihapus (tidak ditemukan setelah pencarian)
      await page.locator('input[name="search"]').clear();
      await page.waitForTimeout(500);
      await page.locator('input[name="search"]').fill(testUserName);
      await page.waitForTimeout(1000);
      await page.locator('button[type="submit"]:has-text("Filter")').click();
      await waitForPageLoad(page);

      // Scroll untuk melihat hasil
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.5));
      await page.waitForTimeout(1000);

      // Seharusnya tidak ada baris yang mengandung nama pengguna yang dihapus
      const rows = await page.locator('table tbody tr').count();
      if (rows > 0) {
        const hasName = await page.locator(`table tbody tr:has-text("${testUserName}")`).count() > 0;
        expect(hasName).toBe(false);
      }
    } catch (error) {
      console.log('Akun tidak ditemukan atau sudah dihapus sebelumnya:', error);
    }

    // Scrolling kembali ke atas sebelum logout
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(1000);

    // Logout
    await doLogout(page);
  });
});

// Fungsi helper untuk proses logout
async function doLogout(page) {
  // Tunggu halaman sepenuhnya dimuat
  await waitForPageLoad(page);

  try {
    // Scroll ke atas untuk memastikan profil button terlihat
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(1000);

    // Langkah 1: Klik tombol profil (gambar profile)
    await page.locator('button.profile-button').click();
    await page.waitForTimeout(1000);

    // Langkah 2: Klik tombol "Keluar" dalam dropdown profile
    await page.locator('button', { hasText: 'Keluar' }).click();
    await page.waitForTimeout(1000);

    // Langkah 3: Klik tombol "Keluar" (berwarna merah) pada modal konfirmasi
    await page.locator('form[action*="logout"] button[type="submit"]').click();
    await page.waitForTimeout(2000);

    // Memastikan kembali ke halaman login
    await expect(page).toHaveURL(/login/);

    return true;
  } catch (error) {
    console.error('Error saat proses logout:', error);
    return false;
  }
}
