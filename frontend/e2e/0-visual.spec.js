import { expect, test } from '@playwright/test'

// TST-19: pixel baselines for the main pages, desktop and phone. Refresh after an
// intended change with
//   npx playwright test e2e/0-visual.spec.js --update-snapshots
// The file name sorts first so it runs on the freshly seeded catalog, before
// flows.spec adds and features a test perfume.
// Baselines live next to this file (0-visual.spec.js-snapshots/).
const PAGES = [
  ['home', '/'],
  ['category', '/c/women'],
  ['product', '/p/black-oud-velvet'],
  ['discovery', '/discovery'],
  ['gifts', '/gifts'],
  ['finder', '/finder'],
  ['cart-empty', '/cart'],
  ['login', '/login'],
  ['not-found', '/no-such-page'],
]

// before the page loads, so animated parts render in their final, resting state
test.beforeEach(async ({ page }) => page.emulateMedia({ reducedMotion: 'reduce' }))

async function settle(page) {
  await page.waitForLoadState('networkidle')
  // no skeletons left: the first run after a reseed meets a cold cache
  await expect(page.locator('[aria-busy="true"]')).toHaveCount(0, { timeout: 15_000 })
  await page.evaluate(() => document.fonts.ready)
  // lazy images (the colour cards' bottles) load eagerly for the shot, and are decoded
  await page.evaluate(async () => {
    const imgs = [...document.images]
    imgs.forEach((i) => (i.loading = 'eager'))
    await Promise.all(imgs.map((i) => (i.complete && i.naturalWidth ? null : new Promise((r) => { i.addEventListener('load', r, { once: true }); i.addEventListener('error', r, { once: true }); setTimeout(r, 8000) }))))
    await Promise.all(imgs.map((i) => i.decode().catch(() => {})))
  })
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(300)
}

for (const [name, path] of PAGES) {
  test(`${name} looks as approved`, async ({ page }) => {
    test.setTimeout(60_000)
    await page.goto(path)
    await settle(page)
    await expect(page).toHaveScreenshot(`${name}.png`, {
      fullPage: true,
      animations: 'disabled',
      maxDiffPixelRatio: 0.01,
      timeout: 30_000,
      mask: [page.locator('[data-visual-mask]')],
    })
  })

  test(`@mobile ${name} looks as approved on a phone`, async ({ page }) => {
    test.setTimeout(60_000)
    await page.goto(path)
    await settle(page)
    await expect(page).toHaveScreenshot(`${name}-phone.png`, { fullPage: true, animations: 'disabled', maxDiffPixelRatio: 0.01, timeout: 30_000 })
  })
}
