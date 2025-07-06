// @ts-check
import { test, expect } from '@playwright/test';

// Konfigurasi timeout global untuk test ini
test.setTimeout(120000); // Set timeout lebih lama menjadi 120 detik / 2 menit

test.describe('Pengujian Kegiatan Pengelolaan', () => {
  // Data untuk kegiatan baru
  const namaKegiatan = 'Kegiatan Pengujian Otomatis ' + new Date().getTime();
  const jenisKegiatan = 'Pemupukan';
  const deskripsiKegiatan = 'Ini adalah kegiatan yang dibuat untuk pengujian otomatis';

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

  // Skenario 1: Login dan tambah kegiatan baru dengan status belum berjalan
  test('Tambah kegiatan baru dengan status belum berjalan', async ({ page }) => {
    // Login sebagai superadmin
    await page.goto('/login');
    await waitForPageLoad(page, 2000);

    await page.locator('input[name="login"]').fill('superadmin@symadu.com');
    await page.locator('input[type="password"]').fill('superadmin123');
    await page.waitForTimeout(1000);
    await page.locator('button[type="submit"]').click();

    await page.waitForTimeout(3000); // Tunggu halaman dashboard muncul

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

    // Klik menu Kegiatan Pengelolaan - gunakan pendekatan yang lebih fleksibel
    const linkSelectors = [
      'a:has-text("Kegiatan Pengelolaan")',
      'a:has-text("Kegiatan")',
      'a:has-text("Pengelolaan")',
      '.sidebar a:has-text("Kegiatan")'
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
    await page.waitForTimeout(3000);
    await waitForPageLoad(page);

    // Klik tombol Tambah Kegiatan
    await page.locator('button:has-text("Tambah Kegiatan")').click();
    await page.waitForTimeout(2000);

    // Isi form tambah kegiatan
    await page.locator('#nama_kegiatan').fill(namaKegiatan);
    await page.locator('#jenis_kegiatan').selectOption(jenisKegiatan);
    await page.locator('#deskripsi_kegiatan').fill(deskripsiKegiatan);

    // Isi tanggal mulai (tanggal hari ini)
    const today = new Date();
    const formattedDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    await page.locator('#tanggal_mulai').fill(formattedDate);

    // Pilih status "Belum Berjalan"
    await page.locator('#status_tambah').selectOption('Belum Berjalan');
    await page.waitForTimeout(2000);

    // Klik tombol Simpan - perbaikan untuk strict mode violation
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await page.waitForTimeout(5000);

    // Verifikasi kegiatan sudah ditambahkan (cek apakah nama kegiatan muncul di tabel)
    await expect(page.locator(`td:has-text("${namaKegiatan}")`).first()).toBeVisible();

    // Logout
    await doLogout(page);
    await page.waitForTimeout(3000);
  });

  // Skenario 2: Login dan ubah status kegiatan menjadi sedang berjalan
  test('Ubah status kegiatan menjadi sedang berjalan', async ({ page }) => {
    // Login sebagai superadmin
    await page.goto('/login');
    await waitForPageLoad(page, 2000);

    await page.locator('input[name="login"]').fill('superadmin@symadu.com');
    await page.locator('input[type="password"]').fill('superadmin123');
    await page.waitForTimeout(1000);
    await page.locator('button[type="submit"]').click();

    await page.waitForTimeout(3000);

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

    // Klik menu Kegiatan Pengelolaan - gunakan pendekatan yang lebih fleksibel
    const linkSelectors = [
      'a:has-text("Kegiatan Pengelolaan")',
      'a:has-text("Kegiatan")',
      'a:has-text("Pengelolaan")',
      '.sidebar a:has-text("Kegiatan")'
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
    await page.waitForTimeout(3000);
    await waitForPageLoad(page);

    // Cari kegiatan yang telah dibuat sebelumnya
    await page.locator('input[placeholder*="Cari berdasarkan nama kegiatan"]').fill(namaKegiatan);
    await page.waitForTimeout(2000);

    // Klik tombol ubah status (tombol biru dengan ikon)
    const kegiatanRow = page.locator(`tr:has-text("${namaKegiatan}")`).first();
    await kegiatanRow.locator('button.bg-blue-500[title="Ubah Status"]').click();
    await page.waitForTimeout(2000);

    // Ubah status menjadi "Sedang Berjalan"
    await page.locator('#new_status').selectOption('Sedang Berjalan');
    await page.waitForTimeout(2000);

    // Simpan perubahan status - perbaikan selector
    await page.getByRole('button', { name: 'Simpan Status', exact: true }).click();
    await page.waitForTimeout(5000);

    // Verifikasi status sudah berubah (cek apakah ada span dengan teks "Sedang Berjalan")
    await page.locator('input[placeholder*="Cari berdasarkan nama kegiatan"]').fill(namaKegiatan);
    await page.waitForTimeout(2000);
    await expect(page.locator(`tr:has-text("${namaKegiatan}") span:has-text("Sedang Berjalan")`).first()).toBeVisible();

    // Logout
    await doLogout(page);
    await page.waitForTimeout(3000);
  });

  // Skenario 3: Login dan edit kegiatan, ubah status menjadi selesai
  test('Edit kegiatan dan ubah status menjadi selesai', async ({ page }) => {
    // Login sebagai superadmin
    await page.goto('/login');
    await waitForPageLoad(page, 2000);

    await page.locator('input[name="login"]').fill('superadmin@symadu.com');
    await page.locator('input[type="password"]').fill('superadmin123');
    await page.waitForTimeout(1000);
    await page.locator('button[type="submit"]').click();

    await page.waitForTimeout(3000);

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

    // Klik menu Kegiatan Pengelolaan - gunakan pendekatan yang lebih fleksibel
    const linkSelectors = [
      'a:has-text("Kegiatan Pengelolaan")',
      'a:has-text("Kegiatan")',
      'a:has-text("Pengelolaan")',
      '.sidebar a:has-text("Kegiatan")'
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
    await page.waitForTimeout(3000);
    await waitForPageLoad(page);

    // Cari kegiatan yang telah dibuat sebelumnya
    await page.locator('input[placeholder*="Cari berdasarkan nama kegiatan"]').fill(namaKegiatan);
    await page.waitForTimeout(2000);

    // Klik tombol edit (tombol kuning dengan ikon pensil)
    const kegiatanRow = page.locator(`tr:has-text("${namaKegiatan}")`).first();
    await kegiatanRow.locator('button.bg-yellow-500[title="Edit Kegiatan"]').click();
    await page.waitForTimeout(2000);

    // Ubah status menjadi "Selesai"
    await page.locator('#edit_status').selectOption('Selesai');
    await page.waitForTimeout(2000);

    // Isi tanggal selesai (tanggal hari ini)
    const today = new Date();
    const formattedDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    await page.locator('#edit_tanggal_selesai').fill(formattedDate);
    await page.waitForTimeout(2000);

    // Simpan perubahan - perbaikan selector
    await page.getByRole('button', { name: 'Simpan Perubahan', exact: true }).click();
    await page.waitForTimeout(5000);

    // Verifikasi status sudah berubah menjadi Selesai
    await page.locator('input[placeholder*="Cari berdasarkan nama kegiatan"]').fill(namaKegiatan);
    await page.waitForTimeout(2000);
    await expect(page.locator(`tr:has-text("${namaKegiatan}") span:has-text("Selesai")`).first()).toBeVisible();

    // Logout
    await doLogout(page);
    await page.waitForTimeout(3000);
  });

  // Skenario 4: Login dan hapus kegiatan
  test('Hapus kegiatan', async ({ page }) => {
    // Login sebagai superadmin
    await page.goto('/login');
    await waitForPageLoad(page, 2000);

    await page.locator('input[name="login"]').fill('superadmin@symadu.com');
    await page.locator('input[type="password"]').fill('superadmin123');
    await page.waitForTimeout(1000);
    await page.locator('button[type="submit"]').click();

    await page.waitForTimeout(3000);

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

    // Klik menu Kegiatan Pengelolaan - gunakan pendekatan yang lebih fleksibel
    const linkSelectors = [
      'a:has-text("Kegiatan Pengelolaan")',
      'a:has-text("Kegiatan")',
      'a:has-text("Pengelolaan")',
      '.sidebar a:has-text("Kegiatan")'
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
    await page.waitForTimeout(3000);
    await waitForPageLoad(page);

    // Cari kegiatan yang telah dibuat sebelumnya
    await page.locator('input[placeholder*="Cari berdasarkan nama kegiatan"]').fill(namaKegiatan);
    await page.waitForTimeout(2000);

    // Klik tombol hapus (tombol merah dengan ikon trash)
    const kegiatanRow = page.locator(`tr:has-text("${namaKegiatan}")`).first();
    await kegiatanRow.locator('button.bg-red-500[title="Hapus Kegiatan"]').click();
    await page.waitForTimeout(2000);

    // Konfirmasi hapus - perbaikan selector
    await page.getByRole('button', { name: 'Hapus', exact: true }).click();
    await page.waitForTimeout(5000);

    // Verifikasi kegiatan sudah dihapus (cari lagi dan pastikan tidak ditemukan)
    await page.locator('input[placeholder*="Cari berdasarkan nama kegiatan"]').fill(namaKegiatan);
    await page.waitForTimeout(3000);
    await expect(page.locator(`td:has-text("${namaKegiatan}")`)).toHaveCount(0);

    // Logout
    await doLogout(page);
    await page.waitForTimeout(3000);
  });
});

// Fungsi helper untuk proses logout
async function doLogout(page) {
  // Tunggu halaman sepenuhnya dimuat
  await page.waitForTimeout(2000);

  // Coba logout dengan mengklik tombol profil
  try {
    // Scroll ke atas untuk memastikan profil button terlihat
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(1000);

    // Langkah 1: Klik tombol profil
    await page.locator('button.profile-button').click();
    await page.waitForTimeout(2000);

    // Langkah 2: Klik tombol "Keluar" dalam dropdown profile
    await page.getByRole('button', { name: 'Keluar' }).click();
    await page.waitForTimeout(2000);

    // Langkah 3: Klik tombol konfirmasi "Keluar"
    await page.locator('form[action*="logout"] button[type="submit"]').click();
    await page.waitForTimeout(2000);

    return true;
  } catch (error) {
    console.error('Error saat proses logout: ', error);

    // Metode alternatif jika cara di atas gagal
    try {
      await page.goto('/logout');
      await page.waitForTimeout(2000);
      return true;
    } catch (err) {
      console.error('Gagal logout dengan metode alternatif:', err);
    return false;
    }
  }
}
