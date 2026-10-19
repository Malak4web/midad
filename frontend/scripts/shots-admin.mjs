// Batched review captures of the admin pages: node scripts/shots-admin.mjs
import { chromium } from '@playwright/test'
import { mkdirSync } from 'node:fs'

const base = 'http://localhost:5391'
const out = '../.impeccable/review/admin'
mkdirSync(out, { recursive: true })
const browser = await chromium.launch({ channel: 'chrome' })
const shots = [
  ['dashboard', '/admin'],
  ['orders', '/admin/orders'],
  ['products', '/admin/products'],
  ['product-new', '/admin/products/new'],
  ['stock', '/admin/stock'],
  ['spotlights', '/admin/spotlights'],
  ['coupons', '/admin/coupons'],
  ['customers', '/admin/customers'],
  ['shipping', '/admin/shipping'],
  ['settings', '/admin/settings'],
  ['reports', '/admin/reports'],
]
for (const [vw, vh, tag, mobile] of [[1366, 860, 'd', false], [390, 844, 'm', true]]) {
  const ctx = await browser.newContext({ viewport: { width: vw, height: vh }, isMobile: mobile, hasTouch: mobile, deviceScaleFactor: mobile ? 2 : 1 })
  const page = await ctx.newPage()
  const errors = []
  page.on('pageerror', (e) => errors.push(e.message))
  page.on('response', (r) => r.url().includes('/api/') && r.status() >= 400 && errors.push(`${r.status()} ${r.url()}`))
  await page.goto(base + '/login', { waitUntil: 'networkidle' })
  await page.getByLabel(/البريد/).fill('admin@midad.local')
  await page.getByLabel(/كلمة المرور/).fill('Midad-Dev-2026')
  await page.getByRole('button', { name: /دخول/ }).click()
  await page.waitForTimeout(1200)
  for (const [name, path] of shots) {
    await page.goto(base + path, { waitUntil: 'networkidle' })
    await page.waitForTimeout(400)
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)
    await page.screenshot({ path: `${out}/${tag}-${name}.png`, fullPage: true })
    if (overflow > 0) errors.push(`${name}: h-overflow ${overflow}`)
  }
  // open the first order detail
  await page.goto(base + '/admin/orders', { waitUntil: 'networkidle' })
  const link = page.locator('a[href*="/admin/orders/"]').first()
  if (await link.count()) {
    await link.click()
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(400)
    await page.screenshot({ path: `${out}/${tag}-order.png`, fullPage: true })
  }
  console.log(tag, 'errors', JSON.stringify(errors))
  await ctx.close()
}
await browser.close()
