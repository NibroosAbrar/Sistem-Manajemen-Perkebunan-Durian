# Test info

- Name: Manajemen Pengguna >> Registrasi, login sebagai superadmin, edit role, dan logout
- Location: C:\laragon\www\laravel11\e2e\akun.spec.js:34:7

# Error details

```
TimeoutError: locator.click: Timeout 60000ms exceeded.
Call log:
  - waiting for locator('button[type="submit"]')
    - locator resolved to <button type="submit" class="btn-login w-full text-white py-3 px-4 rounded-lg font-medium text-base focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mt-2">↵            Buat Akun↵        </button>
  - attempting click action
    - waiting for element to be visible, enabled and stable
    - element is visible, enabled and stable
    - scrolling into view if needed
    - done scrolling
    - performing click action
    - click action done
    - waiting for scheduled navigations to finish

    at C:\laragon\www\laravel11\e2e\akun.spec.js:57:49
```

# Page snapshot

```yaml
- heading "Welcome to Symadu" [level=1]
- paragraph: Masuk ke akun Anda untuk melanjutkan
- text: Username atau Email*
- img
- textbox "Username atau Email*": guest
- text: Kata Sandi*
- img
- textbox "Kata Sandi*": guest123
- button
- checkbox "Ingat saya"
- text: Ingat saya
- link "Lupa kata sandi?":
  - /url: http://localhost:8000/forgot-password
- button "Masuk"
- paragraph:
  - text: Belum punya akun?
  - link "Buat Akun":
    - /url: http://localhost:8000/register
```

# Test source

