import { useCartStore } from '@/stores/cart'
import { useUiStore } from '@/stores/ui'
import { formatMl } from '@/utils/format'

export function firstError(e, fallback = 'تعذّر تنفيذ الطلب. حاول مرة أخرى.') {
  const errs = e?.errors && Object.values(e.errors).flat()
  return errs?.[0] || e?.message || fallback
}

export function useAddToCart() {
  const cart = useCartStore()
  const ui = useUiStore()
  return async (product, ml = 50, qty = 1) => {
    const variant = product.variants?.find((v) => v.ml === ml)
    if (variant && variant.stock <= 0) {
      ui.toast(`حجم ${formatMl(ml)} من ${product.name} غير متوفر حالياً.`)
      return false
    }
    try {
      await cart.add(product.slug, ml, qty)
      ui.toast(`أُضيف ${product.name} · ${formatMl(ml)} إلى السلة`, { action: { label: 'عرض السلة', run: () => (ui.cartOpen = true) } })
      return true
    } catch (e) {
      ui.toast(firstError(e))
      return false
    }
  }
}
