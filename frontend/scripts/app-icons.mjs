// Home-screen icons for "add to home screen" (the phone opens the store as an app):
// the gold mark on Midad Black, from the official logo data.
//   node scripts/app-icons.mjs
import { chromium } from '@playwright/test'
import { readFileSync, writeFileSync } from 'node:fs'

const logo = JSON.parse(readFileSync('src/assets/brand/midad-logo.json', 'utf8'))
const [x, y, w, h] = logo.viewBox.split(' ').map(Number)
// maskable icons keep the mark inside the central 80% safe zone; plain ones fill more
const svg = (scale) => {
  const side = Math.max(w, h) / scale
  const cx = x + w / 2
  const cy = y + h / 2
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${cx - side / 2} ${cy - side / 2} ${side} ${side}"><rect x="${cx - side / 2}" y="${cy - side / 2}" width="${side}" height="${side}" fill="#0E0D0C"/><path fill="#D6B77E" d="${logo.ink}"/><path fill="#D6B77E" d="${logo.gold}"/></svg>`
}
const out = [
  ['public/icons/icon-192.png', 192, 0.78],
  ['public/icons/icon-512.png', 512, 0.78],
  ['public/icons/maskable-512.png', 512, 0.6],
  ['public/icons/apple-touch-icon.png', 180, 0.72],
]
const browser = await chromium.launch({ channel: 'chrome' })
const page = await browser.newPage()
for (const [file, size, scale] of out) {
  await page.setViewportSize({ width: size, height: size })
  await page.setContent(`<body style="margin:0">${svg(scale).replace('<svg ', `<svg width="${size}" height="${size}" `)}</body>`)
  writeFileSync(file, await page.screenshot({ type: 'png' }))
  console.log(file)
}
await browser.close()
