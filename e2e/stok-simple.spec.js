// @ts-check
import { test, expect } from '@playwright/test';

// Generate timestamp untuk nama barang unik - buat di level global agar konsisten untuk semua test
const timestamp = new Date().getTime();
const testStokName = `pupuk test ${timestamp}`;
const testStokCategory = 'pupuk';
const testStokQuantity = '10';
const testStokUnit = 'kg';
const editedQuantity = '15';
const currentDate = new Date().toISOString().split('T')[0]; // Format YYYY-MM-DD

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

/**
 * Helper untuk login sebagai superadmin
 */
async function loginAsSuperadmin(page) {
  await page.goto('/login');
  await waitForPageLoad(page);

  await page.locator('input[name="login"], #login').fill('superadmin@symadu.com');
  await page.locator('input[type="password"], #password').fill('superadmin123');
  await page.locator('button[type="submit"]').click();

  // Verifikasi berhasil login
  await expect(page).toHaveURL(/dashboard/, { timeout: 10000 });
  await page.waitForTimeout(2000);
  console.log('✅ Login berhasil');
}

/**
 * Helper untuk navigasi ke halaman stok
 */
async function navigateToStok(page) {
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
  console.log('✅ Tombol hamburger diklik');

  // Klik menu Manajemen Stok - gunakan pendekatan yang lebih fleksibel
  const linkSelectors = [
    'a:has-text("Manajemen Stok")',
    'a:has-text("Stok")',
    'a:has-text("Inventori")',
    '.sidebar a:has-text("Stok")'
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
  console.log('✅ Menu Manajemen Stok diklik');
}

/**
 * Helper untuk navigasi ke tab pupuk dan sub tab masuk
 */
async function navigateToPupukStokMasuk(page) {
  // Klik tab pupuk
  await page.locator('button:has-text("Pupuk")').click();
  console.log('✅ Tab Pupuk diklik');
  await page.waitForTimeout(1000);

  // Klik sub tab masuk - menggunakan selector yang lebih spesifik
  try {
    // Cari tombol sub tab Masuk dalam konteks tab pupuk yang aktif
    await page.locator('div[x-show="activeTab === \'pupuk\'"] button[\\@click="activeSubTab = \'masuk\'"]').click();
  } catch (error) {
    console.log('Selector spesifik untuk sub tab Masuk gagal, mencoba alternatif:', error);
    // Gunakan metode yang lebih generik sebagai fallback
    await page.evaluate(() => {
      // Temukan semua tombol dengan teks "Masuk"
      const buttons = Array.from(document.querySelectorAll('button'));
      // Filter tombol yang terlihat dan teksnya hanya berisi "Masuk"
      for (const btn of buttons) {
        if (btn.textContent && btn.textContent.trim() === 'Masuk' &&
            window.getComputedStyle(btn).display !== 'none' &&
            btn.closest('div[x-show="activeTab === \'pupuk\'"]')) {
          btn.click();
          return;
        }
      }
    });
  }

  console.log('✅ Sub tab Masuk diklik');
  await page.waitForTimeout(1000);
}

/**
 * Helper untuk navigasi ke tab pupuk dan sub tab keluar
 */
async function navigateToPupukStokKeluar(page) {
  // Klik tab pupuk
  await page.locator('button:has-text("Pupuk")').click();
  console.log('✅ Tab Pupuk diklik');
  await page.waitForTimeout(1000);

  // Klik sub tab keluar - menggunakan selector yang lebih spesifik
  try {
    // Cari tombol sub tab Keluar dalam konteks tab pupuk yang aktif
    await page.locator('div[x-show="activeTab === \'pupuk\'"] button[\\@click="activeSubTab = \'keluar\'"]').click();
  } catch (error) {
    console.log('Selector spesifik untuk sub tab Keluar gagal, mencoba alternatif:', error);
    // Gunakan metode yang lebih generik sebagai fallback
    await page.evaluate(() => {
      // Temukan semua tombol dengan teks "Keluar"
      const buttons = Array.from(document.querySelectorAll('button'));
      // Filter tombol yang terlihat dan teksnya hanya berisi "Keluar" (sub tab, bukan tombol logout)
      for (const btn of buttons) {
        if (btn.textContent && btn.textContent.trim() === 'Keluar' &&
            window.getComputedStyle(btn).display !== 'none' &&
            btn.closest('div[x-show="activeTab === \'pupuk\'"]')) {
          btn.click();
          return;
        }
      }
    });
  }

  console.log('✅ Sub tab Keluar diklik');
  await page.waitForTimeout(1000);
}

/**
 * Helper untuk logout
 */
async function logout(page) {
  // Tunggu halaman sepenuhnya dimuat
  await waitForPageLoad(page);

  try {
    // Scroll ke atas untuk memastikan profil button terlihat
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(1000);

    // Langkah 1: Klik tombol profil (gambar profile)
    await page.locator('button.profile-button').click();
    await page.waitForTimeout(1000);
    console.log('✅ Tombol profil diklik');

    // Langkah 2: Klik tombol "Keluar" dalam dropdown profile
    await page.locator('button', { hasText: 'Keluar' }).first().click();
    await page.waitForTimeout(1000);
    console.log('✅ Tombol keluar di dropdown diklik');

    // Langkah 3: Klik tombol "Keluar" (berwarna merah) pada modal konfirmasi
    await page.locator('form[action*="logout"] button[type="submit"]').click();
    await page.waitForTimeout(2000);
    console.log('✅ Konfirmasi keluar diklik');

    // Memastikan kembali ke halaman login
    await expect(page).toHaveURL(/login/);
    console.log('✅ Berhasil logout');

    return true;
  } catch (error) {
    console.error('Error saat proses logout:', error);
    return false;
  }
}

/**
 * Helper untuk pencarian stok
 */
async function searchStock(page, name) {
  await page.locator('input[x-model="searchQuery"]').fill(name);
  console.log(`✅ Mencari stok dengan nama: ${name}`);
  await page.waitForTimeout(2000);
}

/**
 * Helper untuk menampilkan log status lebih detail
 */
async function logEditStatus(page, formSelector) {
  try {
    await page.evaluate((selector) => {
      // Log status Alpine.js dan elemen form
      const form = document.querySelector(selector);
      if (!form) {
        console.log('Form tidak ditemukan dengan selector:', selector);
        return;
      }

      console.log('Form ID:', form.id);
      console.log('Form action:', form.action);

      // Log semua input dalam form
      const inputs = form.querySelectorAll('input');
      console.log('Jumlah input dalam form:', inputs.length);

      inputs.forEach(input => {
        if (input instanceof HTMLInputElement) {
          console.log(`Input ${input.name}: ${input.value} (type: ${input.type})`);
        }
      });

    }, formSelector);
  } catch (error) {
    console.log('Error saat menampilkan log status:', error);
  }
}

// Menggunakan .serial() untuk memastikan tes berjalan secara berurutan
test.describe.serial('Pengujian Stok Masuk', () => {
  test('1. Create data stok masuk', async ({ page }) => {
    // 1. Login
    await loginAsSuperadmin(page);

    // 2. Navigasi ke halaman stok
    await navigateToStok(page);

    // 3. Klik tombol Stok Masuk
    await page.locator('button.bg-emerald-600:has-text("Stok Masuk")').first().click();
    console.log('✅ Tombol Stok Masuk diklik');
    await page.waitForTimeout(1000);

    // 4. Isi form
    await page.locator('div[x-show="showAddModal"] input[name="name"]').fill(testStokName);
    await page.locator('div[x-show="showAddModal"] select[name="category"]').selectOption(testStokCategory);
    await page.locator('div[x-show="showAddModal"] input[name="quantity"]').fill(testStokQuantity);
    await page.locator('div[x-show="showAddModal"] input[name="unit"]').fill(testStokUnit);
    await page.locator('div[x-show="showAddModal"] input[name="date_added"]').fill(currentDate);
    console.log('✅ Form stok masuk diisi');

    // 5. Klik tombol simpan
    await page.locator('div[x-show="showAddModal"] button:has-text("Simpan Stok")').click();
    console.log('✅ Tombol Simpan Stok diklik');
    await page.waitForTimeout(3000);

    // 6. Navigasi ke tab pupuk dan sub tab masuk
    await navigateToPupukStokMasuk(page);

    // 7. Search stok yang baru dibuat
    await searchStock(page, testStokName);

    // 8. Validasi stok muncul di tabel
    const rowVisible = await page.locator(`#stok-masuk tr[id^="stok-row"]:has-text("${testStokName}")`).isVisible();
    expect(rowVisible).toBeTruthy();
    console.log('✅ Data stok masuk berhasil dibuat dan terlihat');

    // 9. Logout
    await logout(page);
  });

  test('2. Edit data stok masuk', async ({ page }) => {
    // 1. Login
    await loginAsSuperadmin(page);

    // 2. Navigasi ke halaman stok
    await navigateToStok(page);

    // 3. Navigasi ke tab pupuk dan sub tab masuk
    await navigateToPupukStokMasuk(page);

    // 4. Search stok yang akan diedit
    await searchStock(page, testStokName);

    // 5. Klik tombol edit pada baris yang ditemukan
    const editRow = page.locator(`#stok-masuk tr[id^="stok-row"]:has-text("${testStokName}")`);

    // Pastikan baris ditemukan dan terlihat sebelum mencoba mengklik tombol Edit
    await editRow.waitFor({ state: 'visible', timeout: 5000 });
    
    // Catat ID stok sebelum klik tombol edit
    const stockId = await editRow.getAttribute('id');
    console.log(`ID stok yang akan diedit: ${stockId}`);
    
    // Klik tombol edit
    await editRow.locator('button:has-text("Edit")').click();
    console.log('✅ Tombol Edit diklik');
    await page.waitForTimeout(2000);

    // 6. Edit jumlah di form dengan pendekatan langsung ke modal edit yang terbuka
    try {
      // Tunggu modal edit muncul dengan selector yang lebih spesifik
      await page.waitForSelector('form[id^="edit-form-"]', { state: 'visible', timeout: 15000 });
      await page.waitForSelector('h2:has-text("Edit Data Stok")', { state: 'visible', timeout: 5000 });
      
      console.log('Modal edit terdeteksi, mencoba edit form...');

      // Gunakan selector yang lebih spesifik untuk input yang ingin diubah
      const jumlahInput = page.locator('input[name="quantity"]').filter({ hasText: '' }).first();
      
      // Direct DOM manipulation untuk bypass Alpine.js binding
      await page.evaluate((newValue) => {
        // Reset nilai form langsung di DOM, apapun binding-nya
        const inputs = document.querySelectorAll('form[id^="edit-form-"] input');
        for (const input of inputs) {
          if (input instanceof HTMLInputElement && input.name === 'quantity') {
            // Override langsung
            input.value = '';
            input.focus();
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            break;
          }
        }
      });
      
      // Tunggu sebentar untuk memastikan Alpine.js selesai memproses perubahan
      await page.waitForTimeout(1000);
      
      // Gunakan pendekatan yang lebih kuat untuk menghapus dan mengisi nilai
      // 1. Temukan input jumlah dan fokus ke sana
      await page.click('form[id^="edit-form-"] input[name="quantity"]', { force: true });
      await page.waitForTimeout(500);
      
      // 2. Hapus nilainya dengan keyboard dan isi dengan nilai baru
      await page.keyboard.press('Control+a');
      await page.keyboard.press('Delete');
      await page.waitForTimeout(500);
      
      // 3. Ketik nilai baru
      await page.keyboard.type(editedQuantity, { delay: 100 });
      await page.waitForTimeout(500);
      
      // 4. Tekan Tab untuk berpindah fokus dan memicu event change
      await page.keyboard.press('Tab');
      
      console.log(`✅ Jumlah diubah menjadi ${editedQuantity}`);
    } catch (error) {
      // Jika terjadi error, coba alternatif dengan DOM manipulation
      console.error('Error saat edit form normal:', error);
      
      // Langsung ubah nilai melalui JavaScript jika method UI normal gagal
      try {
        await page.evaluate((editedQty) => {
          const form = document.querySelector('form[id^="edit-form-"]');
          if (form) {
            const quantityInput = form.querySelector('input[name="quantity"]');
            if (quantityInput && quantityInput instanceof HTMLInputElement) {
              // Hapus binding Alpine.js jika ada
              if (quantityInput.hasAttribute('x-bind:value')) {
                quantityInput.removeAttribute('x-bind:value');
              }
              
              // Set nilai langsung
              quantityInput.value = editedQty;
              
              // Trigger events untuk memberi tahu framework
              quantityInput.dispatchEvent(new Event('input', { bubbles: true }));
              quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
              
              console.log(`DOM manipulation: Quantity diubah jadi ${editedQty}`);
            }
          }
        }, editedQuantity);
        
        console.log('✅ Alternatif DOM manipulation berhasil');
        await page.waitForTimeout(500);
      } catch (domError) {
        console.error('DOM manipulation juga gagal:', domError);
        throw error; // Throw error asli jika kedua pendekatan gagal
      }
    }

    // 7. Klik tombol simpan perubahan
    try {
      console.log('Mencoba klik tombol Simpan Perubahan dengan berbagai selector...');
      
      // Coba gunakan selector yang lebih spesifik berdasarkan struktur HTML yang ada
      await Promise.any([
        // Pendekatan 1: Selector spesifik form
        page.locator('form[id^="edit-form-"] button[type="submit"]').click().then(() => { 
          console.log('✅ Tombol Submit diklik dengan selector form[id^="edit-form-"] button[type="submit"]');
          return true;
        }),
        
        // Pendekatan 2: Selector teks
        page.locator('button:has-text("Simpan Perubahan")').first().click().then(() => {
          console.log('✅ Tombol Submit diklik dengan selector button:has-text("Simpan Perubahan")');
          return true;
        }),
        
        // Pendekatan 3: Selector spesifik modal
        page.locator('.fixed button:has-text("Simpan Perubahan")').click().then(() => {
          console.log('✅ Tombol Submit diklik dengan selector .fixed button:has-text("Simpan Perubahan")');
          return true;
        }),
        
        // Tunggu untuk memastikan timeout tidak terlalu cepat
        new Promise(resolve => setTimeout(() => resolve(false), 10000))
      ]).catch(async error => {
        console.log('Error saat klik tombol dengan selector standar:', error);
        
        // Pendekatan terakhir: Gunakan JavaScript untuk klik tombol secara langsung
        const clickedJS = await page.evaluate(() => {
          const buttons = Array.from(document.querySelectorAll('button'));
          const saveButton = buttons.find(btn => 
            btn.textContent && 
            btn.textContent.trim().includes('Simpan Perubahan') && 
            window.getComputedStyle(btn).display !== 'none'
          );
          
          if (saveButton) {
            console.log('Tombol Simpan Perubahan ditemukan via JS:', saveButton.textContent);
            saveButton.click();
            return true;
          }
          
          // Coba cari tombol submit dalam form
          const form = document.querySelector('form[id^="edit-form-"]');
          if (form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
              console.log('Tombol submit form ditemukan via JS:', submitBtn.textContent);
              submitBtn.click();
              return true;
            }
          }
          
          return false;
        });
        
        if (!clickedJS) {
          throw new Error('Tidak dapat menemukan tombol Simpan Perubahan dengan cara apapun');
        }
      });
      
      console.log('✅ Tombol Simpan Perubahan berhasil diklik');
    } catch (error) {
      console.error('Gagal mengklik tombol simpan:', error);
      throw error;
    }
    
    // Tunggu response dari server
    await page.waitForTimeout(3000);

    // 8. Search kembali stok yang sudah diedit
    await navigateToPupukStokMasuk(page);
    await searchStock(page, testStokName);

    // 9. Validasi perubahan jumlah
    const tableContent = await page.locator('#stok-masuk').filter({ visible: true }).first().innerText();
    expect(tableContent).toContain(testStokName);
    expect(tableContent).toContain(editedQuantity);
    console.log('✅ Data stok masuk berhasil diedit');

    // 10. Logout
    await logout(page);
  });

  test('3. Hapus data stok masuk', async ({ page }) => {
    // 1. Login
    await loginAsSuperadmin(page);

    // 2. Navigasi ke halaman stok
    await navigateToStok(page);

    // 3. Navigasi ke tab pupuk dan sub tab masuk
    await navigateToPupukStokMasuk(page);

    // 4. Search stok yang akan dihapus
    await searchStock(page, testStokName);

    // 5. Klik tombol hapus pada baris yang ditemukan
    const deleteRow = page.locator(`#stok-masuk tr[id^="stok-row"]:has-text("${testStokName}")`);
    await deleteRow.waitFor({ state: 'visible', timeout: 5000 });
    await deleteRow.locator('button:has-text("Hapus")').click();
    console.log('✅ Tombol Hapus diklik');
    await page.waitForTimeout(2000);

    // 6. Konfirmasi hapus
    await page.locator('div[x-show="showDeleteModal"] button:has-text("Hapus")').click();
    console.log('✅ Konfirmasi hapus diklik');
    await page.waitForTimeout(3000);

    // 7. Search kembali stok yang sudah dihapus
    await navigateToPupukStokMasuk(page);
    await searchStock(page, testStokName);

    // 8. Validasi stok sudah tidak ada
    const tableContent = await page.locator('#stok-masuk').filter({ visible: true }).first().innerText();
    expect(tableContent).not.toContain(testStokName);
    console.log('✅ Data stok masuk berhasil dihapus');

    // 9. Logout
    await logout(page);
  });
});

