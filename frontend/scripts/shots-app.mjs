// Phone review captures of the app shell: node scripts/shots-app.mjs
import { chromium } from '@playwright/test'
import { mkdirSync } from 'node:fs'

const base = 'http://localhost:5391'
const out = '../.impeccable/review/app'
mkdirSync(out, { recursive: true })
const browser = await chromium.launch({ channel: 'chrome' })
const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 })
const page = await ctx.newPage()
const errors = []
page.on('pageerror', (e) => errors.push(e.message))
const shot = (name) => page.screenshot({ path: `${out}/${name}.png` })

await page.goto(base + '/', { waitUntil: 'networkidle' })
await shot('01-home')
await page.evaluate(() => scrollTo(0, 1900)); await page.waitForTimeout(700)
await shot('02-home-scrolled')
await page.getByRole('button', { name: 'تسوّق' }).click(); await page.waitForTimeout(600)
await shot('03-shop-sheet')
await page.getByRole('dialog').getByRole('link', { name: /نسائي/ }).click(); await page.waitForLoadState('networkidle'); await page.waitForTimeout(500)
await shot('04-category')
await page.goto(base + '/p/her-blossom', { waitUntil: 'networkidle' })
await shot('05-product')
await page.evaluate(() => scrollTo(0, 1500)); await page.waitForTimeout(700)
await shot('06-product-buybar')
await page.locator('.buybar').getByRole('button', { name: /أضف/ }).click(); await page.waitForTimeout(900)
await shot('07-added-toast')
await page.getByRole('button', { name: /^السلة،/ }).click(); await page.waitForTimeout(700)
await shot('08-cart-sheet')
await page.keyboard.press('Escape'); await page.waitForTimeout(400)
await page.getByRole('button', { name: 'بحث' }).click(); await page.waitForTimeout(500)
await page.keyboard.type('عود'); await page.waitForTimeout(900)
await shot('09-search')
await page.goto(base + '/checkout', { waitUntil: 'networkidle' })
await shot('10-checkout')
await page.goto(base + '/login', { waitUntil: 'networkidle' })
await shot('11-login')
console.log('errors', JSON.stringify(errors))
await browser.close()
