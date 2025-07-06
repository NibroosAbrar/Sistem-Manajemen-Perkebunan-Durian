# Test info

- Name: Manajemen Pengguna >> Login sebagai superadmin, hapus akun, dan logout
- Location: C:\laragon\www\laravel11\e2e\akun.spec.js:163:7

# Error details

```
TimeoutError: locator.click: Timeout 60000ms exceeded.
Call log:
  - waiting for locator('button[type="submit"]')
    - locator resolved to <button type="submit" class="btn-login w-full text-white py-3 px-4 rounded-lg font-medium text-base focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">↵                Masuk↵            </button>
  - attempting click action
    - waiting for element to be visible, enabled and stable
    - element is visible, enabled and stable
    - scrolling into view if needed
    - done scrolling
    - performing click action

    at C:\laragon\www\laravel11\e2e\akun.spec.js:170:49
```

# Page snapshot

```yaml
- heading "Welcome to Symadu" [level=1]
- paragraph: Masuk ke akun Anda untuk melanjutkan
- text: Username atau Email*
- img
- textbox "Username atau Email*": superadmin@symadu.com
- text: Kata Sandi*
- img
- textbox "Kata Sandi*": superadmin123
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
  158 |     // Logout
  159 |     await doLogout(page);
  160 |     await page.waitForTimeout(2000);
  161 |   });
  162 |
  163 |   test('Login sebagai superadmin, hapus akun, dan logout', async ({ page }) => {
  164 |     // Login sebagai superadmin
  165 |     await page.goto('/login');
  166 |     await waitForPageLoad(page);
  167 |
  168 |     await page.locator('input[name="login"], #login').fill('superadmin@symadu.com');
  169 |     await page.locator('input[type="password"], #password').fill('superadmin123');
> 170 |     await page.locator('button[type="submit"]').click();
      |                                                 ^ TimeoutError: locator.click: Timeout 60000ms exceeded.
  171 |
  172 |     // Verifikasi berhasil login
  173 |     await expect(page).toHaveURL(/dashboard/, { timeout: 10000 });
  174 |     await page.waitForTimeout(2000);
  175 |
  176 |     // Tunggu sampai halaman sepenuhnya dimuat
  177 |     await waitForPageLoad(page);
  178 |
  179 |     // Perbaikan untuk tombol hamburger
  180 |     try {
  181 |       await page.waitForSelector('button.hamburger-button, button.sidebar-toggle, button[aria-label="Toggle Sidebar"]', { timeout: 5000 });
  182 |       await page.click('button.hamburger-button, button.sidebar-toggle, button[aria-label="Toggle Sidebar"]');
  183 |     } catch (error) {
  184 |       console.log('Tombol hamburger tidak ditemukan dengan selector biasa, mencoba alternatif:', error);
  185 |       // Coba pendekatan alternatif jika selector pertama gagal
  186 |       await page.waitForTimeout(1000);
  187 |       await page.evaluate(() => {
  188 |         // Cari elemen hamburger berdasarkan tampilan atau posisinya
  189 |         const buttons = document.querySelectorAll('button');
  190 |         for (const button of buttons) {
  191 |           // Cek jika button terletak di pojok kiri atas atau memiliki ikon hamburger
  192 |           if (button.innerHTML.includes('svg') &&
  193 |              (button.getBoundingClientRect().left < 100 && button.getBoundingClientRect().top < 100)) {
  194 |             button.click();
  195 |             return;
  196 |           }
  197 |         }
  198 |       });
  199 |     }
  200 |     await page.waitForTimeout(2000);
  201 |
  202 |     // Klik menu Manajemen Pengguna - gunakan pendekatan yang lebih fleksibel
  203 |     const linkSelectors = [
  204 |       'a:has-text("Manajemen Pengguna")',
  205 |       'a:has-text("Akun")',
  206 |       'a:has-text("Pengguna")',
  207 |       '.sidebar a:has-text("Pengguna")'
  208 |     ];
  209 |
  210 |     for (const selector of linkSelectors) {
  211 |       try {
  212 |         if (await page.locator(selector).count() > 0) {
  213 |           await page.locator(selector).click();
  214 |           break;
  215 |         }
  216 |       } catch (error) {
  217 |         console.log(`Selector ${selector} tidak ditemukan, mencoba selanjutnya`);
  218 |       }
  219 |     }
  220 |
  221 |     await waitForPageLoad(page);
  222 |
  223 |     // Cari akun yang ingin dihapus - menggunakan nama lengkap bukan email
  224 |     await page.locator('input[name="search"]').fill(testUserName);
  225 |     await page.waitForTimeout(1000);
  226 |     await page.locator('button[type="submit"]:has-text("Filter")').click();
  227 |     await waitForPageLoad(page);
  228 |
  229 |     // Scroll untuk melihat hasil pencarian
  230 |     await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.5));
  231 |     await page.waitForTimeout(1000);
  232 |
  233 |     try {
  234 |       // Verifikasi akun ditemukan berdasarkan nama lengkap
  235 |       await expect(page.locator('table tbody tr')).toContainText(testUserName);
  236 |
  237 |       // Hapus akun
  238 |       // Temukan baris dengan nama pengguna yang ingin dihapus
  239 |       const userRow = page.locator(`table tbody tr:has-text("${testUserName}")`);
  240 |
  241 |       // Scroll ke elemen tersebut
  242 |       await userRow.scrollIntoViewIfNeeded();
  243 |       await page.waitForTimeout(1000);
  244 |
  245 |       // Klik tombol Hapus (sesuai dengan struktur di akun.blade.php)
  246 |       await userRow.locator('button:has-text("Hapus")').click();
  247 |       await page.waitForTimeout(2000);
  248 |
  249 |       // Konfirmasi penghapusan pada modal dengan mengklik tombol Hapus
  250 |       // Sesuai dengan akun.blade.php, tombol Hapus adalah tombol terakhir di dalam modal
  251 |       await page.locator('div[x-show="showDeleteModal"] button:has-text("Hapus")').click();
  252 |       await waitForPageLoad(page);
  253 |
  254 |       // Verifikasi akun sudah dihapus (tidak ditemukan setelah pencarian)
  255 |       await page.locator('input[name="search"]').clear();
  256 |       await page.waitForTimeout(500);
  257 |       await page.locator('input[name="search"]').fill(testUserName);
  258 |       await page.waitForTimeout(1000);
  259 |       await page.locator('button[type="submit"]:has-text("Filter")').click();
  260 |       await waitForPageLoad(page);
  261 |
  262 |       // Scroll untuk melihat hasil
  263 |       await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.5));
  264 |       await page.waitForTimeout(1000);
  265 |
  266 |       // Seharusnya tidak ada baris yang mengandung nama pengguna yang dihapus
  267 |       const rows = await page.locator('table tbody tr').count();
  268 |       if (rows > 0) {
  269 |         const hasName = await page.locator(`table tbody tr:has-text("${testUserName}")`).count() > 0;
  270 |         expect(hasName).toBe(false);
```