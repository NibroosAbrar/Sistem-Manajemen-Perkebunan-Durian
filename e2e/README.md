# Pengujian End-to-End (E2E) untuk WebGIS Laravel 11

Panduan ini menjelaskan cara menjalankan pengujian E2E untuk memastikan kompatibilitas website WebGIS Laravel 11 di berbagai browser dan perangkat.

## Instalasi Playwright

1. **Pastikan Node.js sudah terpasang** pada sistem Anda. Jika belum, unduh dari [nodejs.org](https://nodejs.org/)

2. **Instal Playwright di folder proyek**:
   ```
   cd c:\laragon\www\laravel11
   npm init playwright@latest
   ```
   Selama instalasi, pilih:
   - TypeScript: `No`
   - Folder pengujian: `e2e`
   - GitHub Actions: `No`
   - Instal browser: `Yes`

3. **Atau instal dengan npm secara manual**:
   ```
   npm install @playwright/test
   npx playwright install
   ```

4. **Sesuaikan PowerShell Policy** (untuk Windows):
   ```
   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
   ```
   atau dengan hak administrator:
   ```
   Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
   ```

## Persiapan Pengujian

1. **Pastikan server Laravel berjalan SEBELUM memulai pengujian**:
   ```
   php artisan serve
   ```

2. **Bersihkan cache Laravel** jika perlu:
   ```
   php artisan route:clear
   php artisan config:clear 
   php artisan cache:clear
   ```

## Rekomendasi Pengujian

Untuk hasil terbaik, ikuti langkah-langkah berikut:

1. **Mulai dengan pengujian satu browser terlebih dahulu**:
   ```bash
   npx playwright test --project=chromium
   ```
   atau di Windows jika menemui kendala:
   ```bash
   node node_modules\@playwright\test\cli.js test --project=chromium
   ```

2. **Lakukan pengujian file per file** sampai stabilitas tercapai:
   ```bash
   npx playwright test auth.spec.js
   ```
   atau di Windows:
   ```bash
   node node_modules\@playwright\test\cli.js test auth.spec.js
   ```

3. **Jika semua pengujian sudah stabil**, aktifkan browser tambahan di `playwright.config.js` dengan menghapus komentar pada konfigurasi browser lainnya.

## Menjalankan Pengujian

### Pengujian di Browser Tertentu

```bash
# Chrome (mode headless)
npx playwright test --project=chromium

# Chrome (dengan UI yang terlihat)
npx playwright test --project=chromium --headed
```

### Menjalankan UI Mode (Interactive)

```bash
npx playwright test --ui
```
atau di Windows:
```bash
node node_modules\@playwright\test\cli.js test --ui
```

## Melihat Laporan Pengujian

Setelah pengujian selesai, Anda dapat melihat laporan dengan perintah:

```bash
npx playwright show-report
```
atau di Windows:
```bash
node node_modules\@playwright\test\cli.js show-report
```

## Mengatasi Masalah Login & Timeout

Jika pengujian gagal karena error selama login atau timeout, periksa hal berikut:

1. **Pastikan server Laravel berjalan** sebelum menjalankan test
2. **Periksa struktur HTML form login** dengan UI Mode:
   ```bash
   npx playwright test auth.spec.js --ui
   ```
   
3. **Sesuaikan credentials** di file pengujian dengan akun yang valid:
   - Default: admin@example.com / password
   - Edit di `auth.spec.js` dan `webgis.spec.js`

4. **Jika mengalami timeout**, coba tingkatkan nilai timeout di `playwright.config.js`:
   ```javascript
   // Contoh:
   navigationTimeout: 60000,  // 60 detik
   actionTimeout: 30000,      // 30 detik 
   ```

## Skenario Pengujian

1. **Autentikasi**: Login, logout, dan reset password
2. **WebGIS**: Navigasi peta, zoom, layer, dan informasi pohon
3. **Pengelolaan**: CRUD data pengelolaan
4. **Dashboard**: Akses halaman dan navigasi
5. **Responsivitas**: Tampilan di berbagai perangkat (desktop, tablet, mobile)
6. **Data Pohon dan Shapefile**: Digitasi, upload, dan ekspor data

## Tips Pengujian

- Sesuaikan kredensial login di file pengujian dengan akun yang valid di sistem Anda
- Pastikan data awal tersedia untuk pengujian (seperti pohon, pengelolaan, dll.)
- Jika pengujian gagal, periksa screenshot dan video yang dihasilkan di folder `playwright-report`
- Cek elemen form login di website Anda, pastikan menggunakan id dan name yang benar sesuai HTML
- Untuk Windows, jika mengalami masalah izin, coba jalankan PowerShell atau Command Prompt sebagai administrator
