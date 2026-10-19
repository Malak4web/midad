// Default link-preview image (BE-56): node scripts/og-image.mjs → public/og-default.png
import { chromium } from '@playwright/test'
import { readFileSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { pathToFileURL } from 'node:url'

const logo = readFileSync('../assets/brand/midad-logo.svg', 'utf8')
const font = (f) => pathToFileURL(`node_modules/@fontsource/${f}`).href
const html = `<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><style>
@font-face{font-family:Amiri;src:url(${font('amiri/files/amiri-arabic-700-normal.woff2')});font-weight:700}
@font-face{font-family:Amiri;src:url(${font('amiri/files/amiri-arabic-400-normal.woff2')});font-weight:400}
@font-face{font-family:Montserrat;src:url(${font('montserrat/files/montserrat-latin-400-normal.woff2')})}
@font-face{font-family:Cinzel;src:url(${font('cinzel/files/cinzel-latin-400-normal.woff2')})}
html,body{margin:0;width:1200px;height:630px;background:#F2EADB;overflow:hidden}
/* the guide's horizontal lockup (p.7): symbol, then مداد / MIDAD / PARFUMS; the tagline
   sits outside the lockup's clear space; the ink wave stays more than x below the circle */
.lockup{position:absolute;top:70px;right:120px;display:flex;align-items:center;gap:48px}
.logo{height:380px;flex:none}.logo svg{height:100%;width:auto}
.words{display:flex;flex-direction:column;align-items:flex-start}
h1{font:700 128px/1 Amiri;color:#0E0D0C;margin:0}
.lat{font:400 30px Cinzel;letter-spacing:.3em;color:#0E0D0C;margin-top:18px;direction:ltr}
.parf{font:400 13px Montserrat;letter-spacing:.5em;color:#3B342C;margin-top:8px;direction:ltr}
.tag{position:absolute;left:120px;top:250px;font:400 46px/1.4 Amiri;color:#3B342C;margin:0}
.wave{position:absolute;inset:auto 0 0 0;height:84px}
</style></head><body>
<div class="lockup"><div class="logo">${logo}</div><div class="words"><h1>مداد</h1><div class="lat">MIDAD</div><div class="parf">PARFUMS</div></div></div>
<p class="tag">عطرٌ يُكتَب، فيبقى</p>
<svg class="wave" viewBox="0 0 1200 84" preserveAspectRatio="none"><path d="M0 46 C 300 14, 600 80, 900 40 S 1200 26, 1200 26 V84 H0Z" fill="#0E0D0C"/><path d="M0 46 C 300 14, 600 80, 900 40 S 1200 26, 1200 26" fill="none" stroke="#B08D57" stroke-width="3"/></svg>
</body></html>`
const browser = await chromium.launch({ channel: 'chrome' })
const page = await browser.newPage({ viewport: { width: 1200, height: 630 } })
// fonts load only from a file:// page, not from setContent's about:blank
const tmp = join(tmpdir(), 'midad-og.html')
writeFileSync(tmp, html)
await page.goto(pathToFileURL(tmp).href, { waitUntil: 'load' })
await page.evaluate(() => document.fonts.ready)
await page.screenshot({ path: 'public/og-default.png' })
await browser.close()
console.log('public/og-default.png')
