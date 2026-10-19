// Short brand films for the featured-perfume section ("وراء العطر").
//   node scripts/films.mjs "<folder>" ["<folder>" …]
// The folders hold the generator's 1280×720 exports. Each film is cut to a
// portrait 4:5 window from its centre, 384×480 (a ~190 css px tile on a 2×
// screen), muted, and gets a WebP poster from its first frame for reduced
// motion and before it loads.
import { spawnSync } from 'node:child_process'
import { copyFileSync, mkdirSync, readdirSync } from 'node:fs'
import { join } from 'node:path'

const dirs = process.argv.slice(2)
if (!dirs.length) throw new Error('usage: node scripts/films.mjs "<folder>" ["<folder>" …]')
const files = dirs.flatMap((d) => readdirSync(d).map((f) => ({ name: f, path: join(d, f) })))
const find = (prefix) => files.find((f) => f.name.startsWith(prefix) && f.name.toLowerCase().endsWith('.mp4'))

// name → [source prefix, seconds, centre of the portrait window (share of width)]
const FILMS = {
  dropper: ['Perfumer_drawing_golden_essentia', 8, 0.5], // the perfumer's hand and dropper
  amber: ['Amber_liquid_drops_falling', 8, 0.5], // amber turning in the glass
  raw: ['Raw_materials_falling_on_wood', 8, 0.58], // saffron, rose and oud falling
  mist: ['Ink_turns_into_golden_mist', 8, 0.5], // a golden ribbon of scent
  sparkle: ['Perfume_bottle_spraying_mist', 8, 0.48], // the spray in warm light
  spray: ['Perfume_bottle_rotating_on_display', 8, 0.5], // the spray on black marble
}

const out = 'src/assets/films'
const keep = '../assets/films-src'
mkdirSync(out, { recursive: true })
mkdirSync(keep, { recursive: true })
const run = (args) => {
  const r = spawnSync('ffmpeg', ['-loglevel', 'error', '-y', ...args])
  if (r.status) throw new Error(r.stderr.toString())
}

for (const [name, [prefix, secs, cx]] of Object.entries(FILMS)) {
  const file = find(prefix)
  if (!file) throw new Error(`missing film ${prefix}`)
  copyFileSync(file.path, join(keep, `${name}.mp4`))
  // the exports are all 1280×720: a 576×720 window, slid to the subject
  const x = Math.round(Math.min(1280 - 576, Math.max(0, 1280 * cx - 288)))
  const crop = `crop=576:720:${x}:0,scale=384:480:flags=lanczos`
  run(['-t', String(secs), '-i', file.path, '-vf', `${crop},fps=24,format=yuv420p`, '-c:v', 'libx264', '-crf', '28', '-preset', 'slow', '-movflags', '+faststart', '-an', join(out, `${name}.mp4`)])
  run(['-ss', '1', '-i', file.path, '-vf', crop, '-frames:v', '1', '-c:v', 'libwebp', '-quality', '78', join(out, `${name}.webp`)])
  console.log('film', name.padEnd(8), `${secs}s`)
}
