// Traces the official Midad logo PNG into two vector layers (ink + gold) so the
// approved colorways can be rendered at any size. Run once when the source changes:
//   node scripts/trace-logo.mjs ../assets/brand
import potrace from 'potrace'
import { promisify } from 'node:util'
import { readFileSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'

const dir = process.argv[2] || '../assets/brand'
const trace = promisify(potrace.trace)
const opts = (turdSize) => ({ threshold: 128, turdSize, optTolerance: 0.25, color: '#000', background: 'transparent' })

const ink = await trace(readFileSync(join(dir, 'mask-ink.png')), opts(8))
const gold = await trace(readFileSync(join(dir, 'mask-gold.png')), opts(1))
const pathOf = (svg) => [...svg.matchAll(/ d="([^"]+)"/g)].map((m) => m[1]).join(' ')
const inkD = pathOf(ink)
const goldD = pathOf(gold)

// bounding box over all coordinates (potrace emits absolute M/L/C commands)
const nums = (d) => [...d.matchAll(/-?\d+(?:\.\d+)?/g)].map(Number)
const all = [...nums(inkD), ...nums(goldD)]
let [x0, y0, x1, y1] = [Infinity, Infinity, -Infinity, -Infinity]
for (let i = 0; i + 1 < all.length; i += 2) {
  x0 = Math.min(x0, all[i]); x1 = Math.max(x1, all[i])
  y0 = Math.min(y0, all[i + 1]); y1 = Math.max(y1, all[i + 1])
}
const pad = 6
const box = { x: Math.floor(x0 - pad), y: Math.floor(y0 - pad), w: Math.ceil(x1 - x0 + pad * 2), h: Math.ceil(y1 - y0 + pad * 2) }

const data = { viewBox: `${box.x} ${box.y} ${box.w} ${box.h}`, width: box.w, height: box.h, ink: inkD, gold: goldD }
writeFileSync(join(dir, 'midad-logo.json'), JSON.stringify(data))
writeFileSync(
  join(dir, 'midad-logo.svg'),
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${data.viewBox}"><path fill="#0E0D0C" d="${inkD}"/><path fill="#B08D57" d="${goldD}"/></svg>\n`,
)
console.log('viewBox', data.viewBox, 'ink chars', inkD.length, 'gold chars', goldD.length)
