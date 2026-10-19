// Confirms the homepage renders from the live API (INT-04).
import { chromium } from '@playwright/test'
const browser = await chromium.launch({ channel: 'chrome' })
const page = await browser.newPage()
const hits = []
page.on('response', (r) => r.url().includes('/api/') && hits.push(`${r.status()} ${new URL(r.url()).pathname}`))
const errors = []
page.on('pageerror', (e) => errors.push(e.message))
await page.goto('http://localhost:5391/', { waitUntil: 'networkidle' })
const h = await page.$$eval('main h3', (els) => els.slice(0, 3).map((e) => e.textContent.trim()))
console.log({ hits, featured: h, errors })
await browser.close()
