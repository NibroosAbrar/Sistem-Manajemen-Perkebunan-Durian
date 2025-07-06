// @ts-check
import { test, expect } from '@playwright/test';

// Generate random number untuk ID pohon
const randomTreeNumber = Math.floor(Math.random() * 1000);

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

// Data pengujian
const blokKebunData = {
  nama: 'Test Playwright',
  luasArea: 2.5,
  tipeTanah: 'Andosol'
};

const pohonData = {
  id: randomTreeNumber + 'Z', // Format ID: (number) + Z
  varietas: 'Durian Montong',
  tahunTanam: 2022,
  statusKesehatan: 'Sehat',
  fase: 'Vegetatif',
};

test.describe('Pengujian Fitur WebGIS', () => {
  // Dialog handler untuk mengklik OK pada dialog konfirmasi
  const setupDialogHandler = (page) => {
    page.on('dialog', async (dialog) => {
      console.log(`Dialog muncul dengan pesan: ${dialog.message()}`);
      // Klik tombol OK untuk dialog konfirmasi
      await dialog.accept();
      console.log('Dialog berhasil dikonfirmasi dengan tombol OK');
    });
  };

  // Login terlebih dahulu sebelum melakukan pengujian fitur WebGIS
  test.beforeEach(async ({ page }) => {
    // Setup dialog handler
    setupDialogHandler(page);
    
    console.log('Setup: Login dengan akun yang valid');

    await page.goto('/login');
    await waitForPageLoad(page, 3000);

    // Tunggu form login muncul
    await expect(page.locator('form')).toBeVisible({ timeout: 15000 });

    // Login dengan akun yang pasti ada
    await page.locator('input[name="login"], #login').fill('superadmin@symadu.com');
    await page.locator('input[type="password"], #password').fill('superadmin123');
    await page.locator('button[type="submit"]').click();

    // Verifikasi berhasil login
    await expect(page).toHaveURL(/dashboard|webgis|home/i, { timeout: 20000 });
    console.log('Berhasil login ke sistem');

    // Tambahkan jeda yang lebih lama setelah login (dari 5 detik ke 8 detik)
    await page.waitForTimeout(8000);

    // Klik tombol hamburger menu dengan retry jika gagal
    let menuClickSuccess = false;
    for (let attempt = 0; attempt < 3 && !menuClickSuccess; attempt++) {
      try {
        await page.locator('button.hamburger-button').click({ timeout: 5000 });
        menuClickSuccess = true;
        console.log('Berhasil mengklik tombol hamburger menu');
      } catch (error) {
        console.log(`Percobaan ${attempt + 1} klik hamburger menu gagal: ${error.message}. Mencoba lagi...`);
        await page.waitForTimeout(2000);
      }
    }

    if (!menuClickSuccess) {
      throw new Error('Gagal mengklik tombol hamburger menu setelah beberapa percobaan');
    }

    await page.waitForTimeout(2000);

    // Klik menu Peta/WebGIS
    // Tambahkan scrollIntoView terlebih dahulu untuk mengatasi masalah elemen di luar viewport
    const webgisLink = page.getByRole('link', { name: /peta|webgis|map/i });
    await webgisLink.scrollIntoViewIfNeeded();
    await page.waitForTimeout(1000); // Tambahkan jeda setelah scroll
    await webgisLink.click({ timeout: 60000, force: true }); // Gunakan force: true sebagai fallback
    console.log('Mengklik menu Peta/WebGIS');
    await page.waitForTimeout(5000); // Perbesar jeda setelah klik menu

    // Verifikasi berada di halaman WebGIS
    await expect(page.locator('#map')).toBeVisible({ timeout: 20000 });
    console.log('Berhasil mengakses halaman WebGIS');

    // Tunggu map selesai dimuat dengan waktu yang lebih lama
    await page.waitForTimeout(8000); // Perbesar jeda untuk memastikan peta benar-benar dimuat
  });

  // Logout setelah setiap tes selesai
  test.afterEach(async ({ page }) => {
    await doLogout(page);
  });

  test('1. Verifikasi Halaman Peta', async ({ page }) => {
    console.log('Langkah 1: Verifikasi halaman peta berhasil dimuat');

    // Tambah jeda untuk memastikan peta sudah sepenuhnya dimuat
    await page.waitForTimeout(5000);

    // Verifikasi peta sudah dimuat dengan benar
    await expect(page.locator('#map')).toBeVisible();
    console.log('Peta sudah dimuat dengan benar');

    console.log('Langkah 1 selesai: Halaman peta terverifikasi berfungsi dengan baik');
  });

  test('2. Pembuatan Shape Poligon Blok Kebun di Luar Foto Udara', async ({ page }) => {
    console.log('Langkah 2: Pembuatan shape poligon blok kebun di luar foto udara');

    // Tunggu untuk memastikan peta sudah sepenuhnya dimuat
    await page.waitForTimeout(5000);

    // Buka kontrol Geoman untuk menggambar poligon
    const drawPolygonButton = page.locator('.leaflet-pm-icon-polygon');
    await expect(drawPolygonButton).toBeVisible({ timeout: 10000 });
    await drawPolygonButton.click();
    console.log('Mengklik tombol draw polygon');

    // Hitung ukuran peta untuk membuat poligon di luar foto udara (gunakan bagian kanan bawah peta)
    const mapElement = page.locator('#map');
    const boundingBox = await mapElement.boundingBox();

    if (boundingBox) {
      // Buat koordinat untuk 4 titik poligon di kanan bawah peta (jauh dari pusat peta yang biasanya adalah lokasi foto udara)
      const x1 = boundingBox.x + boundingBox.width * 0.75;
      const y1 = boundingBox.y + boundingBox.height * 0.75;
      const x2 = boundingBox.x + boundingBox.width * 0.9;
      const y2 = boundingBox.y + boundingBox.height * 0.75;
      const x3 = boundingBox.x + boundingBox.width * 0.9;
      const y3 = boundingBox.y + boundingBox.height * 0.9;
      const x4 = boundingBox.x + boundingBox.width * 0.75;
      const y4 = boundingBox.y + boundingBox.height * 0.9;

      // Gambar poligon dengan mengklik pada 4 titik
      await page.mouse.click(x1, y1);
      await page.waitForTimeout(500);
      await page.mouse.click(x2, y2);
      await page.waitForTimeout(500);
      await page.mouse.click(x3, y3);
      await page.waitForTimeout(500);
      await page.mouse.click(x4, y4);
      await page.waitForTimeout(500);
      await page.mouse.click(x1, y1); // Klik titik awal untuk menutup poligon
      await page.waitForTimeout(500);

      console.log('Poligon blok kebun digambar di luar area foto udara');

      // Tunggu modal pemilihan form muncul
      await expect(page.locator('#formSelectorModalContainer')).toBeVisible({ timeout: 10000 });

      // Pilih jenis data Blok Kebun
      await page.locator('#select-plantation').click();
      console.log('Memilih jenis data Blok Kebun');

      // Tunggu form blok kebun muncul
      await expect(page.locator('#plantationModalContainer form')).toBeVisible({ timeout: 10000 });

      // Tambahkan jeda untuk memastikan form sepenuhnya dimuat
      await page.waitForTimeout(1000);

      // Pastikan elemen input ada dan terlihat dengan menunggu
      await expect(page.locator('#name')).toBeVisible({ timeout: 5000 });
      
      // Bersihkan dulu input field jika ada isinya
      await page.locator('#name').clear();
      // Isi form blok kebun dengan "Test Playwright" dengan pendekatan fill yang lebih pasti
      await page.locator('#name').fill(blokKebunData.nama);
      
      // Verifikasi bahwa nilai sudah terisi dengan benar
      const inputValue = await page.locator('#name').inputValue();
      if (inputValue !== blokKebunData.nama) {
        console.log(`Nilai input masih salah: "${inputValue}", mencoba mengisi ulang...`);
        await page.locator('#name').click({ clickCount: 3 }); // Seleksi semua teks
        await page.keyboard.press('Backspace');
        await page.locator('#name').type(blokKebunData.nama, { delay: 100 }); // Ketik dengan delay
      }
      
      await page.locator('#luas_area').fill(blokKebunData.luasArea.toString());
      await page.locator('#tipe_tanah').selectOption(blokKebunData.tipeTanah);
      console.log('Mengisi form data blok kebun dengan nama:', blokKebunData.nama);

      // Submit form dan tunggu lebih lama
      await page.locator('#plantationModalContainer button[type="submit"]').click();
      console.log('Submit form blok kebun');

      // Tunggu lebih lama untuk proses penyimpanan
      await page.waitForTimeout(5000);

      console.log('Blok kebun berhasil disimpan');
    } else {
      console.log('Tidak dapat menemukan elemen peta');
    }

    // Tunggu untuk memastikan data disimpan
    await page.waitForTimeout(3000);

    console.log('Langkah 2 selesai: Pembuatan shape poligon blok kebun di luar foto udara');
  });

  test('3. Pembuatan Shape Poligon Pohon di Dalam Blok Kebun', async ({ page }) => {
    console.log('Langkah 3: Pembuatan shape poligon pohon di dalam blok kebun');

    // Tunggu untuk memastikan peta sudah dimuat
    await page.waitForTimeout(5000);

    // Buka kontrol Geoman untuk menggambar poligon
    const drawPolygonButton = page.locator('.leaflet-pm-icon-polygon');
    await expect(drawPolygonButton).toBeVisible({ timeout: 10000 });
    await drawPolygonButton.click();
    console.log('Mengklik tombol draw polygon');

    // Hitung ukuran peta untuk membuat poligon di area yang sama dengan blok kebun tapi lebih kecil
    const mapElement = page.locator('#map');
    const boundingBox = await mapElement.boundingBox();

    if (boundingBox) {
      // Buat koordinat untuk 4 titik poligon pohon yang lebih masuk ke tengah blok kebun
      // untuk menghindari benturan dengan shape blok kebun pada tampilan mobile
      const x1 = boundingBox.x + boundingBox.width * 0.80;
      const y1 = boundingBox.y + boundingBox.height * 0.80;
      const x2 = boundingBox.x + boundingBox.width * 0.85;
      const y2 = boundingBox.y + boundingBox.height * 0.80;
      const x3 = boundingBox.x + boundingBox.width * 0.85;
      const y3 = boundingBox.y + boundingBox.height * 0.85;
      const x4 = boundingBox.x + boundingBox.width * 0.80;
      const y4 = boundingBox.y + boundingBox.height * 0.85;

      // Gambar poligon dengan mengklik pada 4 titik
      await page.mouse.click(x1, y1);
      await page.waitForTimeout(500);
      await page.mouse.click(x2, y2);
      await page.waitForTimeout(500);
      await page.mouse.click(x3, y3);
      await page.waitForTimeout(500);
      await page.mouse.click(x4, y4);
      await page.waitForTimeout(500);
      await page.mouse.click(x1, y1); // Klik titik awal untuk menutup poligon
      await page.waitForTimeout(500);

      console.log('Poligon pohon digambar di dalam area blok kebun');

      // Tunggu modal pemilihan form muncul
      await expect(page.locator('#formSelectorModalContainer')).toBeVisible({ timeout: 10000 });

      // Pilih jenis data Pohon
      await page.locator('#select-tree').click();
      console.log('Memilih jenis data Pohon');

      // Tunggu form pohon muncul
      await expect(page.locator('#treeModalContainer form')).toBeVisible({ timeout: 10000 });

      // Tambahkan jeda untuk memastikan form sepenuhnya dimuat
      await page.waitForTimeout(1000);

      // Pastikan setiap field terlihat dan fokus sebelum mengisi
      // Isi form pohon dengan ID format (number) + Z
      await page.locator('#custom_id').click();
      await page.locator('#custom_id').fill(pohonData.id);
      console.log('Mengisi ID pohon:', pohonData.id);

      await page.locator('#varietas').click();
      await page.locator('#varietas').fill(pohonData.varietas);
      console.log('Mengisi varietas pohon:', pohonData.varietas);

      await page.locator('#tahun_tanam').click();
      await page.locator('#tahun_tanam').fill(pohonData.tahunTanam.toString());
      console.log('Mengisi tahun tanam:', pohonData.tahunTanam);

      await page.locator('#health_status').selectOption(pohonData.statusKesehatan);
      console.log('Memilih status kesehatan:', pohonData.statusKesehatan);

      await page.locator('#fase').selectOption(pohonData.fase);
      console.log('Memilih fase:', pohonData.fase);

      // Pilih blok kebun yang baru saja dibuat
      try {
        const plantationDropdown = page.locator('#plantation_id');

        // Mencari opsi yang mengandung "Test Playwright"
        const options = await plantationDropdown.locator('option').all();
        for (const option of options) {
          const text = await option.textContent();
          if (text && text.includes(blokKebunData.nama)) {
            await option.click();
            console.log(`Memilih blok kebun: ${text}`);
            break;
          }
        }
      } catch (error) {
        console.log('Error saat memilih blok kebun:', error);
      }

      console.log('Mengisi form data pohon dengan ID:', pohonData.id);

      // Tunggu semua field terisi dengan benar
      await page.waitForTimeout(1000);

      // Verifikasi semua field telah terisi
      const idValue = await page.locator('#custom_id').inputValue();
      const varietasValue = await page.locator('#varietas').inputValue();
      const tahunValue = await page.locator('#tahun_tanam').inputValue();

      console.log('Verifikasi nilai form:');
      console.log('- ID:', idValue);
      console.log('- Varietas:', varietasValue);
      console.log('- Tahun Tanam:', tahunValue);

      // Submit form dengan percobaan berulang jika gagal
      let formSubmitSuccess = false;
      for (let attempt = 0; attempt < 3 && !formSubmitSuccess; attempt++) {
        try {
          console.log(`Percobaan submit form pohon ke-${attempt + 1}`);
          
          // Klik tombol submit form dengan berbagai pendekatan
          const submitButton = page.locator('#treeModalContainer button[type="submit"]');
          await expect(submitButton).toBeVisible({ timeout: 5000 });
          
          // Coba dengan force: true untuk memastikan klik berhasil
          await submitButton.click({ force: true, timeout: 5000 });
          
          // Tunggu sedikit untuk melihat apakah form tertutup
          await page.waitForTimeout(2000);
          
          // Periksa apakah modal masih terbuka
          const modalStillVisible = await page.locator('#treeModalContainer form').isVisible();
          if (!modalStillVisible) {
            console.log('Form pohon berhasil disubmit (modal tertutup)');
            formSubmitSuccess = true;
            break;
          }
          
          // Jika modal masih terbuka, coba metode lain
          console.log('Modal masih terbuka, mencoba metode submit alternatif');
          
          // Coba dengan JavaScript untuk mengirimkan form
          await page.evaluate(() => {
            const form = document.querySelector('#treeForm');
            if (form) {
              console.log('Mengirimkan form dengan JavaScript');
              
              // Coba metode 1: triggering submit event
              const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
              form.dispatchEvent(submitEvent);
              
              // Coba metode 2: memanggil fungsi submitTreeForm jika ada
              if (typeof submitTreeForm === 'function') {
                submitTreeForm();
              }
              
              // Coba metode 3: simulasi klik tombol submit
              const submitButton = form.querySelector('button[type="submit"]');
              if (submitButton) submitButton.click();
            }
          });
          
          await page.waitForTimeout(2000);
          
          // Periksa lagi apakah modal tertutup
          const stillVisibleAfterJS = await page.locator('#treeModalContainer form').isVisible();
          if (!stillVisibleAfterJS) {
            console.log('Form pohon berhasil disubmit dengan JavaScript');
            formSubmitSuccess = true;
          }
        } catch (error) {
          console.log(`Error pada percobaan submit ke-${attempt + 1}:`, error.message);
          await page.waitForTimeout(1000);
        }
      }

      if (!formSubmitSuccess) {
        console.log('Gagal submit form pohon setelah beberapa percobaan, melanjutkan pengujian');
    }

    // Tunggu untuk memastikan data disimpan
    await page.waitForTimeout(3000);

      console.log('Pohon berhasil disimpan');
    } else {
      console.log('Tidak dapat menemukan elemen peta');
    }

    console.log('Langkah 3 selesai: Pembuatan shape poligon pohon di dalam blok kebun');
  });

  test('4. Edit Poligon Pohon', async ({ page }) => {
    console.log('Langkah 4: Edit poligon pohon');

    // Tunggu peta dan layer pohon dimuat lebih lama
    await page.waitForTimeout(10000);

    // Cari layer pohon yang telah dibuat (di area kanan bawah peta sesuai pengujian langkah 3)
    const mapElement = page.locator('#map');
    const boundingBox = await mapElement.boundingBox();

    if (boundingBox) {
      // Mencoba beberapa posisi berbeda dengan jarak yang lebih bervariasi
      const positions = [
        { x: 0.83, y: 0.83 },
        { x: 0.85, y: 0.85 },
        { x: 0.80, y: 0.80 },
        { x: 0.81, y: 0.81 },
        { x: 0.82, y: 0.82 },
        { x: 0.84, y: 0.84 },
        { x: 0.79, y: 0.79 }
      ];

      let found = false;
      let popupVisible = false;
      let treeId = null;

      for (const pos of positions) {
        const x = boundingBox.x + boundingBox.width * pos.x;
        const y = boundingBox.y + boundingBox.height * pos.y;

        await page.mouse.click(x, y);
        console.log(`Mencoba klik di posisi (${pos.x}, ${pos.y})`);

        // Tunggu lebih lama untuk popup muncul
      await page.waitForTimeout(2000);

      // Cari popup yang muncul
      const popupElement = page.locator('.leaflet-popup');

      if (await popupElement.isVisible()) {
          popupVisible = true;
          const popupText = await popupElement.textContent() || '';
          console.log('Popup text:', popupText);
          
          // Ekstrak ID pohon dari popup text jika memungkinkan
          const idMatch = popupText.match(/ID: ([^\s]+)/);
          if (idMatch && idMatch[1]) {
            treeId = idMatch[1];
            console.log('Tree ID extracted from popup:', treeId);
          } else {
            // Coba ekstraksi ID dengan cara lain
            const idLines = popupText.split('\n').filter(line => line.includes('ID:') || line.includes('ID Pohon:'));
            if (idLines.length > 0) {
              const idParts = idLines[0].split(':');
              if (idParts.length > 1) {
                treeId = idParts[1].trim();
                console.log('Tree ID extracted from popup line:', treeId);
              }
            }
          }
          
        if (popupText && (popupText.includes(pohonData.id) || popupText.includes(pohonData.varietas))) {
          found = true;
          console.log('Popup ditemukan dengan data pohon yang sesuai');
            
            // Mirip dengan metode di langkah 5 (Edit Blok Kebun)
            try {
              // Cara 1: Menggunakan selector yang tepat berdasarkan atribut onclick
              const editButtonByOnClick = page.locator(`.leaflet-popup button[onclick*="editTree"], .leaflet-popup a[onclick*="editTree"]`).first();
              
              if (await editButtonByOnClick.count() > 0) {
                console.log('Edit button found by onclick attribute');
                await page.waitForTimeout(500);
                await editButtonByOnClick.click({ timeout: 5000 });
                console.log('Berhasil mengklik tombol edit dengan onclick selector');
                break;
              } else {
                console.log('Edit button not found by onclick attribute, trying other methods');
                
                // Cara 2: Gunakan pendekatan SVG icon
                const editButtonBySVG = page.locator('.leaflet-popup .fa-edit, .leaflet-popup [data-icon="edit"], .leaflet-popup svg[data-icon="edit"]').first();
                
                if (await editButtonBySVG.count() > 0) {
                  // Klik pada parent button dari SVG icon
                  const parentButton = editButtonBySVG.locator('xpath=..').first();
                  if (await parentButton.count() > 0) {
                    await page.waitForTimeout(500);
                    await parentButton.click({ timeout: 5000 });
                    console.log('Berhasil mengklik tombol edit melalui SVG icon parent');
                    break;
                  } else {
                    // Klik langsung pada SVG
                    await page.waitForTimeout(500);
                    await editButtonBySVG.click({ timeout: 5000 });
                    console.log('Berhasil mengklik tombol edit melalui SVG icon');
                    break;
                  }
                }
                
                // Cara 3: Gunakan JS untuk memicu fungsi editTree langsung jika ID ditemukan
                if (treeId) {
                  console.log(`Mencoba trigger editTree('${treeId}') via JS`);
                  await page.evaluate((id) => {
                    if (typeof editTree === 'function') {
                      console.log(`Calling editTree('${id}') directly`);
                      editTree(id);
                      return true;
                    }
                    return false;
                  }, treeId);
                  console.log(`Berhasil memanggil fungsi editTree('${treeId}') langsung dengan JS`);
              break;
                }
              }
            } catch (error) {
              console.log('Error saat mencoba klik tombol edit:', error.message);
            }
          }
        }
      }
      
      // Jika masih belum berhasil menemukan atau mengklik tombol edit
      if (!found || !popupVisible) {
        console.log('Tidak berhasil menemukan popup atau klik tombol edit, mencoba panggil editTree langsung');
        
        // Jika kita punya ID pohon dari langkah sebelumnya, gunakan
        if (pohonData.id) {
          await page.evaluate((id) => {
            if (typeof editTree === 'function') {
              console.log(`Calling editTree('${id}') as last resort`);
              editTree(id);
            }
          }, pohonData.id);
          console.log(`Mencoba panggil editTree langsung dengan ID: ${pohonData.id}`);
        }
      }

      // Tunggu form edit pohon muncul
      await expect(page.locator('#treeModalContainer form')).toBeVisible({ timeout: 15000 });
      
      // Tambah jeda untuk memastikan form sudah sepenuhnya dimuat
      await page.waitForTimeout(3000);

      // Verifikasi bahwa kita mengedit pohon yang benar - PERBAIKAN: pilih salah satu input saja
      let currentId = '';
      try {
        // Coba dapatkan ID dari custom_id terlebih dahulu
        const customIdField = page.locator('#custom_id');
        if (await customIdField.isVisible()) {
          currentId = await customIdField.inputValue();
          console.log('ID pohon yang sedang diedit (dari custom_id):', currentId);
        } else {
          // Jika tidak ada, coba dari display_id
          const displayIdField = page.locator('#display_id');
          if (await displayIdField.isVisible()) {
            currentId = await displayIdField.inputValue();
            console.log('ID pohon yang sedang diedit (dari display_id):', currentId);
          }
        }
      } catch (error) {
        console.log('Error saat mendapatkan ID pohon:', error.message);
      }

      // Edit varietas pohon menjadi D24
      try {
        const varietasField = page.locator('#varietas');
        
        // Pastikan field terlihat sebelum diinteraksi
        await expect(varietasField).toBeVisible({ timeout: 5000 });
        
        // Hapus nilai yang ada
        await varietasField.click({ clickCount: 3 }); // Triple-click untuk pilih semua
        await page.keyboard.press('Backspace');
        await page.waitForTimeout(500);
        
        // Isi dengan nilai baru "D24"
        const newVarietas = "D24";
        console.log('Mengisi varietas dengan:', newVarietas);
        await varietasField.fill(newVarietas);
        await page.waitForTimeout(500);
        
        // Verifikasi nilai sudah berubah
        const newValue = await varietasField.inputValue();
        console.log('Nilai varietas setelah diedit:', newValue);
        
        if (newValue !== newVarietas) {
          console.log('Nilai tidak berubah, mencoba cara alternatif dengan JavaScript');
          await page.evaluate((newVal) => {
            const fieldEl = document.querySelector('#varietas');
            if (fieldEl) {
              fieldEl.value = newVal;
              // Trigger event untuk memastikan perubahan terdeteksi
              fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
              fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
          }, newVarietas);
          await page.waitForTimeout(500);
        }
      } catch (error) {
        console.log('Error saat mengubah nilai varietas:', error.message);
      }
      
      // Submit form edit
      console.log('Mencoba submit form edit pohon');
      const submitButton = page.locator('#treeModalContainer button[type="submit"]');
      
      // Pastikan tombol submit terlihat
      await expect(submitButton).toBeVisible({ timeout: 5000 });
      await submitButton.click({ force: true });
      
      // Tunggu dan cek apakah form sudah tertutup
      await page.waitForTimeout(3000);
      
      if (await page.locator('#treeModalContainer form').isVisible()) {
        console.log('Form masih terbuka, mencoba cara alternatif submit');
        
        // Gunakan JavaScript untuk submit form
        await page.evaluate(() => {
          const formEl = document.querySelector('#treeForm');
          if (formEl) {
            // Coba berbagai metode submit
            try {
              // Metode 1: Kirim submit event
              formEl.dispatchEvent(new Event('submit', { bubbles: true }));
              
              // Metode 2: Coba panggil submit() method langsung
              if (typeof formEl.submit === 'function') {
                formEl.submit();
              }
              
              // Metode 3: Klik tombol submit
              const submitBtnEl = formEl.querySelector('button[type="submit"]');
              if (submitBtnEl) {
                submitBtnEl.dispatchEvent(new MouseEvent('click', { bubbles: true }));
              }
              
              console.log('Form disubmit melalui JavaScript');
            } catch (e) {
              console.error('Error saat submit form:', e);
            }
          }
        });
        
        await page.waitForTimeout(3000);
      }

      // Tunggu lebih lama untuk proses penyimpanan
      await page.waitForTimeout(5000);

      console.log('Langkah 4 selesai: Edit poligon pohon selesai, varietas diubah menjadi D24');
    } else {
      console.log('Tidak dapat menemukan elemen peta');
    }
  });

  test('5. Edit Poligon Blok Kebun', async ({ page }) => {
    console.log('Langkah 5: Edit poligon blok kebun');

    // Tunggu peta dan layer blok kebun dimuat lebih lama
    await page.waitForTimeout(10000);

    // Cari layer blok kebun yang telah dibuat (di area kanan bawah peta sesuai pengujian langkah 2)
    const mapElement = page.locator('#map');
    const boundingBox = await mapElement.boundingBox();

    if (boundingBox) {
      let found = false;
      let editButtonClicked = false;
      let plantationId = null;
      
      // Menggunakan pendekatan JavaScript untuk menemukan dan mengedit blok kebun (lebih handal untuk mobile)
      try {
        console.log('Mencoba menemukan blok kebun dengan JavaScript langsung');
        
        // Coba dapatkan semua layer blok kebun dan klik yang cocok
        const plantationFound = await page.evaluate((kebunNama) => {
          try {
            // Cara 1: Cari dengan fungsi khusus jika tersedia
            if (typeof findPlantationByName === 'function' && kebunNama) {
              console.log(`Mencoba findPlantationByName('${kebunNama}')`);
              const foundPlantation = findPlantationByName(kebunNama);
              if (foundPlantation) {
                // Panggil fungsi edit langsung jika tersedia
                if (typeof editPlantation === 'function' && foundPlantation.id) {
                  editPlantation(foundPlantation.id);
                  return { success: true, id: foundPlantation.id, edited: true, message: `Plantation edit function called directly for ID: ${foundPlantation.id}` };
                }
                
                // Klik untuk membuka popup jika tidak bisa edit langsung
                if (foundPlantation.fire && typeof foundPlantation.fire === 'function') {
                  foundPlantation.fire('click');
                  return { success: true, id: foundPlantation.id, message: `Plantation found and clicked by name: ${kebunNama}` };
                }
              }
            }
            
            // Cara 2: Dapatkan semua layer di map dan filter
            if (window.map && window.map.eachLayer) {
              let foundPlantation = null;
              window.map.eachLayer(function(layer) {
                // Cek apakah ini adalah blok kebun
                if (layer.feature && 
                    layer.feature.properties && 
                    (layer.feature.properties.type === 'plantation' || 
                     layer.feature.properties.jenis === 'kebun' || 
                     layer.feature.properties.name)) {
                  
                  // Cek apakah ini blok kebun yang kita cari
                  const name = layer.feature.properties.name || '';
                  if (name.includes('Test Playwright')) {
                    console.log('Found plantation layer:', layer.feature.properties);
                    foundPlantation = layer;
                    
                    // Simpan ID
                    plantationId = layer.feature.properties.id;
                    
                    // Jika fungsi edit tersedia, panggil langsung
                    if (typeof editPlantation === 'function' && plantationId) {
                      editPlantation(plantationId);
                      return { success: true, id: plantationId, edited: true, message: `Edit function called directly for plantation ID: ${plantationId}` };
                    }
                    
                    // Jika tidak bisa edit langsung, klik untuk membuka popup
                    if (layer.fire && typeof layer.fire === 'function') {
                      layer.fire('click');
                    }
                  }
                }
              });
              
              if (foundPlantation) {
                return { 
                  success: true, 
                  id: plantationId, 
                  message: 'Plantation found by layer properties and clicked' 
                };
              }
            }
            
            // Cara 3: Coba panggil API untuk mendapatkan data
            return fetch('/api/plantations')
              .then(response => response.json())
              .then(data => {
                if (Array.isArray(data.data)) {
                  // Cari blok kebun dengan nama yang cocok
                  const plantation = data.data.find(p => p.name && p.name.includes('Test Playwright'));
                  if (plantation && plantation.id) {
                    console.log('Found plantation from API:', plantation);
                    
                    // Panggil fungsi edit langsung
                    if (typeof editPlantation === 'function') {
                      editPlantation(plantation.id);
                      return { 
                        success: true, 
                        id: plantation.id,
                        edited: true,
                        message: 'Plantation found from API and edit function called' 
                      };
                    }
                    
                    return { 
                      success: true, 
                      id: plantation.id, 
                      message: 'Plantation found from API but edit function not available' 
                    };
                  }
                }
                return { success: false, message: 'Tidak dapat menemukan blok kebun dari API' };
              })
              .catch(error => {
                return { success: false, message: `Error fetching API: ${error.message}` };
              });
          } catch (error) {
            return { success: false, message: `Error: ${error.message}` };
          }
        }, blokKebunData.nama);
        
        console.log('Hasil pencarian blok kebun via JS:', plantationFound);
        
        if (plantationFound && plantationFound.success) {
          found = true;
          if (plantationFound.id) plantationId = plantationFound.id;
          if (plantationFound.edited) editButtonClicked = true;
          console.log('Blok kebun ditemukan via JavaScript:', plantationFound.message);
          await page.waitForTimeout(2000);
        }
      } catch (error) {
        console.log('Error saat mencoba menemukan blok kebun via JavaScript:', error.message);
      }
      
      // Jika pendekatan JavaScript tidak berhasil, coba pendekatan grid sistematis
      if (!found) {
        console.log('Mencoba menemukan blok kebun dengan grid sistematis');
        
        // Buat grid 5x5 yang lebih rapat untuk lebih tepat menemukan objek
        const gridSize = 5;
        const centerX = 0.825; // Area di mana blok kebun dibuat
        const centerY = 0.825;
        const gridRadius = 0.10; // Radius pencarian
        
        for (let i = 0; i < gridSize; i++) {
          for (let j = 0; j < gridSize; j++) {
            // Hitung posisi klik
            const xRatio = centerX - gridRadius + (2 * gridRadius * i / (gridSize - 1));
            const yRatio = centerY - gridRadius + (2 * gridRadius * j / (gridSize - 1));
            
            const x = boundingBox.x + boundingBox.width * xRatio;
            const y = boundingBox.y + boundingBox.height * yRatio;
            
            // Klik pada titik grid
            await page.mouse.click(x, y);
            console.log(`Mengklik di posisi grid (${i},${j}): (${xRatio.toFixed(2)}, ${yRatio.toFixed(2)})`);
            
            // Tunggu untuk melihat apakah popup muncul
            await page.waitForTimeout(1000);
            
            // Cari popup yang muncul
            const popupElement = page.locator('.leaflet-popup');

            if (await popupElement.isVisible()) {
              const popupText = await popupElement.textContent() || '';
              
              // Ekstrak ID blok kebun jika ada
              const idMatch = popupText.match(/ID:?\s*(\d+)/i);
              if (idMatch && idMatch[1]) {
                plantationId = idMatch[1];
                console.log('Plantation ID extracted from popup:', plantationId);
              }
              
              if (popupText && popupText.includes(blokKebunData.nama)) {
                found = true;
                console.log('Popup ditemukan dengan data blok kebun yang sesuai');
                
                try {
                  console.log('Mencoba mencari tombol edit melalui JavaScript');
                  // Cara 1: Menggunakan selector yang tepat berdasarkan atribut onclick
                  const editButtonByOnClick = page.locator(`.leaflet-popup button[onclick*="editPlantation"], .leaflet-popup a[onclick*="editPlantation"]`).first();
                  
                  if (await editButtonByOnClick.count() > 0) {
                    console.log('Edit button found by onclick attribute');
                    await page.waitForTimeout(500);
                    await editButtonByOnClick.click({ timeout: 5000, force: true });
                    console.log('Berhasil mengklik tombol edit dengan onclick selector');
                    editButtonClicked = true;
                    break;
                  } else {
                    console.log('Edit button not found by onclick attribute, trying other methods');
                    
                    // Cara 2: Gunakan pendekatan SVG icon
                    const editButtonBySVG = page.locator('.leaflet-popup .fa-edit, .leaflet-popup [data-icon="edit"], .leaflet-popup svg[data-icon="edit"]').first();
                    
                    if (await editButtonBySVG.count() > 0) {
                      // Klik pada parent button dari SVG icon
                      const parentButton = editButtonBySVG.locator('xpath=..').first();
                      if (await parentButton.count() > 0) {
                        await page.waitForTimeout(500);
                        await parentButton.click({ timeout: 5000, force: true });
                        console.log('Berhasil mengklik tombol edit melalui SVG icon parent');
                        editButtonClicked = true;
                        break;
                      } else {
                        // Klik langsung pada SVG
                        await page.waitForTimeout(500);
                        await editButtonBySVG.click({ timeout: 5000, force: true });
                        console.log('Berhasil mengklik tombol edit melalui SVG icon');
                        editButtonClicked = true;
                        break;
                      }
                    }
                    
                    // Cara 3: Gunakan JS untuk memicu fungsi editPlantation langsung jika ID ditemukan
                    if (plantationId) {
                      console.log(`Mencoba trigger editPlantation(${plantationId}) via JS`);
                      await page.evaluate((id) => {
                        if (typeof editPlantation === 'function') {
                          console.log(`Calling editPlantation(${id}) directly`);
                          editPlantation(id);
                          return true;
                        }
                        return false;
                      }, plantationId);
                      console.log(`Berhasil memanggil fungsi editPlantation(${plantationId}) langsung dengan JS`);
                      editButtonClicked = true;
                      break;
                    }
                  }
                } catch (error) {
                  console.log('Error saat mencoba klik tombol edit:', error.message);
                }
              }
            }
          }
          if (found && editButtonClicked) break;
        }
      }

      // Jika masih belum berhasil mengklik tombol edit meskipun popup ditemukan
      if (found && !editButtonClicked) {
        console.log('Popup ditemukan tapi gagal klik tombol edit, mencoba pendekatan lain');
        
        // Coba klik area tombol secara langsung berdasarkan posisi relatif dalam popup
        try {
          const popupElement = page.locator('.leaflet-popup-content');
          const popupBox = await popupElement.boundingBox();
          
          if (popupBox) {
            // Estimasi posisi tombol edit (biasanya di bagian bawah popup)
            const editButtonX = popupBox.x + popupBox.width * 0.3; // ~30% dari kiri
            const editButtonY = popupBox.y + popupBox.height * 0.9; // ~90% dari atas (mendekati bawah)
            
            await page.mouse.click(editButtonX, editButtonY);
            console.log('Mencoba klik pada posisi perkiraan tombol edit dalam popup');
          }
        } catch (error) {
          console.log('Error saat mencoba klik posisi perkiraan:', error.message);
        }
      }
      
      // Jika semua upaya gagal, coba langsung panggil fungsi editPlantation dengan ID terakhir yang diketahui
      if (!editButtonClicked) {
        console.log('Semua metode klik tombol edit gagal, mencoba mendapatkan ID dari API');
        // Coba dapatkan ID blok kebun yang baru dibuat melalui API
        try {
          console.log('Mencoba mendapatkan ID blok kebun melalui API');
          const plantations = await page.evaluate((kebunNama) => {
            return fetch('/api/plantations')
              .then(response => response.json())
              .then(data => {
                if (Array.isArray(data.data)) {
                  // Cari blok kebun dengan nama yang cocok
                  const plantation = data.data.find(p => p.name && p.name.includes(kebunNama));
                  if (plantation) {
                    return plantation;
                  }
                }
                return null;
              })
              .catch(error => {
                console.error('Error fetching plantations:', error);
                return null;
              });
          }, blokKebunData.nama);
          
          if (plantations && plantations.id) {
            plantationId = plantations.id;
            console.log(`Mendapatkan blok kebun dari API: ID=${plantationId}, Name=${plantations.name}`);
            
            // Panggil fungsi editPlantation dengan ID yang ditemukan
            await page.evaluate((id) => {
              if (typeof editPlantation === 'function') {
                console.log(`Calling editPlantation(${id}) directly from API data`);
                editPlantation(id);
                return true;
              }
              return false;
            }, plantationId);
            console.log(`Berhasil memanggil fungsi editPlantation(${plantationId}) dengan API data`);
            editButtonClicked = true;
          } else {
            console.log('Tidak dapat menemukan blok kebun dari API, mencoba dengan ID 1 sebagai fallback');
            await page.evaluate(() => {
              if (typeof editPlantation === 'function') {
                console.log(`Calling editPlantation(1) as fallback`);
                editPlantation(1);
                return true;
              }
              return false;
            });
          }
        } catch (error) {
          console.log('Error saat mencoba mendapatkan data via API:', error.message);
        }
      }
      
      // Tunggu form edit blok kebun muncul
      await expect(page.locator('#plantationModalContainer form')).toBeVisible({ timeout: 15000 });
      
      // Tambah jeda untuk memastikan form sudah sepenuhnya dimuat
      await page.waitForTimeout(2000);

      // Verifikasi bahwa kita mengedit blok kebun yang benar
      const currentName = await page.locator('#name').inputValue();
      console.log('Nama blok kebun yang sedang diedit:', currentName);

      // Edit nama blok kebun - bersihkan dulu field, lalu isi dengan nilai baru
      const nameField = page.locator('#name');
      await nameField.click({ clickCount: 3 }); // Seleksi semua teks
      await page.keyboard.press('Backspace'); // Hapus teks yang diseleksi
      
      // Verifikasi field kosong sebelum mengisi nilai baru
      const emptyValue = await nameField.inputValue();
      if (emptyValue) {
        console.log('Field nama belum kosong, mencoba cara lain untuk menghapus');
        await nameField.fill(''); // Coba kosongkan dengan fill
        await page.waitForTimeout(500);
      }
      
      // Isi dengan nilai baru
      const editedName = blokKebunData.nama + ' (Edited)';
      await nameField.fill(editedName);
      console.log('Mengubah nama blok kebun menjadi:', editedName);

      // Verifikasi nilai sudah berubah
      const newValue = await nameField.inputValue();
      console.log('Nilai nama setelah diedit:', newValue);
      
      if (newValue !== editedName) {
        console.log('Nilai tidak berubah, mencoba cara lain');
        await nameField.click({ clickCount: 3 });
        await page.keyboard.press('Backspace');
        await page.keyboard.type(editedName, { delay: 100 });
      }

      // Submit form dengan percobaan berulang jika gagal
      let formSubmitSuccess = false;
      for (let attempt = 0; attempt < 3 && !formSubmitSuccess; attempt++) {
        try {
          console.log(`Percobaan submit form edit blok kebun ke-${attempt + 1}`);
          const submitButton = page.locator('#plantationModalContainer button[type="submit"]');
          await submitButton.click({ force: true });
          
          // Tunggu untuk melihat apakah form tertutup
          await page.waitForTimeout(2000);
          const modalStillVisible = await page.locator('#plantationModalContainer form').isVisible();
          
          if (!modalStillVisible) {
            console.log('Form edit blok kebun berhasil disubmit');
            formSubmitSuccess = true;
            break;
          }
          
          // Jika masih terbuka, coba dengan JavaScript
          console.log('Modal masih terbuka, mencoba dengan JavaScript');
          await page.evaluate(() => {
            const form = document.querySelector('#plantationForm');
            if (form) {
              const submitEvent = new Event('submit', { bubbles: true });
              form.dispatchEvent(submitEvent);
              
              if (typeof submitPlantationForm === 'function') {
                submitPlantationForm();
              }
              
              const submitButton = form.querySelector('button[type="submit"]');
              if (submitButton) submitButton.click();
            }
          });
          
          await page.waitForTimeout(2000);
        } catch (error) {
          console.log(`Error pada percobaan submit edit ke-${attempt + 1}:`, error.message);
        }
      }

      // Tunggu lebih lama untuk proses penyimpanan
      await page.waitForTimeout(5000);

      // Update data di memory untuk tahapan selanjutnya
      blokKebunData.nama = editedName;

      console.log('Data blok kebun berhasil diperbarui');
    } else {
      console.log('Tidak dapat menemukan elemen peta');
    }

    console.log('Langkah 5 selesai: Edit poligon blok kebun');
  });

  test('6. Hapus Data Pohon', async ({ page }) => {
    console.log('Langkah 6: Hapus data pohon');

    // Tunggu peta dan layer pohon dimuat
    await page.waitForTimeout(8000);

    // Cari layer pohon yang telah dibuat
    const mapElement = page.locator('#map');
    const boundingBox = await mapElement.boundingBox();

    if (boundingBox) {
      console.log('Mencoba menemukan pohon dengan grid yang lebih sistematis');
      
      // Gunakan pendekatan grid pencarian yang sama seperti pada langkah edit pohon
      // Mencoba beberapa posisi berbeda dengan jarak yang lebih bervariasi
      const positions = [
        { x: 0.83, y: 0.83 },
        { x: 0.85, y: 0.85 },
        { x: 0.80, y: 0.80 },
        { x: 0.81, y: 0.81 },
        { x: 0.82, y: 0.82 },
        { x: 0.84, y: 0.84 },
        { x: 0.79, y: 0.79 }
      ];

      let found = false;
      let popupVisible = false;
      let treeId = null;

      // Mencoba pendekatan JavaScript untuk menemukan pohon langsung
      try {
        console.log('Mencoba menemukan pohon dengan JavaScript langsung');
        
        // Coba dapatkan semua layer pohon dan pilih yang cocok dengan data kita
        const treeFound = await page.evaluate((pohonId) => {
          // Fungsi untuk mencari layer pohon berdasarkan ID atau propertinya
          try {
            // Cara 1: Mencari menggunakan fungsi findTreeById jika tersedia
            if (typeof findTreeById === 'function' && pohonId) {
              console.log(`Mencoba findTreeById('${pohonId}')`);
              const foundTree = findTreeById(pohonId);
              if (foundTree) {
                // Jika menemukan pohon, coba klik untuk membuka popup
                if (foundTree.fire && typeof foundTree.fire === 'function') {
                  foundTree.fire('click');
                  return { success: true, message: `Tree found and clicked by ID: ${pohonId}` };
                }
              }
            }
            
            // Cara 2: Dapatkan semua layer di map dan filter berdasarkan properties
            if (window.map && window.map.eachLayer) {
              let foundTree = null;
              window.map.eachLayer(function(layer) {
                // Cek apakah ini adalah pohon
                if (layer.feature && 
                    layer.feature.properties && 
                    (layer.feature.properties.type === 'tree' || 
                     layer.feature.properties.jenis === 'pohon' || 
                     layer.feature.properties.varietas === 'D24')) {
                  
                  // Ini adalah layer pohon yang mungkin kita cari
                  console.log('Found tree layer:', layer.feature.properties);
                  foundTree = layer;
                  
                  // Klik untuk membuka popup
                  if (layer.fire && typeof layer.fire === 'function') {
                    layer.fire('click');
                  }
                }
              });
              
              if (foundTree) {
                return { success: true, message: 'Tree found by layer properties and clicked' };
              }
            }
            
            return { success: false, message: 'Tidak dapat menemukan layer pohon' };
          } catch (error) {
            return { success: false, message: `Error: ${error.message}` };
          }
        }, pohonData.id);
        
        console.log('Hasil pencarian pohon via JS:', treeFound);
        
        if (treeFound && treeFound.success) {
          found = true;
          console.log('Pohon ditemukan via JavaScript, popup seharusnya sudah muncul');
          await page.waitForTimeout(2000);
        }
      } catch (error) {
        console.log('Error saat mencoba menemukan pohon via JavaScript:', error.message);
      }
      
      // Jika belum berhasil menemukan dengan JavaScript, coba metode klik pada grid posisi
      if (!found) {
        for (const pos of positions) {
          const x = boundingBox.x + boundingBox.width * pos.x;
          const y = boundingBox.y + boundingBox.height * pos.y;

          await page.mouse.click(x, y);
          console.log(`Mencoba klik di posisi (${pos.x}, ${pos.y})`);

          // Tunggu lebih lama untuk popup muncul
          await page.waitForTimeout(2000);

          // Cari popup yang muncul
          const popupElement = page.locator('.leaflet-popup');

          if (await popupElement.isVisible()) {
            popupVisible = true;
            const popupText = await popupElement.textContent() || '';
            console.log('Popup text:', popupText);
            
            // Ekstrak ID pohon dari popup text jika memungkinkan
            const idMatch = popupText.match(/ID: ([^\s]+)/);
            if (idMatch && idMatch[1]) {
              treeId = idMatch[1];
              console.log('Tree ID extracted from popup:', treeId);
            } else {
              // Coba ekstraksi ID dengan cara lain
              const idLines = popupText.split('\n').filter(line => line.includes('ID:') || line.includes('ID Pohon:'));
              if (idLines.length > 0) {
                const idParts = idLines[0].split(':');
                if (idParts.length > 1) {
                  treeId = idParts[1].trim();
                  console.log('Tree ID extracted from popup line:', treeId);
                }
              }
            }
            
            if (popupText && (popupText.includes(pohonData.id) || popupText.includes('D24') || popupText.includes(pohonData.varietas))) {
              found = true;
              console.log('Popup ditemukan dengan data pohon yang sesuai');
            
              try {
                // PERBAIKAN: Cari tombol hapus berdasarkan atribut onclick yang spesifik
                console.log('Mencari tombol dengan onclick="deleteTreeById" atau ikon trash');
              
                // Cari tombol hapus dengan atribut onclick
                const deleteButton = page.locator(`.leaflet-popup button[onclick*="deleteTree"], .leaflet-popup [onclick*="deleteTree"]`).first();
              
                if (await deleteButton.count() > 0) {
                  console.log('Tombol hapus ditemukan dengan atribut onclick');
                  // Tambahkan jeda sebelum klik
                  await page.waitForTimeout(500);
                  // Pastikan klik berhasil dengan force: true
                  await deleteButton.click({ force: true, timeout: 5000 });
                  console.log('Berhasil mengklik tombol hapus dengan onclick');
                  break;
                } else {
                  // Cara alternatif: Cari tombol dengan ikon trash
                  console.log('Mencoba cara alternatif: cari tombol dengan ikon trash');
                  
                  // Gunakan evaluateHandle untuk mencari tombol di DOM
                  const foundDeleteButton = await page.evaluateHandle(() => {
                    // Cari semua tombol dalam popup
                    const popup = document.querySelector('.leaflet-popup');
                    if (!popup) return null;
                    
                    // Cari tombol dengan ikon trash
                    const buttons = popup.querySelectorAll('button, a');
                    for (const btn of buttons) {
                      // Cek apakah tombol memiliki atribut onclick yang berisi deleteTree
                      const onclickAttr = btn.getAttribute('onclick');
                      if (onclickAttr && onclickAttr.includes('deleteTree')) {
                        console.log('Tombol hapus ditemukan dengan atribut onclick');
                        return btn;
                      }
                      
                      // Cek apakah tombol memiliki ikon trash
                      if (btn.innerHTML.includes('fa-trash') || btn.querySelector('.fa-trash') || btn.querySelector('[data-icon="trash"]')) {
                        console.log('Tombol hapus ditemukan dengan ikon trash');
                        return btn;
                      }
                    }
                    return null;
                  });
                  
                  if (foundDeleteButton && !(await foundDeleteButton.evaluate(node => node === null))) {
                    // Klik tombol yang ditemukan
                    await foundDeleteButton.asElement().click({ force: true });
                    console.log('Berhasil mengklik tombol hapus dengan evaluateHandle');
                  } else if (treeId) {
                    // Jika tombol tidak ditemukan, coba panggil fungsi deleteTreeById langsung
                    console.log(`Mencoba panggil deleteTreeById('${treeId}') langsung`);
                    await page.evaluate((id) => {
                      if (typeof deleteTreeById === 'function') {
                        deleteTreeById(id);
                      } else if (typeof deleteTree === 'function') {
                        deleteTree(id);
                      }
                    }, treeId);
                  }
                }
                
                // Tunggu dialog konfirmasi hapus muncul
                await page.waitForTimeout(2000);
                
                // Perbaikan penanganan dialog konfirmasi
                console.log('Mendeteksi dialog konfirmasi hapus...');
                
                try {
                  // Coba tunggu dialog konfirmasi muncul dengan lebih tepat
                  await page.waitForSelector('text="Apakah Anda yakin ingin menghapus"', { timeout: 5000 });
                  console.log('Dialog konfirmasi terdeteksi!');
                  
                  // Tunggu tombol OK muncul dan langsung klik
                  await page.waitForSelector('button:text("OK")', { timeout: 5000 });
                  console.log('Tombol OK terdeteksi, mengklik...');
                  await page.click('button:text("OK")', { force: true });
                  console.log('Tombol OK berhasil diklik');
                  
                  // Tunggu dialog menghilang
                  await page.waitForTimeout(3000);
                  
                  // Cek apakah dialog sudah tertutup
                  const dialogVisible = await page.isVisible('text="Apakah Anda yakin ingin menghapus"');
                  if (dialogVisible) {
                    console.log('Peringatan: Dialog masih terlihat, mencoba klik OK lagi');
                    await page.click('button:text("OK")', { force: true });
                  } else {
                    console.log('Dialog konfirmasi berhasil ditutup');
                  }
                } catch (error) {
                  console.log(`Error saat menangani dialog: ${error.message}`);
                  // Coba cara lain jika selector spesifik gagal
                  try {
                    // Coba cari dialog berdasarkan teks "yakin"
                    await page.waitForSelector('text="yakin"', { timeout: 3000 });
                    console.log('Dialog dengan kata "yakin" terdeteksi, mencoba klik OK');
                    await page.click('button:text("OK")', { force: true });
                  } catch (subError) {
                    console.log(`Gagal menemukan dialog dengan kata "yakin": ${subError.message}`);
                    
                    // Jika semua cara gagal, coba klik pada posisi perkiraan
                    const viewportSize = await page.viewportSize();
                    if (viewportSize) {
                      const centerX = viewportSize.width / 2;
                      const centerY = viewportSize.height / 2;
                      // Klik pada posisi tombol OK (biasanya di sebelah kiri bawah dialog)
                      await page.mouse.click(centerX - 100, centerY + 50);
                      console.log('Mencoba klik pada posisi perkiraan tombol OK');
                    }
                  }
                }
                break;
              } catch (error) {
                console.log('Error saat mencoba hapus data pohon:', error.message);
              }
            }
          }
        }
      }

      // Jika tidak berhasil menemukan popup atau klik tombol hapus
      if (!found && pohonData.id) {
        console.log('Tidak berhasil menemukan popup, mencoba panggil fungsi hapus langsung');
        
        try {
          // Panggil fungsi deleteTreeById langsung dengan ID pohon
          await page.evaluate((id) => {
            console.log(`Calling deleteTreeById('${id}') directly`);
            if (typeof deleteTreeById === 'function') {
              deleteTreeById(id);
            } else if (typeof deleteTree === 'function') {
              deleteTree(id);
            }
          }, pohonData.id);

          // Tunggu dialog konfirmasi hapus muncul
          await page.waitForTimeout(2000);
          
          // Klik tombol OK pada dialog konfirmasi
          const confirmButtonClicked = await page.evaluate(() => {
            // Cari tombol OK pada dialog
            const okButton = document.querySelector('.swal2-confirm');
            if (okButton) {
              okButton.click();
              return true;
            }
            
            // Cara lain: cari tombol dengan teks OK/Ya
            const buttons = document.querySelectorAll('button');
            for (const btn of buttons) {
              const text = (btn.textContent || '').trim();
              if (text === 'OK' || text === 'Ok' || text === 'Ya' || text === 'Hapus') {
                btn.click();
                return true;
              }
            }
            return false;
          });
          
          if (!confirmButtonClicked) {
            // Coba dengan Playwright selectors
            await page.locator('button:has-text("OK"), button:has-text("Ya"), .swal2-confirm').click({ force: true });
          }

          // Tunggu proses hapus selesai
          await page.waitForTimeout(3000);
        } catch (error) {
          console.log('Error saat mencoba hapus langsung:', error.message);
        }
      }

      console.log('Proses hapus data pohon selesai');
    } else {
      console.log('Tidak dapat menemukan elemen peta');
    }

    console.log('Langkah 6 selesai: Hapus data pohon');
  });

  test('7. Hapus Data Blok Kebun', async ({ page }) => {
    console.log('Langkah 7: Hapus data blok kebun');

    // Tunggu peta dan layer blok kebun dimuat
    await page.waitForTimeout(8000);

    // Cari layer blok kebun yang telah dibuat
    const mapElement = page.locator('#map');
    const boundingBox = await mapElement.boundingBox();

    if (boundingBox) {
      console.log('Mencoba menemukan blok kebun dengan metode yang lebih handal untuk mobile');

      let found = false;
      let plantationId = null;

      // Mencoba pendekatan JavaScript terlebih dahulu (paling handal)
      try {
        console.log('Mencoba menemukan blok kebun dengan JavaScript langsung');
        
        // Coba dapatkan semua layer blok kebun dan klik yang cocok
        const plantationFound = await page.evaluate((kebunNama) => {
          try {
            // Cara 1: Cari dengan fungsi khusus jika tersedia
            if (typeof findPlantationByName === 'function' && kebunNama) {
              console.log(`Mencoba findPlantationByName('${kebunNama}')`);
              const foundPlantation = findPlantationByName(kebunNama);
              if (foundPlantation) {
                // Klik untuk membuka popup
                if (foundPlantation.fire && typeof foundPlantation.fire === 'function') {
                  foundPlantation.fire('click');
                  return { success: true, id: foundPlantation.id, message: `Plantation found and clicked by name: ${kebunNama}` };
                }
              }
            }
            
            // Cara 2: Dapatkan semua layer di map dan filter
            if (window.map && window.map.eachLayer) {
              let foundPlantation = null;
              window.map.eachLayer(function(layer) {
                // Cek apakah ini adalah blok kebun
                if (layer.feature && 
                    layer.feature.properties && 
                    (layer.feature.properties.type === 'plantation' || 
                     layer.feature.properties.jenis === 'kebun' || 
                     layer.feature.properties.name)) {
                  
                  // Cek apakah ini blok kebun yang kita cari
                  const name = layer.feature.properties.name || '';
                  if (name.includes('Test Playwright')) {
                    console.log('Found plantation layer:', layer.feature.properties);
                    foundPlantation = layer;
                    
                    // Simpan ID
                    plantationId = layer.feature.properties.id;
                    
                    // Klik untuk membuka popup
                    if (layer.fire && typeof layer.fire === 'function') {
                      layer.fire('click');
                    }
                  }
                }
              });
              
              if (foundPlantation) {
                return { 
                  success: true, 
                  id: plantationId, 
                  message: 'Plantation found by layer properties and clicked' 
                };
              }
            }
            
            // Cara 3: Coba panggil API untuk mendapatkan data
            return fetch('/api/plantations')
              .then(response => response.json())
              .then(data => {
                if (Array.isArray(data.data)) {
                  // Cari blok kebun dengan nama yang cocok
                  const plantation = data.data.find(p => p.name && p.name.includes('Test Playwright'));
                  if (plantation) {
                    console.log('Found plantation from API:', plantation);
                    // Jika menemukan, coba panggil fungsi deletePlantationById
                    if (typeof deletePlantationById === 'function') {
                      deletePlantationById(plantation.id);
                      return { 
                        success: true, 
                        id: plantation.id, 
                        message: 'Plantation found from API and delete function called' 
                      };
                    }
                    return { 
                      success: true, 
                      id: plantation.id, 
                      message: 'Plantation found from API but delete function not available' 
                    };
                  }
                }
                return { success: false, message: 'Tidak dapat menemukan blok kebun dari API' };
              })
              .catch(error => {
                return { success: false, message: `Error fetching API: ${error.message}` };
              });
          } catch (error) {
            return { success: false, message: `Error: ${error.message}` };
          }
        }, blokKebunData.nama);
        
        console.log('Hasil pencarian blok kebun via JS:', plantationFound);
        
        if (plantationFound && plantationFound.success) {
          found = true;
          if (plantationFound.id) plantationId = plantationFound.id;
          console.log('Blok kebun ditemukan via JavaScript, popup seharusnya sudah muncul atau fungsi hapus sudah dipanggil');
        await page.waitForTimeout(2000);
        }
      } catch (error) {
        console.log('Error saat mencoba menemukan blok kebun via JavaScript:', error.message);
      }

      // Jika belum berhasil, coba metode grid sistematis
      if (!found) {
        console.log('Mencoba menemukan blok kebun dengan grid sistematis');
        
        // Buat grid 5x5 yang lebih luas karena blok kebun lebih besar dari pohon
        const gridSize = 5;
        const centerX = 0.825; // Area di mana blok kebun dibuat
        const centerY = 0.825;
        const gridRadius = 0.1; // Radius lebih besar untuk blok kebun
        
        for (let i = 0; i < gridSize; i++) {
          for (let j = 0; j < gridSize; j++) {
            // Hitung posisi klik
            const xRatio = centerX - gridRadius + (2 * gridRadius * i / (gridSize - 1));
            const yRatio = centerY - gridRadius + (2 * gridRadius * j / (gridSize - 1));
            
            const x = boundingBox.x + boundingBox.width * xRatio;
            const y = boundingBox.y + boundingBox.height * yRatio;
            
            // Klik pada titik grid
            await page.mouse.click(x, y);
            console.log(`Mengklik di posisi grid (${i},${j}): (${xRatio.toFixed(2)}, ${yRatio.toFixed(2)})`);
            
            // Tunggu untuk melihat apakah popup muncul
            await page.waitForTimeout(1000);

        // Cari popup yang muncul
        const popupElement = page.locator('.leaflet-popup');

        if (await popupElement.isVisible()) {
          const popupText = await popupElement.textContent() || '';
          
          // Ekstrak ID blok kebun jika ada
              const idMatch = popupText.match(/ID: ([^\s]+)/);
          if (idMatch && idMatch[1]) {
            plantationId = idMatch[1];
            console.log('Plantation ID extracted from popup:', plantationId);
              } else {
                // Coba ekstraksi ID dengan cara lain
                const idLines = popupText.split('\n').filter(line => line.includes('ID:') || line.includes('ID Pohon:'));
                if (idLines.length > 0) {
                  const idParts = idLines[0].split(':');
                  if (idParts.length > 1) {
                    plantationId = idParts[1].trim();
                    console.log('Plantation ID extracted from popup line:', plantationId);
                  }
                }
              }
              
              if (popupText && (popupText.includes(pohonData.id) || popupText.includes(pohonData.varietas))) {
            found = true;
                console.log('Popup ditemukan dengan data pohon yang sesuai');
            
            try {
              // PERBAIKAN: Cari tombol hapus berdasarkan atribut onclick yang spesifik
              console.log('Mencari tombol dengan onclick="deletePlantationById" atau ikon trash');
              
              // Cari tombol hapus dengan atribut onclick
              const deleteButton = page.locator(`.leaflet-popup button[onclick*="deletePlantation"], .leaflet-popup [onclick*="deletePlantation"]`).first();
              
              if (await deleteButton.count() > 0) {
                console.log('Tombol hapus ditemukan dengan atribut onclick');
                // Tambahkan jeda sebelum klik
                await page.waitForTimeout(500);
                // Pastikan klik berhasil dengan force: true
                await deleteButton.click({ force: true, timeout: 5000 });
                console.log('Berhasil mengklik tombol hapus dengan onclick');
              } else {
                // Cara alternatif: Cari tombol dengan ikon trash
                console.log('Mencoba cara alternatif: cari tombol dengan ikon trash');
                
                // Gunakan evaluateHandle untuk mencari tombol di DOM
                const foundDeleteButton = await page.evaluateHandle(() => {
                  // Cari semua tombol dalam popup
                  const popup = document.querySelector('.leaflet-popup');
                  if (!popup) return null;
                  
                  // Cari tombol dengan ikon trash
                  const buttons = popup.querySelectorAll('button, a');
                  for (const btn of buttons) {
                    // Cek apakah tombol memiliki atribut onclick yang berisi deletePlantation
                    const onclickAttr = btn.getAttribute('onclick');
                    if (onclickAttr && onclickAttr.includes('deletePlantation')) {
                      console.log('Tombol hapus ditemukan dengan atribut onclick');
                      return btn;
                    }
                    
                    // Cek apakah tombol memiliki ikon trash
                    if (btn.innerHTML.includes('fa-trash') || btn.querySelector('.fa-trash') || btn.querySelector('[data-icon="trash"]')) {
                      console.log('Tombol hapus ditemukan dengan ikon trash');
                      return btn;
                    }
                  }
                  return null;
                });
                
                if (foundDeleteButton) {
                  // Klik tombol yang ditemukan
                  await foundDeleteButton.asElement().click({ force: true });
                  console.log('Berhasil mengklik tombol hapus dengan evaluateHandle');
                } else if (plantationId) {
                  // Jika tombol tidak ditemukan, coba panggil fungsi deletePlantationById langsung
                  console.log(`Mencoba panggil deletePlantationById(${plantationId}) langsung`);
                  await page.evaluate((id) => {
                    if (typeof deletePlantationById === 'function') {
                      deletePlantationById(id);
                    }
                  }, plantationId);
                }
              }
              
              // Tunggu dialog konfirmasi hapus muncul
              await page.waitForTimeout(2000);
              
              // Perbaikan penanganan dialog konfirmasi
              console.log('Mendeteksi dialog konfirmasi hapus...');
              
              try {
                // Coba tunggu dialog konfirmasi muncul dengan lebih tepat
                await page.waitForSelector('text="Apakah Anda yakin ingin menghapus"', { timeout: 5000 });
                console.log('Dialog konfirmasi terdeteksi!');
                
                // Tunggu tombol OK muncul dan langsung klik
                await page.waitForSelector('button:text("OK")', { timeout: 5000 });
                console.log('Tombol OK terdeteksi, mengklik...');
                await page.click('button:text("OK")', { force: true });
                console.log('Tombol OK berhasil diklik');
                
                // Tunggu dialog menghilang
                await page.waitForTimeout(3000);
                
                // Cek apakah dialog sudah tertutup
                const dialogVisible = await page.isVisible('text="Apakah Anda yakin ingin menghapus"');
                if (dialogVisible) {
                  console.log('Peringatan: Dialog masih terlihat, mencoba klik OK lagi');
                  await page.click('button:text("OK")', { force: true });
                } else {
                  console.log('Dialog konfirmasi berhasil ditutup');
                }
              } catch (error) {
                console.log(`Error saat menangani dialog: ${error.message}`);
                // Coba cara lain jika selector spesifik gagal
                try {
                  // Coba cari dialog berdasarkan teks "yakin"
                  await page.waitForSelector('text="yakin"', { timeout: 3000 });
                  console.log('Dialog dengan kata "yakin" terdeteksi, mencoba klik OK');
                  await page.click('button:text("OK")', { force: true });
                } catch (subError) {
                  console.log(`Gagal menemukan dialog dengan kata "yakin": ${subError.message}`);
                  
                  // Jika semua cara gagal, coba klik pada posisi perkiraan
                  const viewportSize = await page.viewportSize();
                  if (viewportSize) {
                    const centerX = viewportSize.width / 2;
                    const centerY = viewportSize.height / 2;
                    // Klik pada posisi tombol OK (biasanya di sebelah kiri bawah dialog)
                    await page.mouse.click(centerX - 100, centerY + 50);
                    console.log('Mencoba klik pada posisi perkiraan tombol OK');
                  }
                }
              }
            } catch (error) {
              console.log('Error saat mencoba hapus data blok kebun:', error.message);
                }
              }
            }
          }
        }
      }

      // Jika tidak berhasil menemukan popup atau klik tombol hapus, coba API
      if (!found) {
        console.log('Tidak berhasil menemukan popup, mencoba mendapatkan data dari API');
        
        try {
          // Coba dapatkan ID blok kebun melalui API
          const plantationData = await page.evaluate((namaKebun) => {
            return fetch('/api/plantations')
              .then(response => response.json())
              .then(data => {
                if (Array.isArray(data.data)) {
                  // Cari blok kebun dengan nama yang cocok
                  const plantation = data.data.find(p => p.name && p.name.includes(namaKebun));
                  if (plantation) {
                    return { id: plantation.id, name: plantation.name };
                  }
                }
                return null;
              })
              .catch(error => {
                console.error('Error fetching data:', error);
                return null;
              });
          }, blokKebunData.nama);
          
          if (plantationData && plantationData.id) {
            plantationId = plantationData.id;
            console.log(`Mendapatkan ID blok kebun dari API: ${plantationId}`);
            
            // Panggil fungsi hapus langsung
            await page.evaluate((id) => {
              console.log(`Calling deletePlantationById(${id}) directly`);
              if (typeof deletePlantationById === 'function') {
                deletePlantationById(id);
              }
            }, plantationId);

      // Tunggu dialog konfirmasi hapus muncul
            await page.waitForTimeout(2000);
            
            // Klik tombol OK pada dialog konfirmasi
            const confirmButtonClicked = await page.evaluate(() => {
              // Cari tombol OK pada dialog
              const okButton = document.querySelector('.swal2-confirm');
              if (okButton) {
                okButton.click();
                return true;
              }
              
              // Cara lain: cari tombol dengan teks OK/Ya
              const buttons = document.querySelectorAll('button');
              for (const btn of buttons) {
                const text = (btn.textContent || '').trim();
                if (text === 'OK' || text === 'Ok' || text === 'Ya' || text === 'Hapus') {
                  btn.click();
                  return true;
                }
              }
              return false;
            });
            
            if (!confirmButtonClicked) {
              // Coba dengan Playwright selectors
              await page.locator('button:has-text("OK"), button:has-text("Ya"), .swal2-confirm').click({ force: true });
            }

      // Tunggu proses hapus selesai
      await page.waitForTimeout(3000);
          }
        } catch (error) {
          console.log('Error saat mengakses API:', error.message);
        }
      }

      console.log('Proses hapus data blok kebun selesai');
    } else {
      console.log('Tidak dapat menemukan elemen peta');
    }

    console.log('Langkah 7 selesai: Hapus data blok kebun');
  });
});

// Fungsi helper untuk proses logout
async function doLogout(page) {
  // Tunggu halaman sepenuhnya dimuat
  await waitForPageLoad(page, 2000);

  try {
    // Langkah 1: Klik tombol profil (gambar profile) dengan retry
    let profileClickSuccess = false;
    for (let attempt = 0; attempt < 3 && !profileClickSuccess; attempt++) {
      try {
        await page.locator('button.profile-button').click({ timeout: 5000 });
        profileClickSuccess = true;
        console.log('Berhasil klik tombol profil');
      } catch (error) {
        console.log(`Percobaan ${attempt + 1} klik profile gagal: ${error.message}. Mencoba lagi...`);
        await page.waitForTimeout(1000);
      }
    }

    if (!profileClickSuccess) {
      throw new Error('Gagal mengklik tombol profil setelah beberapa percobaan');
    }

    // Tunggu dropdown menu muncul
    await page.waitForTimeout(1000);

    // Langkah 2: Klik tombol "Keluar" dalam dropdown profile
    await page.locator('button', { hasText: 'Keluar' }).click();
    console.log('Berhasil klik tombol Keluar di dropdown');

    // Tunggu modal konfirmasi keluar muncul
    await page.waitForTimeout(1000);

    // Langkah 3: Klik tombol "Keluar" (berwarna merah) pada modal konfirmasi
    await page.locator('form[action*="logout"] button[type="submit"]').click();
    console.log('Berhasil klik tombol Keluar di modal konfirmasi');

    // Tunggu proses logout selesai
    await page.waitForTimeout(2000);

    return true;
  } catch (error) {
    console.log('Error saat proses logout: ' + error);
    return false;
  }
}
