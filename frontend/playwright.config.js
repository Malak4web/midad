import { defineConfig, devices } from '@playwright/test'

// E2E against the running dev stack: Vite on 5391 (proxying /api to Laravel on 8391).
// Start both servers first; global-setup reseeds the database.
export default defineConfig({
  testDir: './e2e',
  timeout: 45_000,
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list']],
  globalSetup: './e2e/global-setup.js',
  use: {
    baseURL: process.env.E2E_BASE_URL || 'http://localhost:5391',
    locale: 'ar-EG',
    trace: 'retain-on-failure',
  },
  projects: [
    // TST-19 runs first, on the freshly seeded catalog: the flows add and feature a
    // test perfume, which would change these pages. Baselines keep their old names.
    { name: 'visual-desktop', use: { ...devices['Desktop Chrome'], channel: 'chrome', viewport: { width: 1366, height: 860 } }, testMatch: /0-visual.spec/, grepInvert: /@mobile/, snapshotPathTemplate: '{testDir}/{testFilePath}-snapshots/{arg}-desktop-{platform}{ext}' },
    { name: 'visual-mobile', use: { ...devices['Pixel 7'], channel: 'chrome' }, testMatch: /0-visual.spec/, grep: /@mobile/, snapshotPathTemplate: '{testDir}/{testFilePath}-snapshots/{arg}-mobile-{platform}{ext}' },
    { name: 'desktop', use: { ...devices['Desktop Chrome'], channel: 'chrome', viewport: { width: 1366, height: 860 } }, testIgnore: /0-visual.spec/, grepInvert: /@mobile/, dependencies: ['visual-desktop', 'visual-mobile'] },
    { name: 'mobile', use: { ...devices['Pixel 7'], channel: 'chrome' }, testIgnore: /0-visual.spec/, grep: /@mobile/, dependencies: ['visual-desktop', 'visual-mobile'] },
    // TST-20: the core purchase flows on the other engines (npx playwright test --project=firefox ...)
    { name: 'firefox', use: { ...devices['Desktop Firefox'], viewport: { width: 1366, height: 860 } }, testMatch: /shop\.spec/, grepInvert: /@mobile/ },
    { name: 'webkit', use: { ...devices['Desktop Safari'], viewport: { width: 1366, height: 860 } }, testMatch: /shop\.spec/, grepInvert: /@mobile/ },
    { name: 'iphone', use: { ...devices['iPhone 14'] }, testMatch: /shop\.spec/, grep: /@mobile/ },
  ],
})
