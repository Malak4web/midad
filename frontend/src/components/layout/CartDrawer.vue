<script setup>
import AttarCameo from '@/components/attar/AttarCameo.vue'
import { nextTick, ref, watch } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import CartLines from '@/components/cart/CartLines.vue'
import { useCartStore } from '@/stores/cart'
import { useUiStore } from '@/stores/ui'
import { formatPrice } from '@/utils/format'

const cart = useCartStore()
const ui = useUiStore()
const panel = ref(null)
// on the phone the drawer is a bottom sheet: drag its handle down to close
const drag = ref(0)
let startY = null
function down(e) {
  if (innerWidth >= 1024) return
  startY = e.clientY
  e.currentTarget.setPointerCapture?.(e.pointerId)
}
function move(e) {
  if (startY !== null) drag.value = Math.max(0, e.clientY - startY)
}
function up() {
  if (startY === null) return
  startY = null
  if (drag.value > 90) ui.cartOpen = false
  drag.value = 0
}
let lastFocus = null

watch(() => ui.cartOpen, async (open) => {
  if (open) {
    lastFocus = document.activeElement
    if (!cart.loaded) cart.load().catch(() => {})
    await nextTick()
    panel.value?.focus()
  } else lastFocus?.focus?.()
})
function trap(e) {
  if (e.key === 'Escape') ui.cartOpen = false
  if (e.key !== 'Tab') return
  const f = panel.value.querySelectorAll('button:not(:disabled), a[href], input')
  if (!f.length) return
  const first = f[0]
  const last = f[f.length - 1]
  if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus() }
  else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus() }
}
</script>

<template>
  <Transition name="drawer">
    <div v-if="ui.cartOpen" class="fixed inset-0 z-50 bg-ink/50" @click.self="ui.cartOpen = false">
      <aside ref="panel" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="cart-title" class="panel absolute flex flex-col bg-ivory outline-none" :style="drag ? { transform: `translateY(${drag}px)`, transition: 'none' } : undefined" @keydown="trap">
        <div class="flex shrink-0 touch-none select-none flex-col border-b border-sand" @pointerdown="down" @pointermove="move" @pointerup="up" @pointercancel="up">
          <span class="mx-auto mt-2.5 block h-1 w-10 rounded-full bg-ink/20 lg:hidden" aria-hidden="true" />
          <div class="flex items-center justify-between px-5 py-3 lg:px-6 lg:py-5">
            <h2 id="cart-title" class="m-0 font-display text-[1.625rem] lg:text-3xl">السلة</h2>
            <button class="-me-2 grid h-11 w-11 place-items-center" aria-label="إغلاق السلة" @click="ui.cartOpen = false"><AppIcon name="close" :size="22" /></button>
          </div>
        </div>

        <div v-if="!cart.items.length" class="flex flex-1 flex-col items-center justify-center gap-4 px-8 text-center">
          <AttarCameo pose="thinking" line="لم تختر بعد؟ أنا هنا." :size="104" :to="{ name: 'finder' }" action="اسأل خبير العطور" class="text-start" @click="ui.cartOpen = false" />
          <p class="m-0 font-display text-2xl">السلة فارغة</p>
          <p class="m-0 text-muted">ابدأ بمجموعة الاكتشاف إن كنت ما زلت تجرّب، أو اختر من الأقسام.</p>
          <RouterLink :to="{ name: 'discovery' }" class="rounded-full mt-2 bg-ink px-6 py-3 text-ivory no-underline" @click="ui.cartOpen = false">ابدأ بستّ عينات</RouterLink>
        </div>

        <div v-else class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 lg:px-6"><CartLines /></div>

        <div v-if="cart.items.length" class="shrink-0 border-t border-sand px-5 pb-[calc(16px+env(safe-area-inset-bottom,0px))] pt-4 lg:px-6 lg:py-5">
          <div class="flex items-baseline justify-between">
            <span>المجموع الفرعي</span>
            <span class="text-lg tabular">{{ formatPrice(cart.summary.subtotal) }}</span>
          </div>
          <p v-if="cart.summary.freeShippingRemaining > 0" class="m-0 mt-1 text-[0.8125rem] text-muted">باقي {{ formatPrice(cart.summary.freeShippingRemaining) }} على الشحن المجاني.</p>
          <p v-else class="m-0 mt-1 text-[0.8125rem] text-muted">الشحن والخصومات تُحسب في الخطوة التالية. الدفع عند الاستلام متاح.</p>
          <div class="mt-4 grid grid-cols-2 gap-2">
            <RouterLink :to="{ name: 'cart' }" class="border border-ink py-3.5 text-center text-ink no-underline" @click="ui.cartOpen = false">عرض السلة</RouterLink>
            <RouterLink :to="{ name: 'checkout' }" class="rounded-full bg-ink py-3.5 text-center text-ivory no-underline" @click="ui.cartOpen = false">إتمام الطلب</RouterLink>
          </div>
        </div>
      </aside>
    </div>
  </Transition>
</template>

<style scoped>
/* desktop: a drawer from the end side · phone: a sheet from the bottom */
.panel { inset-block: 0; inset-inline-end: 0; width: 100%; max-width: 420px; box-shadow: 0 0 60px rgba(14, 13, 12, 0.35); transition: transform 0.38s cubic-bezier(0.22, 0.8, 0.24, 1); }
@media (max-width: 1023px) {
  .panel { inset: auto 0 0 0; max-width: none; max-height: 92dvh; box-shadow: 0 -18px 50px -20px rgba(14, 13, 12, 0.5); }
}
.drawer-enter-active { transition: background-color 0.38s; }
.drawer-leave-active { transition: background-color 0.26s; }
.drawer-enter-from, .drawer-leave-to { background-color: transparent; }
.drawer-enter-from .panel, .drawer-leave-to .panel { transform: translateX(-100%); }
@media (max-width: 1023px) {
  .drawer-enter-from .panel, .drawer-leave-to .panel { transform: translateY(100%); }
}
</style>
