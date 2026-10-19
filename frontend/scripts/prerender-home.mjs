// FE-51: snapshot the home page's first screen (announcement bar, header, hero)
// from the built app, so "/" paints its headline from HTML instead of waiting for
// JS. SeoController puts it inside #app; Vue's mount replaces it with identical DOM.
// node scripts/prerender-home.mjs  (after `vite build`)
import { chromium } from '@playwright/test'
import { spawn } from 'node:child_process'
import { writeFileSync } from 'node:fs'

const port = 5393
const preview = spawn(process.execPath, ['node_modules/vite/bin/vite.js', 'preview', '--port', String(port), '--strictPort'], { stdio: 'ignore' })
try {
  const browser = await chromium.launch({ channel: 'chrome' })
  const page = await browser.newPage({ viewport: { width: 1366, height: 900 } })
  for (let i = 0; ; i++) {
    try {
      await page.goto(`http://localhost:${port}/`, { waitUntil: 'domcontentloaded' })
      break
    } catch (e) {
      if (i > 40) throw e
      await new Promise((r) => setTimeout(r, 250))
    }
  }
  await page.waitForSelector('[data-first-paint] h1')
  const html = await page.evaluate(() => {
    const app = document.querySelector('#app').cloneNode(true)
    const main = app.querySelector('main')
    const hero = main.querySelector('[data-first-paint]')
    // keep the hero, drop every later section
    let band = hero
    while (band.parentElement !== main && band.parentElement) band = band.parentElement
    ;[...main.children].forEach((c) => c !== band && c.remove())
    ;[...app.children].forEach((c) => {
      const keep = c === main || c.compareDocumentPosition(main) & Node.DOCUMENT_POSITION_FOLLOWING
      if (!keep) c.remove()
    })
    // nothing interactive should look live before Vue: no stray focus targets in the snapshot
    app.querySelectorAll('[aria-expanded="true"]').forEach((n) => n.setAttribute('aria-expanded', 'false'))
    return app.innerHTML.replace(/<!--[\s\S]*?-->/g, '')
  })
  writeFileSync('dist/home-first-paint.html', html)
  console.log(`home first paint: ${(html.length / 1024).toFixed(1)} KB`)
  await browser.close()
} finally {
  preview.kill()
}
