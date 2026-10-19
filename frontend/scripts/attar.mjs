// The Attar character: motion videos → small looping clips, poses → WebP.
//   node scripts/attar.mjs "<folder>" ["<folder>" …]
// The folders hold the original 1280×720 MP4 motions (the generator's export) and
// the still poses. A GIF is used only when no MP4 of that motion exists: GIFs are
// 480×270 and dithered, so they always look noisy once enlarged.
// Every motion starts and ends in the same rest pose (hands together), so each
// is kept up to the frame where it is back at rest (~7.83 s in the originals),
// so the site can chain them seamlessly.
import { spawnSync } from 'node:child_process'
import { copyFileSync, mkdirSync, readdirSync } from 'node:fs'
import { extname, join } from 'node:path'

const dirs = process.argv.slice(2)
if (!dirs.length) throw new Error('usage: node scripts/attar.mjs "<folder>" ["<folder>" …]')
const files = dirs.flatMap((d) => readdirSync(d).map((f) => ({ name: f, path: join(d, f) })))
const find = (start, exts) => exts.map((e) => files.find((f) => f.name.startsWith(start) && f.name.toLowerCase().endsWith(e))).find(Boolean)

// name → [source prefix, seconds to keep]
const MOTIONS = {
  idle: ['Character_breathing', 8], // loops: rest, breathing
  listen: ['Man_listening_attentively', 8], // loops: head tilted, attentive
  smile: ['Man_listening_with_warm_smile', 7.875], // reply: a warm smile
  explain: ['Man_gesturing_while_explaining', 7.875], // reply: open hands, explaining
  present: ['Man_presenting_bottle', 7.875], // the result: offers the bottle
  smell: ['Man_smelling_perfume_blotter', 8], // thinking: smells a blotter
  wave: ['Man_waving_hello', 7.875], // hello
  thumbs: ['Person_smiling_and_giving_thumbs', 7.875], // well chosen
}
// name → still pose (1024² unless noted)
const POSES = {
  thinking: '0d0f622c-a05f-4856-8c11-1edbfb5d44b6.jpg',
  hello: '19b6d10b-29ab-4d02-abec-fbef5789f56e.jpg',
  smelling: '2749c7b8-d966-4187-a01b-c1cfe751ac86.jpg',
  samples: '41f84072-9078-4b35-86bf-9124aecc135a.jpg',
  portrait: '8db59149-608b-4523-b80c-baf61b16abf8.jpg',
  gift: 'c73ae581-4522-4517-9238-a552172dbd63.jpg',
  approve: 'dbc6dcd1-4674-4189-a946-1c8dee80ec71.jpg',
  heart: 'e9dc9abf-aaaa-4faf-9f4c-26caca37c1c4.jpg',
  offer: 'f56a8690-b5fb-429c-ac5a-9aea541ffc39.jpg',
}

const out = 'src/assets/attar'
const keep = '../assets/attar-src'
mkdirSync(out, { recursive: true })
mkdirSync(keep, { recursive: true })
const run = (args) => {
  const r = spawnSync('ffmpeg', ['-loglevel', 'error', '-y', ...args])
  if (r.status) throw new Error(r.stderr.toString())
}

// the character stands in the middle half of a 16:9 frame; keep that half, at
// 640×720 (the stage shows him ~490 css px tall, so this is sharp on a 2× screen)
const FILTER = {
  // the original: crop, a whisper of temporal denoise (helps the encoder, keeps detail)
  '.mp4': 'crop=iw/2:ih:iw/4:0,scale=640:720:flags=lanczos,hqdn3d=1:1:3:3',
  // a GIF fallback: take the dithering out, then enlarge
  '.gif': 'crop=iw/2:ih:iw/4:0,nlmeans=s=7:p=7:r=15,hqdn3d=0:0:16:16,scale=640:720:flags=lanczos,unsharp=5:5:1.1:5:5:0',
}
for (const [name, [prefix, secs]] of Object.entries(MOTIONS)) {
  const file = find(prefix, ['.mp4', '.gif'])
  if (!file) throw new Error(`missing motion ${prefix}`)
  const ext = extname(file.name).toLowerCase()
  copyFileSync(file.path, join(keep, `${name}${ext}`))
  const vf = `${FILTER[ext]},fps=24,format=yuv420p`
  run(['-t', String(secs), '-i', file.path, '-vf', vf, '-c:v', 'libx264', '-crf', '24', '-preset', 'slow', '-tune', 'animation', '-movflags', '+faststart', '-an', join(out, `${name}.mp4`)])
  console.log('motion', name.padEnd(8), `${secs}s`, ext)
}
// the rest pose as a poster, shown before a video starts and to reduced motion
const rest = find('Character_breathing', ['.mp4', '.gif'])
run(['-i', rest.path, '-vf', FILTER[extname(rest.name).toLowerCase()], '-frames:v', '1', '-c:v', 'libwebp', '-quality', '84', join(out, 'rest.webp')])

for (const [name, file] of Object.entries(POSES)) {
  const f = files.find((x) => x.name === file)
  copyFileSync(f.path, join(keep, `${name}.jpg`))
  for (const w of [480, 960]) run(['-i', f.path, '-vf', `scale=${w}:-2:flags=lanczos`, '-c:v', 'libwebp', '-quality', '80', join(out, `pose-${name}-${w}.webp`)])
  console.log('pose  ', name)
}
