<script setup>
import AppIcon from '@/components/ui/AppIcon.vue'
import ProductThumb from '@/components/brand/ProductThumb.vue'
import { useCartStore } from '@/stores/cart'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'
import { formatMl, formatNumber, formatPrice } from '@/utils/format'

defineProps({ compact: { type: Boolean, default: false } })
const cart = useCartStore()
const ui = useUiStore()
async function qty(item, n) {
  try {
    await cart.setQty(item.id, n)
  } catch (e) {
    ui.toast(firstError(e))
  }
}
</script>

<template>
  <ul class="m-0 list-none p-0">
    <li v-for="i in cart.items" :key="i.id" class="grid grid-cols-[52px_1fr_auto] gap-4 border-b border-sand py-5">
      <div class="flex justify-center">
        <span v-if="i.kind === 'discovery_box'" class="mt-1 grid h-12 w-12 place-items-center bg-ink text-gold-light" aria-hidden="true"><AppIcon name="gift" :size="22" /></span>
        <ProductThumb v-else :size="52" />
      </div>
      <div class="min-w-0">
        <p class="m-0 font-display text-xl font-bold leading-tight">
          <RouterLink v-if="i.slug" :to="{ name: 'product', params: { slug: i.slug } }" class="text-ink no-underline hover:underline">{{ i.name }}</RouterLink>
          <span v-else>{{ i.name }}</span>
        </p>
        <p class="m-0 text-[0.8125rem] text-muted">
          <template v-if="i.kind === 'discovery_box'">ست عينات · {{ i.samples.join('، ') }}</template>
          <template v-else>{{ formatMl(i.ml) }} · {{ formatPrice(i.unitPrice) }}</template>
        </p>
        <p v-if="!i.available" class="m-0 mt-1 text-[0.8125rem] text-danger">الكمية المطلوبة لم تعد متوفرة. المتاح: {{ formatNumber(i.stock ?? 0) }}</p>
        <div class="mt-3 inline-flex items-center border border-sand" :class="cart.busy && 'opacity-60'">
          <button class="grid h-9 w-9 place-items-center" :disabled="cart.busy" :aria-label="`إنقاص كمية ${i.name}`" @click="qty(i, i.quantity - 1)"><AppIcon name="minus" :size="16" /></button>
          <span class="w-8 text-center text-sm tabular" aria-live="polite">{{ formatNumber(i.quantity) }}</span>
          <button class="grid h-9 w-9 place-items-center disabled:opacity-30" :disabled="cart.busy || i.quantity >= (i.kind === 'discovery_box' ? 3 : 10)" :aria-label="`زيادة كمية ${i.name}`" @click="qty(i, i.quantity + 1)"><AppIcon name="plus" :size="16" /></button>
        </div>
      </div>
      <div class="flex flex-col items-end justify-between">
        <span class="text-[0.9375rem] tabular">{{ formatPrice(i.lineTotal) }}</span>
        <button class="text-[0.8125rem] text-muted underline disabled:opacity-50" :disabled="cart.busy" @click="qty(i, 0)">إزالة</button>
      </div>
    </li>
  </ul>
</template>
