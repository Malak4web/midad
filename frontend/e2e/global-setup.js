import { execSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'

// Fresh, seeded database for every run so stock and orders start clean.
export default function () {
  const backend = join(dirname(fileURLToPath(import.meta.url)), '..', '..', 'backend')
  execSync('php artisan migrate:fresh --seed --force', { cwd: backend, stdio: 'ignore' })
  execSync('php artisan cache:clear', { cwd: backend, stdio: 'ignore' })
}
