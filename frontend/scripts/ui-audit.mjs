// UI audit: every storefront screen on a phone and on a desktop.
//   node scripts/ui-audit.mjs <out-dir> [base-url]
// For each route it saves the first screen (what the visitor sees without
// scrolling) and a full-page shot, and reports:
//   overflow  — the page scrolls sideways
//   clipped   — text cut by its own box (overflow hidden, or an ellipsis) or by
//               an ancestor that hides overflow
//   outside   — a visible element poking past the viewport's sides
//   tall      — a heading whose ink (harakat included) is taller than its box,
//               so marks above the letters may be cut
//   fold      — phone: how far down the page the main action sits
import { chromium, devices } from '@playwright/test'
import { mkdirSync, writeFileSync } from 'node:fs'

const out = process.argv[2] || 'audit'
const base = process.argv[3] || 'http://localhost:5391'
mkdirSync(out, { recursive: true })

const ROUTES = [
  ['home', '/'], ['category', '/c/men'], ['product', '/p/victory-elixir'], ['discovery', '/discovery'], ['gifts', '/gifts'],
  ['finder', '/finder'], ['search', '/search?q=عود'], ['cart', '/cart'], ['checkout', '/checkout'], ['track', '/track'],
  ['login', '/login'], ['register', '/register'], ['about', '/about'], ['faq', '/help/faq'], ['notfound', '/nope'],
]
const DEVICES = [['phone', devices['iPhone 13']], ['desk', { viewport: { width: 1440, height: 900 } }]]

const browser = await chromium.launch({ channel: 'chrome' })
const report = []
for (const [dev, opts] of DEVICES) {
  const ctx = await browser.newContext({ ...opts, locale: 'ar-EG', reducedMotion: 'reduce' })
  const page = await ctx.newPage()
  // a perfume in the cart, so the cart and checkout have content
  await page.goto(`${base}/p/victory-elixir`)
  await page.getByRole('button', { name: 'أضف إلى السلة' }).first().click().catch(() => {})
  await page.waitForTimeout(800)
  for (const [name, path] of ROUTES) {
    await page.goto(base + path)
    await page.waitForLoadState('networkidle').catch(() => {})
    await page.waitForTimeout(900)
    await page.keyboard.press('Escape').catch(() => {})
    await page.screenshot({ path: `${out}/${dev}-${name}-fold.png` })
    await page.screenshot({ path: `${out}/${dev}-${name}-full.png`, fullPage: true })
    const found = await page.evaluate((isPhone) => {
      const vw = innerWidth, vh = innerHeight
      const visible = (el) => { const s = getComputedStyle(el); const r = el.getBoundingClientRect(); return s.visibility !== 'hidden' && s.display !== 'none' && +s.opacity > 0.05 && r.width > 2 && r.height > 2 && !el.closest('[aria-hidden="true"],.sr-only,[hidden]') }
      const label = (el) => `${el.tagName.toLowerCase()}${el.className && typeof el.className === 'string' ? '.' + el.className.split(/\s+/).filter(Boolean).slice(0, 2).join('.') : ''} «${(el.innerText || el.getAttribute('aria-label') || '').trim().slice(0, 40)}»`
      const issues = { overflow: document.documentElement.scrollWidth - vw, clipped: [], outside: [], tall: [], fold: null }
      const texty = [...document.querySelectorAll('h1,h2,h3,h4,p,a,button,label,span,b,small,li,dd,dt')].filter(visible)
      for (const el of texty) {
        if (!el.innerText?.trim() || el.children.length > 3) continue
        const s = getComputedStyle(el)
        const hides = (st) => /hidden|clip/.test(st.overflowX) || /hidden|clip/.test(st.overflowY)
        const ellipsis = s.textOverflow === 'ellipsis' && el.scrollWidth > el.clientWidth + 1
        const own = hides(s) && (el.scrollWidth > el.clientWidth + 2 || el.scrollHeight > el.clientHeight + 4) && !s.webkitLineClamp?.match(/\d/)
        if (ellipsis || own) issues.clipped.push(label(el) + (ellipsis ? ' (ellipsis)' : ''))
        // cut by an ancestor that hides overflow
        const r = el.getBoundingClientRect()
        for (let a = el.parentElement, i = 0; a && i < 6; a = a.parentElement, i++) {
          const as = getComputedStyle(a)
          if (!hides(as)) continue
          const ar = a.getBoundingClientRect()
          if (ar.height < 8) break
          if (r.left < ar.left - 2 || r.right > ar.right + 2 || r.top < ar.top - 2 || r.bottom > ar.bottom + 2) {
            // a scroller (rails, sliders) is meant to hold more than it shows
            if (/auto|scroll/.test(as.overflowX) || /auto|scroll/.test(as.overflowY)) break
            issues.clipped.push(label(el) + ' (by ' + label(a).slice(0, 40) + ')')
          }
          break
        }
        if (r.right > vw + 1 || r.left < -1) {
          if (![...document.querySelectorAll('*')].some((x) => x !== el && x.contains(el) && /auto|scroll/.test(getComputedStyle(x).overflowX))) issues.outside.push(label(el))
        }
      }
      // headings whose drawn ink is taller than the line box (harakat at risk)
      for (const h of [...document.querySelectorAll('h1,h2,h3')].filter(visible)) {
        const s = getComputedStyle(h)
        const fs = parseFloat(s.fontSize), lh = parseFloat(s.lineHeight) || fs * 1.2
        if (lh / fs < 1.12 && /Amiri/.test(s.fontFamily)) issues.tall.push(`${label(h)} line-height ${(lh / fs).toFixed(2)}`)
      }
      if (isPhone) {
        const cta = [...document.querySelectorAll('main button, main a.btn-pill, main .btn-pill')].filter(visible)[0]
        issues.fold = cta ? `${label(cta)} at ${Math.round(cta.getBoundingClientRect().top + scrollY)}px of ${vh}px screen; page ${document.documentElement.scrollHeight}px` : 'no action'
      }
      issues.clipped = [...new Set(issues.clipped)].slice(0, 12)
      issues.outside = [...new Set(issues.outside)].slice(0, 12)
      return issues
    }, dev === 'phone')
    report.push({ dev, name, ...found })
    const flags = [found.overflow > 0 && `overflow ${found.overflow}px`, found.clipped.length && `clipped ${found.clipped.length}`, found.outside.length && `outside ${found.outside.length}`, found.tall.length && `tall ${found.tall.length}`].filter(Boolean)
    console.log(`${dev.padEnd(5)} ${name.padEnd(10)} ${flags.join(' · ') || 'ok'}${found.fold ? '  | ' + found.fold : ''}`)
  }
  await ctx.close()
}
writeFileSync(`${out}/report.json`, JSON.stringify(report, null, 2))
await browser.close()
