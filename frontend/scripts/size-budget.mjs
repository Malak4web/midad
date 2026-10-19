// FE-51 / TST-24: fail the build when the storefront grows past its budget.
// node scripts/size-budget.mjs  (after `vite build`)
import { readFileSync, readdirSync } from 'node:fs'
import { gzipSync } from 'node:zlib'

// fonts: the brand families as shipped (a page loads ~380 KB of them, swap, the
// hero weight preloaded); the cap stops a new family or weight sneaking in
const BUDGET = { entryJs: 95, entryCss: 20, routeJs: 12, fonts: 490 } // KB, gzip for js/css
const dir = 'dist/assets'
const files = readdirSync(dir)
const gz = (f) => gzipSync(readFileSync(`${dir}/${f}`)).length / 1024
const raw = (f) => readFileSync(`${dir}/${f}`).length / 1024

const html = readFileSync('dist/index.html', 'utf8')
const entry = html.match(/assets\/(index-[^"]+\.js)/)[1]
const css = html.match(/assets\/(index-[^"]+\.css)/)[1]
const routes = files.filter((f) => f.endsWith('.js') && f !== entry && !f.startsWith('Admin'))
const fonts = files.filter((f) => f.endsWith('.woff2'))

const rows = [
  ['entry js (gzip)', gz(entry), BUDGET.entryJs],
  ['entry css (gzip)', gz(css), BUDGET.entryCss],
  ['largest storefront route (gzip)', Math.max(...routes.map(gz)), BUDGET.routeJs],
  [`fonts (${fonts.length} files)`, fonts.reduce((n, f) => n + raw(f), 0), BUDGET.fonts],
]
let fail = false
for (const [name, size, max] of rows) {
  const ok = size <= max
  fail ||= !ok
  console.log(`${ok ? 'ok  ' : 'OVER'} ${name.padEnd(34)} ${size.toFixed(1).padStart(6)} KB / ${max} KB`)
}
process.exit(fail ? 1 : 0)
