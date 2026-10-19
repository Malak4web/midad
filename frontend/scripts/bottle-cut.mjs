// The bottle alone, on transparency, for the colour cards and the hero.
//   node scripts/bottle-cut.mjs "<folder with MIDAD_bottle_front_view_*.jpg>"
// The front-view packshot sits on a plain cream backdrop. The backdrop is grown
// from the borders (neighbouring pixels within a small step, and still cream);
// whatever it cannot reach is the bottle. Then: keep the largest piece, soften
// the edge, fade the pale cast shadow under the base, crop, and write
// ../assets/photos-src/bottle-cut.png for scripts/photos.mjs.
import { spawnSync } from 'node:child_process'
import { mkdirSync, readFileSync, readdirSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import { tmpdir } from 'node:os'
import { PNG } from 'pngjs'

const dir = process.argv[2]
if (!dir) throw new Error('usage: node scripts/bottle-cut.mjs "<folder>"')
const file = readdirSync(dir).find((f) => f.startsWith('MIDAD_bottle_front_view') && f.endsWith('.jpg'))
if (!file) throw new Error('missing MIDAD_bottle_front_view_*.jpg')
const raw = join(tmpdir(), 'midad-bottle-front.png')
const r = spawnSync('ffmpeg', ['-loglevel', 'error', '-y', '-i', join(dir, file), '-pix_fmt', 'rgba', raw])
if (r.status) throw new Error(r.stderr.toString())

const img = PNG.sync.read(readFileSync(raw))
const { width: W, height: H, data } = img
const at = (x, y) => (y * W + x) * 4
const step = (i, j) => Math.abs(data[i] - data[j]) + Math.abs(data[i + 1] - data[j + 1]) + Math.abs(data[i + 2] - data[j + 2])
const creamy = (i) => data[i] > 150 && data[i] - data[i + 2] < 70

// 1. grow the backdrop in from the borders
const bg = new Uint8Array(W * H)
const q = []
for (let x = 0; x < W; x++) for (const y of [0, H - 1]) { bg[y * W + x] = 1; q.push(x, y) }
for (let y = 0; y < H; y++) for (const x of [0, W - 1]) { bg[y * W + x] = 1; q.push(x, y) }
while (q.length) {
  const y = q.pop(), x = q.pop()
  for (const [dx, dy] of [[1, 0], [-1, 0], [0, 1], [0, -1]]) {
    const nx = x + dx, ny = y + dy
    if (nx < 0 || ny < 0 || nx >= W || ny >= H || bg[ny * W + nx]) continue
    if (step(at(x, y), at(nx, ny)) < 14 && creamy(at(nx, ny))) { bg[ny * W + nx] = 1; q.push(nx, ny) }
  }
}
// 2. keep the largest piece that is not backdrop
const lab = new Int32Array(W * H)
let best = 0, bestN = 0, id = 0
for (let s = 0; s < W * H; s++) {
  if (bg[s] || lab[s]) continue
  id++
  let n = 0
  const st = [s]
  lab[s] = id
  while (st.length) {
    const p = st.pop(); n++
    const x = p % W, y = (p / W) | 0
    for (const [dx, dy] of [[1, 0], [-1, 0], [0, 1], [0, -1]]) {
      const nx = x + dx, ny = y + dy
      if (nx < 0 || ny < 0 || nx >= W || ny >= H) continue
      const k = ny * W + nx
      if (!bg[k] && !lab[k]) { lab[k] = id; st.push(k) }
    }
  }
  if (n > bestN) { bestN = n; best = id }
}
// 3. soft edge: alpha is the share of the bottle in a 5×5 neighbourhood
const alpha = new Uint8Array(W * H)
for (let y = 0; y < H; y++) for (let x = 0; x < W; x++) {
  let n = 0, t = 0
  for (let dy = -2; dy <= 2; dy++) for (let dx = -2; dx <= 2; dx++) {
    const xx = x + dx, yy = y + dy
    if (xx < 0 || yy < 0 || xx >= W || yy >= H) continue
    t++
    if (lab[yy * W + xx] === best) n++
  }
  alpha[y * W + x] = Math.round((n / t) * 255)
}
// 4. crop to the bottle, fading pale floor pixels in its bottom tenth
let x0 = W, y0 = H, x1 = 0, y1 = 0
for (let p = 0; p < W * H; p++) if (alpha[p] > 8) { const x = p % W, y = (p / W) | 0; x0 = Math.min(x0, x); x1 = Math.max(x1, x); y0 = Math.min(y0, y); y1 = Math.max(y1, y) }
const cw = x1 - x0 + 1, ch = y1 - y0 + 1
const out = new PNG({ width: cw, height: ch })
for (let y = 0; y < ch; y++) for (let x = 0; x < cw; x++) {
  const i = at(x + x0, y + y0), o = (y * cw + x) * 4
  let a = alpha[(y + y0) * W + x + x0]
  if (y > ch * 0.9) {
    const l = data[i] * 0.3 + data[i + 1] * 0.59 + data[i + 2] * 0.11
    if (l > 110) a = Math.min(a, Math.max(0, Math.round((150 - l) * 6)))
  }
  out.data[o] = data[i]; out.data[o + 1] = data[i + 1]; out.data[o + 2] = data[i + 2]; out.data[o + 3] = a
}
mkdirSync('../assets/photos-src', { recursive: true })
writeFileSync('../assets/photos-src/bottle-cut.png', PNG.sync.write(out))
console.log(`bottle ${cw}×${ch}`)