```ts
   1 | // @ts-check
   2 | import { test, expect } from '@playwright/test';
   3 |
   4 | // Generate nama dan email unik untuk user baru
   5 | const timestamp = new Date().getTime();
   6 | const testUserName = `Test User ${timestamp}`;
   7 | const testUserEmail = `testuser${timestamp}@example.com`;
   8 | const testUserUsername = `testuser${timestamp}`;
   9 | const testUserPassword = 'password123';
   10 |
   11 | // Fungsi untuk menunggu halaman dimuat dengan cara yang lebih kompatibel dengan Firefox
   12 | async function waitForPageLoad(page, additionalWaitTime = 2000) {
   13 |   try {
   14 |     // Tunggu minimal untuk DOM terlebih dahulu (lebih stabil daripada networkidle)
   15 |     await page.waitForLoadState('domcontentloaded');
   16 |     
   17 |     // Tunggu sebentar untuk memastikan skrip JS sudah berjalan
   18 |     await page.waitForTimeout(additionalWaitTime);
   19 |     
   20 |     // Coba tunggu networkidle dengan timeout lebih pendek, jika gagal tidak masalah
   21 |     try {
   22 |       await page.waitForLoadState('networkidle', { timeout: 5000 });
   23 |     } catch (e) {
   24 |       // Jika networkidle timeout, tidak masalah, lanjutkan saja
   25 |       console.log('Networkidle timeout, melanjutkan test');
   26 |     }
   27 |   } catch (e) {
   28 |     console.log('Error saat menunggu halaman dimuat:', e);
   29 |     // Tetap lanjutkan dengan test
   30 |   }
   31 | }
   32 |
   33 | test.describe('Manajemen Pengguna', () => {
   34 |   test('Registrasi, login sebagai superadmin, edit role, dan logout', async ({ page }) => {
   35 |     // Langkah 1: Registrasi akun baru
   36 |     await page.goto('/register');
   37 |     await waitForPageLoad(page);
   38 |
   39 |     // Isi form registrasi
   40 |     await page.locator('input[name="name"]').fill(testUserName);
   41 |
   42 |     // Cek apakah field username ada dan isi jika ada
   43 |     const usernameField = page.locator('input[name="username"]');
   44 |     if (await usernameField.count() > 0) {
   45 |       await usernameField.fill(testUserUsername);
   46 |     }
   47 |
   48 |     await page.locator('input[name="email"]').fill(testUserEmail);
   49 |     await page.locator('input[name="password"]').fill(testUserPassword);
   50 |     await page.locator('input[name="password_confirmation"]').fill(testUserPassword);
   51 |
   52 |     // Scroll ke bawah untuk memastikan tombol submit terlihat
   53 |     await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
   54 |     await page.waitForTimeout(1000);
   55 |
   56 |     // Submit form registrasi
>  57 |     await page.locator('button[type="submit"]').click();
      |                                                 ^ TimeoutError: locator.click: Timeout 60000ms exceeded.
   58 |
   59 |     // Tunggu registrasi selesai (akan diarahkan ke halaman login)
   60 |     await waitForPageLoad(page);
   61 |
   62 |     // Verifikasi bahwa kita berada di halaman login
   63 |     await expect(page).toHaveURL(/login/, { timeout: 10000 });
   64 |
   65 |     // Langkah 2: Login sebagai superadmin
   66 |     await page.locator('input[name="login"], #login').fill('superadmin@symadu.com');
   67 |     await page.locator('input[type="password"], #password').fill('superadmin123');
   68 |     await page.locator('button[type="submit"]').click();
   69 |
   70 |     // Verifikasi berhasil login
   71 |     await expect(page).toHaveURL(/dashboard/, { timeout: 10000 });
   72 |     await page.waitForTimeout(2000);
   73 |
   74 |     // Tunggu sampai halaman sepenuhnya dimuat
   75 |     await waitForPageLoad(page);
   76 |
   77 |     // Perbaikan untuk tombol hamburger - gunakan selector yang lebih spesifik dan tunggu sampai tombol muncul
   78 |     try {
   79 |       await page.waitForSelector('button.hamburger-button, button.sidebar-toggle, button[aria-label="Toggle Sidebar"]', { timeout: 5000 });
   80 |       await page.click('button.hamburger-button, button.sidebar-toggle, button[aria-label="Toggle Sidebar"]');
   81 |     } catch (error) {
   82 |       console.log('Tombol hamburger tidak ditemukan dengan selector biasa, mencoba alternatif:', error);
   83 |       // Coba pendekatan alternatif jika selector pertama gagal
   84 |       await page.waitForTimeout(1000);
   85 |       await page.evaluate(() => {
   86 |         // Cari elemen hamburger berdasarkan tampilan atau posisinya
   87 |         const buttons = document.querySelectorAll('button');
   88 |         for (const button of buttons) {
   89 |           // Cek jika button terletak di pojok kiri atas atau memiliki ikon hamburger
   90 |           if (button.innerHTML.includes('svg') &&
   91 |              (button.getBoundingClientRect().left < 100 && button.getBoundingClientRect().top < 100)) {
   92 |             button.click();
   93 |             return;
   94 |           }
   95 |         }
   96 |       });
   97 |     }
   98 |     await page.waitForTimeout(2000);
   99 |
  100 |     // Klik menu Manajemen Pengguna - gunakan pendekatan yang lebih fleksibel
  101 |     const linkSelectors = [
  102 |       'a:has-text("Manajemen Pengguna")',
  103 |       'a:has-text("Akun")',
  104 |       'a:has-text("Pengguna")',
  105 |       '.sidebar a:has-text("Pengguna")'
  106 |     ];
  107 |
  108 |     for (const selector of linkSelectors) {
  109 |       try {
  110 |         if (await page.locator(selector).count() > 0) {
  111 |           await page.locator(selector).click();
  112 |           break;
  113 |         }
  114 |       } catch (error) {
  115 |         console.log(`Selector ${selector} tidak ditemukan, mencoba selanjutnya`);
  116 |       }
  117 |     }
  118 |
  119 |     await waitForPageLoad(page);
  120 |
  121 |     // Cari akun yang baru dibuat - menggunakan nama lengkap bukan email
  122 |     await page.locator('input[name="search"]').fill(testUserName);
  123 |     await page.waitForTimeout(1000);
  124 |     await page.locator('button[type="submit"]:has-text("Filter")').click();
  125 |     await waitForPageLoad(page);
  126 |
  127 |     // Scroll untuk melihat hasil pencarian
  128 |     await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.5));
  129 |     await page.waitForTimeout(1000);
  130 |
  131 |     // Verifikasi akun ditemukan menggunakan nama lengkap
  132 |     await expect(page.locator('table tbody tr')).toContainText(testUserName);
  133 |
  134 |     // Edit role akun - pilih Operasional
  135 |     // Temukan baris dengan nama pengguna yang baru dibuat
  136 |     const userRow = page.locator(`table tbody tr:has-text("${testUserName}")`);
  137 |
  138 |     // Scroll ke elemen tersebut
  139 |     await userRow.scrollIntoViewIfNeeded();
  140 |     await page.waitForTimeout(1000);
  141 |
  142 |     // Pilih role baru (Operasional)
  143 |     await userRow.locator('select[name="role_id"]').selectOption({ label: 'Operasional' });
  144 |     await page.waitForTimeout(1000);
  145 |
  146 |     // Klik tombol edit di samping dropdown
  147 |     await userRow.locator('button[type="button"]').first().click();
  148 |     await page.waitForTimeout(2000);
  149 |
  150 |     // Konfirmasi perubahan pada modal dengan mengklik tombol Simpan
  151 |     await page.locator('button:has-text("Simpan")').click();
  152 |     await waitForPageLoad(page);
  153 |
  154 |     // Scrolling kembali ke atas sebelum logout
  155 |     await page.evaluate(() => window.scrollTo(0, 0));
  156 |     await page.waitForTimeout(1000);
  157 |
```