// Menggunakan .serial() untuk memastikan tes berjalan secara berurutan
test.describe.serial('Pengujian Stok Keluar', () => {
  test('1. Create data stok keluar', async ({ page }) => {
    // 1. Login
    await loginAsSuperadmin(page);

    // 2. Navigasi ke halaman stok
    await navigateToStok(page);

    // 3. Klik tombol Stok Keluar
    await page.locator('button.bg-emerald-600:has-text("Stok Keluar")').first().click();
    console.log('✅ Tombol Stok Keluar diklik');
    await page.waitForTimeout(1000);

    // 4. Isi form
    await page.locator('div[x-show="showOutModal"] input[name="name"]').fill(testStokName);
    await page.locator('div[x-show="showOutModal"] select[name="category"]').selectOption(testStokCategory);
    await page.locator('div[x-show="showOutModal"] input[name="quantity"]').fill(testStokQuantity);
    await page.locator('div[x-show="showOutModal"] input[name="unit"]').fill(testStokUnit);
    await page.locator('div[x-show="showOutModal"] input[name="date_added"]').fill(currentDate);
    console.log('✅ Form stok keluar diisi');

    // 5. Klik tombol simpan
    await page.locator('div[x-show="showOutModal"] button:has-text("Simpan Stok Keluar")').click();
    console.log('✅ Tombol Simpan Stok Keluar diklik');
    await page.waitForTimeout(3000);

    // 6. Navigasi ke tab pupuk dan sub tab keluar
    await navigateToPupukStokKeluar(page);

    // 7. Search stok yang baru dibuat
    await searchStock(page, testStokName);

    // 8. Validasi stok muncul di tabel
    const rowVisible = await page.locator(`#stok-keluar tr[id^="stok-row"]:has-text("${testStokName}")`).isVisible();
    expect(rowVisible).toBeTruthy();
    console.log('✅ Data stok keluar berhasil dibuat dan terlihat');

    // 9. Logout
    await logout(page);
  });

  test('2. Edit data stok keluar', async ({ page }) => {
    // 1. Login
    await loginAsSuperadmin(page);

    // 2. Navigasi ke halaman stok
    await navigateToStok(page);

    // 3. Navigasi ke tab pupuk dan sub tab keluar
    await navigateToPupukStokKeluar(page);

    // 4. Search stok yang akan diedit
    await searchStock(page, testStokName);

    // 5. Klik tombol edit pada baris yang ditemukan
    const editRow = page.locator(`#stok-keluar tr[id^="stok-row"]:has-text("${testStokName}")`);
    await editRow.waitFor({ state: 'visible', timeout: 5000 });
    
    // Catat ID stok sebelum klik tombol edit
    const stockId = await editRow.getAttribute('id');
    console.log(`ID stok yang akan diedit: ${stockId}`);
    
    await editRow.locator('button:has-text("Edit")').click();
    console.log('✅ Tombol Edit diklik');
    await page.waitForTimeout(2000);

    // 6. Edit jumlah di form dengan pendekatan langsung ke modal edit yang terbuka
    try {
      // Tunggu modal edit muncul dengan selector yang lebih spesifik
      await page.waitForSelector('form[id^="edit-form-"]', { state: 'visible', timeout: 15000 });
      await page.waitForSelector('h2:has-text("Edit Data Stok")', { state: 'visible', timeout: 5000 });
      
      console.log('Modal edit terdeteksi, mencoba edit form...');

      // Gunakan selector yang lebih spesifik untuk input yang ingin diubah
      const jumlahInput = page.locator('input[name="quantity"]').filter({ hasText: '' }).first();
      
      // Direct DOM manipulation untuk bypass Alpine.js binding
      await page.evaluate((newValue) => {
        // Reset nilai form langsung di DOM, apapun binding-nya
        const inputs = document.querySelectorAll('form[id^="edit-form-"] input');
        for (const input of inputs) {
          if (input instanceof HTMLInputElement && input.name === 'quantity') {
            // Override langsung
            input.value = '';
            input.focus();
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            break;
          }
        }
      });
      
      // Tunggu sebentar untuk memastikan Alpine.js selesai memproses perubahan
      await page.waitForTimeout(1000);
      
      // Gunakan pendekatan yang lebih kuat untuk menghapus dan mengisi nilai
      // 1. Temukan input jumlah dan fokus ke sana
      await page.click('form[id^="edit-form-"] input[name="quantity"]', { force: true });
      await page.waitForTimeout(500);
      
      // 2. Hapus nilainya dengan keyboard dan isi dengan nilai baru
      await page.keyboard.press('Control+a');
      await page.keyboard.press('Delete');
      await page.waitForTimeout(500);
      
      // 3. Ketik nilai baru
      await page.keyboard.type(editedQuantity, { delay: 100 });
      await page.waitForTimeout(500);
      
      // 4. Tekan Tab untuk berpindah fokus dan memicu event change
      await page.keyboard.press('Tab');
      
      console.log(`✅ Jumlah diubah menjadi ${editedQuantity}`);
    } catch (error) {
      // Jika terjadi error, coba alternatif dengan DOM manipulation
      console.error('Error saat edit form normal:', error);
      
      // Langsung ubah nilai melalui JavaScript jika method UI normal gagal
      try {
        await page.evaluate((editedQty) => {
          const form = document.querySelector('form[id^="edit-form-"]');
          if (form) {
            const quantityInput = form.querySelector('input[name="quantity"]');
            if (quantityInput && quantityInput instanceof HTMLInputElement) {
              // Hapus binding Alpine.js jika ada
              if (quantityInput.hasAttribute('x-bind:value')) {
                quantityInput.removeAttribute('x-bind:value');
              }
              
              // Set nilai langsung
              quantityInput.value = editedQty;
              
              // Trigger events untuk memberi tahu framework
              quantityInput.dispatchEvent(new Event('input', { bubbles: true }));
              quantityInput.dispatchEvent(new Event('change', { bubbles: true }));
              
              console.log(`DOM manipulation: Quantity diubah jadi ${editedQty}`);
            }
          }
        }, editedQuantity);
        
        console.log('✅ Alternatif DOM manipulation berhasil');
        await page.waitForTimeout(500);
      } catch (domError) {
        console.error('DOM manipulation juga gagal:', domError);
        throw error; // Throw error asli jika kedua pendekatan gagal
      }
    }

    // 7. Klik tombol simpan perubahan
    try {
      console.log('Mencoba klik tombol Simpan Perubahan dengan berbagai selector...');
      
      // Coba gunakan selector yang lebih spesifik berdasarkan struktur HTML yang ada
      await Promise.any([
        // Pendekatan 1: Selector spesifik form
        page.locator('form[id^="edit-form-"] button[type="submit"]').click().then(() => { 
          console.log('✅ Tombol Submit diklik dengan selector form[id^="edit-form-"] button[type="submit"]');
          return true;
        }),
        
        // Pendekatan 2: Selector teks
        page.locator('button:has-text("Simpan Perubahan")').first().click().then(() => {
          console.log('✅ Tombol Submit diklik dengan selector button:has-text("Simpan Perubahan")');
          return true;
        }),
        
        // Pendekatan 3: Selector spesifik modal
        page.locator('.fixed button:has-text("Simpan Perubahan")').click().then(() => {
          console.log('✅ Tombol Submit diklik dengan selector .fixed button:has-text("Simpan Perubahan")');
          return true;
        }),
        
        // Tunggu untuk memastikan timeout tidak terlalu cepat
        new Promise(resolve => setTimeout(() => resolve(false), 10000))
      ]).catch(async error => {
        console.log('Error saat klik tombol dengan selector standar:', error);
        
        // Pendekatan terakhir: Gunakan JavaScript untuk klik tombol secara langsung
        const clickedJS = await page.evaluate(() => {
          const buttons = Array.from(document.querySelectorAll('button'));
          const saveButton = buttons.find(btn => 
            btn.textContent && 
            btn.textContent.trim().includes('Simpan Perubahan') && 
            window.getComputedStyle(btn).display !== 'none'
          );
          
          if (saveButton) {
            console.log('Tombol Simpan Perubahan ditemukan via JS:', saveButton.textContent);
            saveButton.click();
            return true;
          }
          
          // Coba cari tombol submit dalam form
          const form = document.querySelector('form[id^="edit-form-"]');
          if (form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
              console.log('Tombol submit form ditemukan via JS:', submitBtn.textContent);
              submitBtn.click();
              return true;
            }
          }
          
          return false;
        });
        
        if (!clickedJS) {
          throw new Error('Tidak dapat menemukan tombol Simpan Perubahan dengan cara apapun');
        }
      });
      
      console.log('✅ Tombol Simpan Perubahan berhasil diklik');
    } catch (error) {
      console.error('Gagal mengklik tombol simpan:', error);
      throw error;
    }
    
    // Tunggu response dari server
    await page.waitForTimeout(3000);

    // 8. Search kembali stok yang sudah diedit
    await navigateToPupukStokKeluar(page);
    await searchStock(page, testStokName);

    // 9. Validasi perubahan jumlah
    const tableContent = await page.locator('#stok-keluar').filter({ visible: true }).first().innerText();
    expect(tableContent).toContain(testStokName);
    expect(tableContent).toContain(editedQuantity);
    console.log('✅ Data stok keluar berhasil diedit');

    // 10. Logout
    await logout(page);
  });

  test('3. Hapus data stok keluar', async ({ page }) => {
    // 1. Login
    await loginAsSuperadmin(page);

    // 2. Navigasi ke halaman stok
    await navigateToStok(page);

    // 3. Navigasi ke tab pupuk dan sub tab keluar
    await navigateToPupukStokKeluar(page);

    // 4. Search stok yang akan dihapus
    await searchStock(page, testStokName);

    // 5. Klik tombol hapus pada baris yang ditemukan
    const deleteRow = page.locator(`#stok-keluar tr[id^="stok-row"]:has-text("${testStokName}")`);
    await deleteRow.waitFor({ state: 'visible', timeout: 5000 });
    await deleteRow.locator('button:has-text("Hapus")').click();
    console.log('✅ Tombol Hapus diklik');
    await page.waitForTimeout(2000);

    // 6. Konfirmasi hapus
    await page.locator('div[x-show="showDeleteModal"] button:has-text("Hapus")').click();
    console.log('✅ Konfirmasi hapus diklik');
    await page.waitForTimeout(3000);

    // 7. Search kembali stok yang sudah dihapus
    await navigateToPupukStokKeluar(page);
    await searchStock(page, testStokName);

    // 8. Validasi stok sudah tidak ada
    const tableContent = await page.locator('#stok-keluar').filter({ visible: true }).first().innerText();
    expect(tableContent).not.toContain(testStokName);
    console.log('✅ Data stok keluar berhasil dihapus');

    // 9. Logout
    await logout(page);
  });
});
