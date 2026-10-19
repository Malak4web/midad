// Home payload from GET /api/home (BE-24). The local mock catalog stays as an
// offline fallback so the storefront still renders when the API is down in dev.
import { categories, productBySlug, productsIn } from '@/data/catalog'
import { spotlights, fallbackPalette } from '@/data/spotlights'
import { http } from './http'

export const ORIGINAL_SEARCHES = ['إنفيكتوس إكسير', 'بلو دي شانيل', 'كريد أفنتوس', 'بيانكو لاتيه', 'ميس ديور', 'بلاك أفغانو']

// Settings-driven in production (BE-54). PLACEHOLDER free-shipping threshold.
export const ANNOUNCEMENTS = ['الدفع عند الاستلام في كل المحافظات', 'عينة ٢ مل هدية مع كل طلب', 'شحن مجاني للطلبات فوق ١٥٠٠ ج.م']

export function buildHome() {
  return {
    discovery: { price: 350, samples: 6, sampleMl: 2 }, // PLACEHOLDER price
    sections: categories.map((c) => {
      const s = spotlights[c.slug]
      const featured = s ? productBySlug(s.product) : null
      return {
        category: c,
        spotlight: featured ? { ...s, product: featured } : null,
        palette: s?.palette || fallbackPalette,
        others: productsIn(c.slug).filter((p) => p.slug !== featured?.slug),
      }
    }),
  }
}

// FE-51: on a cold load of "/" the server embeds the payload in the HTML shell.
// Read it once; later visits fetch fresh.
function takeBoot() {
  const el = typeof document !== 'undefined' && document.getElementById('midad-boot')
  if (!el) return null
  el.remove()
  try {
    return JSON.parse(el.textContent).home || null
  } catch {
    return null
  }
}

export async function getHome({ signal } = {}) {
  const boot = takeBoot()
  if (boot) return { ...boot, source: 'api' }
  try {
    const { data } = await http('/home', { signal })
    return { ...data, source: 'api' }
  } catch (e) {
    if (e.name === 'AbortError') throw e
    if (import.meta.env.DEV) console.warn('[home] API unavailable, using local catalog:', e.message)
    return { ...buildHome(), source: 'local' }
  }
}
