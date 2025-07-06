# Symadu - Sistem Manajemen Perkebunan Durian
<div align="center">
  <img src="https://github.com/user-attachments/assets/f7166bbb-4160-4957-b831-f286e2d24267" width="900"/>
</div>


## 📋 Tentang Symadu

Symadu (Sistem Manajemen Perkebunan Durian) adalah sebuah aplikasi web yang dirancang untuk membantu pengelolaan dan manajemen perkebunan durian secara efisien dan terintegrasi. Aplikasi ini dikembangkan menggunakan framework Laravel dengan berbagai fitur modern untuk memudahkan pengguna dalam mengelola perkebunan durian.

## 🌟 Fitur Utama

### 1. Dashboard Kebun
- Monitoring total pohon durian
- Informasi luas area perkebunan
- Tracking produksi buah durian
- Statistik pertumbuhan year-over-year
- Status musim dan kondisi cuaca

### 2. Peta Digital (WebGIS)
- Visualisasi peta perkebunan
- Penanda lokasi pohon durian
- Informasi detail setiap blok kebun
- Manajemen area tanam

### 3. Kegiatan Pengelolaan
- Pencatatan aktivitas perawatan
- Manajemen jadwal kegiatan
- Status progress kegiatan
- Riwayat kegiatan perkebunan

### 4. Manajemen Stok
- Pencatatan hasil panen
- Monitoring stok durian
- Tracking distribusi hasil panen

### 5. Manajemen Pengguna
- Multi-level user access (Superadmin, Admin, Operasional, Guest)
- Manajemen profil pengguna
- Sistem autentikasi aman

## 💻 Teknologi yang Digunakan

- **Framework:** Laravel
- **Frontend:** Blade Template, HTML, CSS, JavaScript
- **Maps:** Leaflet.js
- **Database:** PostgreSQL (PostGIS)
- **Authentication:** Laravel Authentication
- **CSS Framework:** Tailwind CSS
- **Icons:** Font Awesome

## 🚀 Instalasi

1. Clone repository
```bash
git clone https://github.com/NibroosAbrar/Sistem-Manajemen-Perkebunan-Durian.git
```

2. Install dependencies
```bash
composer install
npm install
```

3. Copy file .env.example
```bash
cp .env.example .env
```

4. Generate application key
```bash
php artisan key:generate
```

5. Setup database di file .env
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=symadu_db
DB_USERNAME=root
DB_PASSWORD=
```

6. Jalankan migration dan seeder
```bash
php artisan migrate --seed
```

7. Jalankan aplikasi
```bash
php artisan serve
npm run dev
```

## 👥 Role Pengguna

1. **Superadmin**
   - Akses penuh ke semua fitur
   - Manajemen user dan role
   - Konfigurasi sistem

2. **Manajer**
   - Manajemen data perkebunan
   - Akses ke semua fitur operasional
   - Monitoring dan reporting

3. **Operasional**
   - Input data kegiatan
   - Update status pekerjaan
   - Akses ke fitur operasional terbatas

4. **Guest**
   - Melihat informasi umum
   - Akses terbatas ke data publik

## 📝 Lisensi

© 2025 Muhammad Nibroos Abrar. All rights reserved.

## 🤝 Kontribusi

Kontribusi dan saran untuk pengembangan Symadu sangat diterima. Silakan buat pull request atau laporkan issues jika menemukan bug atau memiliki ide untuk pengembangan.

## 📧 Kontak

Untuk informasi lebih lanjut, silakan hubungi:
- Email: nibroos@example.com
- Website: https://symadu.example.com

---
Dibuat dengan oleh Muhammad Nibroos Abrar
