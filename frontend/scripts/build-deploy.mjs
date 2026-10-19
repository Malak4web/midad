// Production hand-off (BE-56): npm run build:deploy
// The shell goes to backend/resources/spa/ (so the web server never serves it
// raw and SeoController can rewrite its meta); hashed assets and static files
// go to backend/public/ where the web server serves them directly.
import { cpSync, existsSync, mkdirSync, readdirSync, rmSync } from 'node:fs'
import { join } from 'node:path'

const dist = 'dist'
const pub = '../backend/public'
const spa = '../backend/resources/spa'

mkdirSync(spa, { recursive: true })
cpSync(join(dist, 'index.html'), join(spa, 'index.html'))
const firstPaint = join(dist, 'home-first-paint.html')
if (existsSync(firstPaint)) {
  cpSync(firstPaint, join(spa, 'home-first-paint.html'))
}
rmSync(join(pub, 'assets'), { recursive: true, force: true })
for (const entry of readdirSync(dist)) {
  if (entry === 'index.html' || entry === 'home-first-paint.html') continue
  cpSync(join(dist, entry), join(pub, entry), { recursive: true })
}
console.log('shell →', spa, '· assets →', pub)
