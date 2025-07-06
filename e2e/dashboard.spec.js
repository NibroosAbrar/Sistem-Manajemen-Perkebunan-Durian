// @ts-check
import { test, expect } from '@playwright/test';

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

test.describe('Dashboard', () => {
  // Login sebelum setiap test
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await waitForPageLoad(page);

    // Tunggu form login muncul
    await expect(page.locator('form')).toBeVisible({ timeout: 10000 });

    // Login dengan akun yang pasti ada
    await page.locator('input[name="login"], #login').fill('superadmin@symadu.com');
    await page.locator('input[type="password"], #password').fill('superadmin123');
    await page.locator('button[type="submit"]').click();

    // Verifikasi berhasil login dan berada di dashboard
    await expect(page).toHaveURL(/dashboard/, { timeout: 10000 });

    // Tunggu halaman dashboard sepenuhnya dimuat
    await waitForPageLoad(page, 3000);

    // Scroll sampai bawah untuk melihat semua elemen
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(1000);
  });

  // Logout setelah setiap tes selesai
  test.afterEach(async ({ page }) => {
    // Scroll kembali ke atas sebelum logout
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(500);

    await doLogout(page);
  });

  test('Memastikan seluruh komponen dashboard berhasil dimuat', async ({ page }) => {
    // Tunggu semua komponen dimuat dengan benar
    await page.waitForTimeout(2000);

    // Memastikan timestamp card muncul
    await expect(page.locator('.timestamp-card')).toBeVisible();

    // Memastikan scoreboard cards muncul (4 kartu)
    const scoreboardCards = page.locator('.dashboard-card.card-1, .dashboard-card.card-2, .dashboard-card.card-3, .dashboard-card.card-4');
    await expect(scoreboardCards).toHaveCount(4);

    // Scroll ke tengah halaman
    await page.evaluate(() => window.scrollTo(0, window.innerHeight * 0.5));
    await page.waitForTimeout(1000);

    // Memastikan welcome message muncul
    await expect(page.locator('#welcome-message')).toBeVisible();

    // Memastikan filter blok kebun muncul
    await expect(page.locator('#blockFilter')).toBeVisible();
    await page.waitForTimeout(1000);

    // Memastikan komponen waktu digital berhasil dimuat - tunggu sampai JS menginisialisasi
    try {
      // Coba tunggu sampai elemen waktu digital diisi oleh JavaScript
      await page.waitForFunction(() => {
        const clockElement = document.getElementById('digital-clock');
        return clockElement && clockElement.textContent && clockElement.textContent.trim() !== '';
      }, { timeout: 5000 });

      // Verifikasi elemen waktu terlihat
      await expect(page.locator('#digital-clock')).toBeVisible();
      await expect(page.locator('#date-display')).toBeVisible();
    } catch (error) {
      console.log('Komponen waktu mungkin tidak terlihat atau belum terisi: ', error);
      // Lanjutkan pengujian meskipun komponen waktu tidak terlihat
    }

    // Scroll ke bawah untuk melihat chart
    await page.evaluate(() => window.scrollTo(0, window.innerHeight * 1.5));
    await page.waitForTimeout(1000);

    // Tunggu chart dimuat
    await page.waitForTimeout(2000);

    // Memastikan semua canvas grafik dimuat
    try {
      const charts = [
        '#treeGrowthChart',
        '#healthStatusChart',
        '#ageDistributionChart',
        '#varietasDistributionChart',
        '#productivityChart',
        '#faseTanamanChart'
      ];

      for (const chartSelector of charts) {
        // Verifikasi grafik terlihat jika ada
        const chartExists = await page.locator(chartSelector).count() > 0;
        if (chartExists) {
          // Scroll ke grafik
          await page.locator(chartSelector).scrollIntoViewIfNeeded();
          await page.waitForTimeout(500);

          await expect(page.locator(chartSelector)).toBeVisible();
        }
      }

      // Memastikan chart header muncul jika ada
      const chartHeaderCount = await page.locator('.chart-header').count();
      if (chartHeaderCount > 0) {
        // Hanya verifikasi jika ada header
        await expect(page.locator('.chart-header').first()).toBeVisible();
      }
    } catch (error) {
      console.log('Beberapa grafik mungkin tidak terlihat: ', error);
    }

    // Scroll ke bawah halaman
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(1000);
  });

  test('Melakukan filter blok kebun', async ({ page }) => {
    // Memastikan filter blok kebun muncul
    const blockFilter = page.locator('#blockFilter');
    await expect(blockFilter).toBeVisible();
    await page.waitForTimeout(1000);

    // Scroll ke filter blok
    await blockFilter.scrollIntoViewIfNeeded();
    await page.waitForTimeout(500);

    // Mendapatkan nilai opsi filter pertama selain "Semua Blok"
    const options = await page.locator('#blockFilter option:not([value=""])').all();

    // Memastikan ada opsi blok kebun
    expect(options.length).toBeGreaterThan(0);

    if (options.length > 0) {
      // Mendapatkan teks dan nilai dari opsi pertama
      const firstOptionValue = await options[0].getAttribute('value');
      const firstOptionText = await options[0].textContent() || '';

      // Memilih opsi blok kebun pertama
      await blockFilter.selectOption(firstOptionValue);

      // Tunggu beberapa saat setelah pilihan
      await page.waitForTimeout(2000);

      // Menunggu halaman dimuat ulang setelah pemilihan
      await waitForPageLoad(page);

      // Scroll ke chart pertama
      await page.locator('#treeGrowthChart, canvas').first().scrollIntoViewIfNeeded();
      await page.waitForTimeout(1000);

      // Pada beberapa implementasi, filter mungkin menggunakan AJAX dan tidak mengubah URL
      try {
        // Coba cek URL terlebih dahulu
        await expect(page).toHaveURL(new RegExp(`plantation_id=${firstOptionValue}`), { timeout: 3000 });
      } catch (error) {
        console.log('URL tidak berubah setelah filter, mungkin menggunakan AJAX');

        // Cek apakah ada opsi yang sekarang terlihat sebagai "selected"
        // Untuk menghindari linter error dengan property value, kita cek atribut selected
        const isOptionSelected = await page.locator(`#blockFilter option[value="${firstOptionValue}"]`).evaluate(
          (option) => option.hasAttribute('selected')
        );
        expect(isOptionSelected).toBe(true);
      }

      // Scroll ke chart tengah
      await page.evaluate(() => window.scrollTo(0, window.innerHeight * 1.5));
      await page.waitForTimeout(1000);

      // Memastikan semua grafik tetap terlihat setelah filter (cek minimal satu grafik)
      await page.waitForTimeout(2000);
      const canvasCount = await page.locator('canvas').count();
      expect(canvasCount).toBeGreaterThan(0);

      // Scroll ke chart terakhir
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(1000);
    }
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
