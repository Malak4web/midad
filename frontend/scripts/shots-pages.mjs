// Batched review captures of the inner pages: node scripts/shots-pages.mjs
import { chromium } from '@playwright/test'
import { mkdirSync } from 'node:fs'

const base = 'http://localhost:5391'
const out = '../.impeccable/review/pages'
mkdirSync(out, { recursive: true })
const browser = await chromium.launch({ channel: 'chrome' })
const shots = [
  ['category', '/c/women'],
  ['product', '/p/black-oud-velvet'],
  ['discovery', '/discovery'],
  ['gifts', '/gifts'],
  ['cart', '/cart'],
  ['checkout', '/checkout'],
  ['search', '/search?q=عود'],
  ['login', '/login'],
  ['faq', '/help/faq'],
]
for (const [vw, vh, tag, mobile] of [[1366, 860, 'd', false], [390, 844, 'm', true]]) {
  const ctx = await browser.newContext({ viewport: { width: vw, height: vh }, isMobile: mobile, hasTouch: mobile, deviceScaleFactor: mobile ? 2 : 1 })
  const page = await ctx.newPage()
  const errors = []
  page.on('pageerror', (e) => errors.push(e.message))
  // put something in the cart first so cart/checkout show their real state
  await page.goto(base + '/p/her-blossom', { waitUntil: 'networkidle' })
  await page.getByRole('button', { name: 'أضف إلى السلة' }).click()
  await page.waitForTimeout(800)
  for (const [name, path] of shots) {
    await page.goto(base + path, { waitUntil: 'networkidle' })
    await page.waitForTimeout(300)
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)
    await page.screenshot({ path: `${out}/${tag}-${name}.png`, fullPage: true })
    if (overflow > 0) errors.push(`${name}: h-overflow ${overflow}`)
  }
  console.log(tag, 'errors', JSON.stringify(errors))
  await ctx.close()
}
await browser.close()
