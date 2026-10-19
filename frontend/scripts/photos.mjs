// Brand photography → WebP renditions the site serves with srcset.
//   node scripts/photos.mjs "<folder with the photos>" ["<another folder>" …]
// Copies each chosen source into assets/photos-src/ (kept with the project), then
// writes src/assets/photos/<name>-<width>.webp at a few widths, never upscaling.
import { spawnSync } from 'node:child_process'
import { copyFileSync, existsSync, mkdirSync } from 'node:fs'
import { join } from 'node:path'

// several folders: the first one holding a file wins
const dirs = process.argv.slice(2)
if (!dirs.length) throw new Error('usage: node scripts/photos.mjs "<source folder>" ["<another folder>" …]')
const locate = (file) => {
  const d = [...dirs, '../assets/photos-src'].find((dir) => existsSync(join(dir, file)))
  if (!d) throw new Error(`missing photo ${file}`)
  return join(d, file)
}

// name → source file. Names say where a photo is used, not what it shows.
const PHOTOS = {
  'hero-sizes': '8b9f9974-1b7f-47b3-95ab-3e8c39eae834.jpg', // the four sizes on dark marble
  'world-men': 'b8ec2a2f-1597-490f-9b30-2060e2fa852b.jpg', // amber, vanilla and lavender on wood
  'world-women': '304341a3-528f-4135-ab3d-59127b4d6be0.jpg', // peonies, strawberries and berries
  'world-oriental': 'MIDAD_bottle_with_bakhoor_incense_20261018103707.jpg', // bakhoor burner and roses
  'story-ink': '519683f0-a62c-4fba-9973-42d9efc73c5b.jpg', // reed pen, ink and paper
  'discovery-set': 'unnamed (3).jpg', // the six-vial box
  'gift-ribbon': 'unnamed (10).jpg', // wrapped box with gold ribbon
  'gift-open': 'unnamed (2).jpg', // opened box with thank-you card
  'studio-dark': 'unnamed (6).jpg', // bottle on black
  'studio-light': 'unnamed.jpg', // bottle on cream
  'spray-wrist': 'unnamed (9).jpg', // spraying a wrist
  'logo-drawing': 'unnamed (4).jpg', // the logo drawn in gold ink
  'fresh-sea': 'unnamed (8).jpg', // waves and citrus
  'gourmand': 'unnamed (5).jpg', // caramel and pear
  'delivery': 'unnamed (7).jpg', // the box handed over at the door
  'atelier': 'unnamed (1).jpg', // the perfumer's table
  // product cards: a scene for each scent family (the generator's full-size exports)
  'scene-gourmand': 'MIDAD_bottle_with_caramel_drizzle_20261018120930.jpg', // caramel, hazelnut, pear
  'scene-fresh': 'MIDAD_bottle_on_wet_stone_20261018120930.jpg', // waves, lime and lemon
  'scene-ink': 'MIDAD_bottle_with_calligraphy_tools_20261018120930.jpg', // reed pen, ink, old paper
  'scene-gift': 'MIDAD_bottle_with_gift_box_20261018120930.jpg', // black box, gold ribbon, lights
  // the bottle alone on transparency (scripts/bottle-cut.mjs writes it)
  'bottle-cut': 'bottle-cut.png',
}
const WIDTHS = [480, 960, 1376]

const keep = '../assets/photos-src'
const out = 'src/assets/photos'
mkdirSync(keep, { recursive: true })
mkdirSync(out, { recursive: true })
const width = (f) => Number(spawnSync('ffprobe', ['-v', 'error', '-show_entries', 'stream=width', '-of', 'csv=p=0', f]).stdout.toString().trim())

for (const [name, file] of Object.entries(PHOTOS)) {
  const from = locate(file)
  const ext = file.slice(file.lastIndexOf('.'))
  if (from !== join(keep, name + ext)) copyFileSync(from, join(keep, name + ext))
  const w0 = width(from)
  const widths = [...new Set(WIDTHS.filter((w) => w < w0).concat(w0 <= 1376 ? [w0] : []))].sort((a, b) => a - b)
  for (const w of widths) {
    const r = spawnSync('ffmpeg', ['-loglevel', 'error', '-y', '-i', from, '-vf', `scale=${w}:-2:flags=lanczos`, ...(ext === '.png' ? ['-pix_fmt', 'yuva420p'] : []), '-c:v', 'libwebp', '-quality', '80', '-compression_level', '6', join(out, `${name}-${w}.webp`)])
    if (r.status) throw new Error(r.stderr.toString())
  }
  console.log(name.padEnd(16), `${w0}px →`, widths.join(', '))
}
