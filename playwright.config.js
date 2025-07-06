// @ts-check
import { defineConfig, devices } from '@playwright/test';

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// import dotenv from 'dotenv';
// import path from 'path';
// dotenv.config({ path: path.resolve(__dirname, '.env') });

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './e2e',
  /* Run tests in files in parallel */
  fullyParallel: false, // Menonaktifkan paralel untuk memastikan urutan
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 1,
  /* Opt out of parallel tests on CI. */
  workers: 1, // Memastikan hanya ada 1 worker agar urutan terjaga
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['json', { outputFile: 'test-results/test-results.json' }],
    ['list']
  ],
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: 'http://localhost:8000',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',

    /* Capture screenshot on failure */
    screenshot: 'only-on-failure',

    /* Record video on failure */
    video: 'on-first-retry',

    /* Meningkatkan timeout untuk navigasi halaman */
    navigationTimeout: 90000,

    /* Menambahkan timeout untuk action */
    actionTimeout: 60000,

    /* Menambahkan timeout untuk expect */
    expect: {
      timeout: 60000,
    },

    /* Mengurangi slowMo untuk mempercepat pengujian */
    launchOptions: {
      slowMo: 100,
    },
  },

  /* Tentukan urutan file test yang akan dieksekusi */
  testMatch: [
    'e2e/auth.spec.js',
    'e2e/dashboard.spec.js',
    'e2e/webgis.spec.js',
    'e2e/pengelolaan.spec.js',
    'e2e/stok-simple.spec.js',
    'e2e/akun.spec.js'
  ],

  /* Configure projects for major browsers */
  projects: [
    // Desktop Browser
    {
      name: 'desktop-chromium',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1366, height: 768 },
        // Selalu merekam video untuk desktop-chromium
        video: 'on',
        // Menyimpan video di folder khusus
        recordVideo: {
          dir: 'test-results/videos/desktop-chromium',
          size: { width: 1366, height: 768 }
        }
      },
    },
    {
      name: 'desktop-firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'desktop-webkit',
      use: { ...devices['Desktop Safari'] },
    },

    // Tablet iOS
    {
      name: 'tablet-ios',
      use: {
        ...devices['iPad (gen 7)'],
      },
    },

    // Tablet Android
    {
      name: 'tablet-android',
      use: {
        ...devices['Galaxy Tab S4'],
      },
    },

    // Mobile Android
    {
      name: 'mobile-android',
      use: { ...devices['Pixel 5'] },
    },

    // Mobile iOS
    {
      name: 'mobile-ios',
      use: { ...devices['iPhone 12'] },
    },
  ],

  /* Run your local dev server before starting the tests */
  webServer: {
    command: 'php artisan serve',
    url: 'http://localhost:8000',
    reuseExistingServer: true, // Selalu gunakan server yang sudah berjalan
    timeout: 30000, // Mengurangi timeout menjadi 30 detik
    stderr: 'pipe',
    stdout: 'pipe',
  },

  /* Kurangi global timeout */
  globalTimeout: 60000000, // 10 menit

  /* Kurangi timeout per test */
  timeout: 12000000, // 2 menit
});


