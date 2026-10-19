// The guide (p.5) draws the spray as crisp square dots; tracing turned them into
// blobs. This rebuilds the gold layer from the source artwork: every spray dot becomes
// an exact square at its measured centre, size and angle, and the kasra is drawn from
// measured points. The ink layer is left as traced (it overlays the source cleanly).
//   node scripts/rebuild-spray.mjs ../assets/brand
import { spawnSync } from 'node:child_process'
import { readFileSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'

const dir = process.argv[2] || '../assets/brand'

// 1. gold pixels, read from the source artwork itself (the old mask lost the paler dots):
// warm (red well above blue), mid-light, and clearly away from the ivory ground
const W = 1024
const H = 1024
const raw = spawnSync(
  'ffmpeg',
  ['-loglevel', 'error', '-i', join(dir, 'midad-logo-source.png'), '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-'],
  { maxBuffer: W * H * 4 },
).stdout
if (raw.length !== W * H * 3) throw new Error(`unexpected source size ${raw.length}`)
const on = (x, y) => {
  const i = (y * W + x) * 3
  const [r, g, b] = [raw[i], raw[i + 1], raw[i + 2]]
  const lum = 0.299 * r + 0.587 * g + 0.114 * b
  return r - b > 28 && lum > 90 && lum < 205
}

// 2. connected components (4-neighbour flood fill)
function components(on) {
  const label = new Int32Array(W * H).fill(-1)
  const comps = []
  for (let y = 0; y < H; y++) {
    for (let x = 0; x < W; x++) {
      if (!on(x, y) || label[y * W + x] !== -1) continue
      const px = []
      const stack = [[x, y]]
      label[y * W + x] = comps.length
      while (stack.length) {
        const [cx, cy] = stack.pop()
        px.push([cx, cy])
        for (const [nx, ny] of [
          [cx + 1, cy],
          [cx - 1, cy],
          [cx, cy + 1],
          [cx, cy - 1],
        ]) {
          if (nx < 0 || ny < 0 || nx >= W || ny >= H || !on(nx, ny) || label[ny * W + nx] !== -1) continue
          label[ny * W + nx] = comps.length
          stack.push([nx, ny])
        }
      }
      comps.push(px)
    }
  }
  return comps.sort((a, b) => b.length - a.length)
}
// dots only: the largest gold shape is the kasra (drawn separately below), and
// specks under 12 px are anti-aliasing noise
const [, ...rest] = components(on)
const dots = rest.filter((c) => c.length >= 12 && c.length < 400)

// 3. each dot → a square: centre and size from the pixels; the angle from the
// farthest pixel (a corner sits 45° off the square's own axis), then one shared
// angle for the whole spray (the median), since the artwork turns every dot alike
const measured = dots.map((px) => {
  const cx = px.reduce((n, p) => n + p[0], 0) / px.length + 0.5
  const cy = px.reduce((n, p) => n + p[1], 0) / px.length + 0.5
  let far = [0, 0, -1]
  for (const [x, y] of px) {
    const d = (x + 0.5 - cx) ** 2 + (y + 0.5 - cy) ** 2
    if (d > far[2]) far = [x + 0.5 - cx, y + 0.5 - cy, d]
  }
  const corner = (Math.atan2(far[1], far[0]) * 180) / Math.PI
  return { cx, cy, side: Math.sqrt(px.length), deg: (((corner - 45) % 90) + 90) % 90 }
})
const angles = measured.map((m) => m.deg).sort((a, b) => a - b)
const shared = angles[Math.floor(angles.length / 2)]
const squares = measured.map((m) => ({ ...m, deg: shared }))
const sq = ({ cx, cy, side, deg }) => {
  const t = (deg * Math.PI) / 180
  const h = side / 2
  const pts = [
    [-h, -h],
    [h, -h],
    [h, h],
    [-h, h],
  ].map(([u, v]) => [cx + u * Math.cos(t) - v * Math.sin(t), cy + u * Math.sin(t) + v * Math.cos(t)])
  return 'M ' + pts.map(([x, y]) => `${x.toFixed(2)} ${y.toFixed(2)}`).join(' L ') + ' Z'
}

// 4. the kasra: one brush stroke. Tracing its jagged mask gave a lumpy outline, so
// it is drawn from points measured on the source at 10× (source pixel coordinates):
// a diagonal cut at the upper right, a near-straight upper edge, a lower edge that
// bulges slightly, and the sharp tip at the lower left where the brush lifted.
const kasraD = [
  'M 665.8 771.2', // upper end of the diagonal cut
  'C 650 777.5, 618 790, 594 799.5', // upper edge
  'Q 591.2 803.5, 589.5 810.5', // the short left end, down into the tip
  'C 604 806.5, 641 791.5, 660.5 780.5', // lower edge, back up
  'Z', // the cut
].join(' ')

// 5. write the gold layer back into both copies of the logo data
const goldD = [kasraD, ...squares.map(sq)].join(' ')
for (const file of [join(dir, 'midad-logo.json'), 'src/assets/brand/midad-logo.json']) {
  const data = JSON.parse(readFileSync(file, 'utf8'))
  data.gold = goldD
  writeFileSync(file, JSON.stringify(data))
}
const data = JSON.parse(readFileSync(join(dir, 'midad-logo.json'), 'utf8'))
writeFileSync(
  join(dir, 'midad-logo.svg'),
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${data.viewBox}"><path fill="#0E0D0C" d="${data.ink}"/><path fill="#B08D57" d="${goldD}"/></svg>\n`,
)
console.log(
  `kasra redrawn · ${squares.length} squares, sides ${Math.min(...squares.map((s) => s.side)).toFixed(1)}–${Math.max(...squares.map((s) => s.side)).toFixed(1)} px, measured angles ${angles.map(Math.round).join(',')}, shared ${shared.toFixed(1)}°`,
)
console.log('dot centres', squares.map((q) => `${Math.round(q.cx)},${Math.round(q.cy)}`).join(' '))
