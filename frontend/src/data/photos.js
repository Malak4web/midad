// Brand photography (scripts/photos.mjs). Each photo is a set of WebP widths;
// photo(name) returns { src, srcset } ready for <img>, the largest width as src.
const files = import.meta.glob('@/assets/photos/*.webp', { eager: true, import: 'default' })

const sets = {}
for (const [path, url] of Object.entries(files)) {
  const m = path.match(/\/([a-z-]+)-(\d+)\.webp$/)
  if (m) (sets[m[1]] ||= []).push([Number(m[2]), url])
}

export function photo(name) {
  const list = (sets[name] || []).sort((a, b) => a[0] - b[0])
  if (!list.length) return null
  return { src: list.at(-1)[1], srcset: list.map(([w, u]) => `${u} ${w}w`).join(', '), width: list.at(-1)[0] }
}

// Each category's world. `cap` is where the bottle's nozzle sits in the source
// photo (shares of its width and height) so the spray leaves from it; `focus` is
// the object-position that keeps the bottle in a square crop.
export const WORLDS = {
  men: { photo: 'world-men', cap: [0.5, 0.1], focus: [0.5, 0.5] },
  women: { photo: 'world-women', cap: [0.6, 0.12], focus: [0.6, 0.5] },
  oriental: { photo: 'world-oriental', cap: [0.505, 0.26], focus: [0.5, 0.5] },
}

// The bottle alone, for cards and thumbnails: cream or black studio, whichever
// sits closer to the surface it is placed on.
export const studioFor = (isDark) => photo(isDark ? 'studio-dark' : 'studio-light')

// Each scent family: its photograph (for scenes), where the bottle stands in it,
// its name, and its tone in the bento: Midad's sand-to-smoke family, so the
// families stay apart without leaving the brand. Dark tones carry ivory text.
export const SCENES = {
  gourmand: { photo: 'scene-gourmand', x: 0.41, label: 'حلو ودافئ', color: '#C9B48E' },
  fresh: { photo: 'scene-fresh', x: 0.5, label: 'منعش', color: '#DCD3C0' },
  floral: { photo: 'world-women', x: 0.58, label: 'زهري', color: '#E8DCC4' },
  oriental: { photo: 'world-oriental', x: 0.5, label: 'شرقي', color: '#3B342C', dark: true },
  amber: { photo: 'world-men', x: 0.49, label: 'عنبري', color: '#D6B77E' },
  woody: { photo: 'scene-ink', x: 0.64, label: 'خشبي', color: '#0E0D0C', dark: true },
  gift: { photo: 'scene-gift', x: 0.63, label: '', color: '#D8CAB0' },
}

// the bottle alone on transparency (scripts/bottle-cut.mjs)
export const BOTTLE = photo('bottle-cut')
