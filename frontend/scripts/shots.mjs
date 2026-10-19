// Review captures for the finish pass: node scripts/shots.mjs [url] [outDir]
import { chromium } from '@playwright/test'
import { mkdirSync } from 'node:fs'

const url = process.argv[2] || 'http://localhost:5391/'
const out = process.argv[3] || '../.impeccable/review'
mkdirSync(out, { recursive: true })

const browser = await chromium.launch({ channel: 'chrome' })
const shots = [
  { name: 'desktop', viewport: { width: 1440, height: 900 } },
  { name: 'mobile', viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 },
]
for (const s of shots) {
  const page = await browser.newPage({ viewport: s.viewport, isMobile: s.isMobile, hasTouch: s.hasTouch, deviceScaleFactor: s.deviceScaleFactor || 1 })
  const errors = []
  page.on('pageerror', (e) => errors.push(e.message))
  page.on('console', (m) => m.type() === 'error' && errors.push(m.text()))
  await page.goto(url, { waitUntil: 'networkidle' })
  await page.evaluate(() => document.fonts.ready)
  await page.screenshot({ path: `${out}/${s.name}-fold.png` })
  // scroll through so the pen line draws, then capture the whole page
  const h = await page.evaluate(() => document.documentElement.scrollHeight)
  for (let y = 0; y <= h; y += 600) {
    await page.evaluate((yy) => window.scrollTo(0, yy), y)
    await page.waitForTimeout(40)
  }
  await page.waitForTimeout(400)
  await page.evaluate(() => window.scrollTo(0, 0))
  await page.waitForTimeout(200)
  await page.screenshot({ path: `${out}/${s.name}.png`, fullPage: true })
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
  console.log(s.name, 'height', h, 'h-overflow', overflow, 'errors', JSON.stringify(errors))
  await page.close()
}
await browser.close()
