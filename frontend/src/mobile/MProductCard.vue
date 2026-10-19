<script setup>
import { computed } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { useAddToCart } from '@/composables/useAddToCart'
import { lowestPrice } from '@/data/catalog'
import { BOTTLE, SCENES } from '@/data/photos'
import { familiesOf } from '@/utils/scenes'
import { formatPrice } from '@/utils/format'

// A perfume on the phone: a small paper card, the bottle on its family's tone,
// the name, the original, the price and a square add button.
const props = defineProps({ product: { type: Object, required: true } })
const add = useAddToCart()
const family = computed(() => familiesOf(props.product)[0])
const tone = computed(() => SCENES[family.value] || SCENES.amber)
const from = computed(() => props.product.fromPrice ?? lowestPrice(props.product))
</script>

<template>
  <article class="pc">
    <div class="im" :style="{ background: tone.color }">
      <img v-if="product.image" :src="product.image.src" :srcset="product.image.srcset" sizes="45vw" alt="" loading="lazy" decoding="async" class="own" />
      <img v-else :src="BOTTLE.src" alt="" loading="lazy" decoding="async" class="bottle" />
      <em v-if="tone.label">{{ tone.label }}</em>
    </div>
    <h3 class="m-0 font-display text-[1rem] font-bold leading-[1.3]">
      <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" class="stretched text-ink no-underline">{{ product.name }}</RouterLink>
    </h3>
    <p class="m-0 truncate text-[0.6875rem] text-muted">مستوحى من {{ product.inspiredBy }}</p>
    <div class="pr">
      <span class="tabular">{{ formatPrice(from) }}</span>
      <button class="add" :aria-label="`أضف ${product.name} ٥٠ مل إلى السلة`" @click="add(product, 50)"><AppIcon name="plus" :size="16" /></button>
    </div>
  </article>
</template>

<style scoped>
.pc { position: relative; display: flex; flex-direction: column; gap: 2px; padding: 8px; border-radius: 16px; background: var(--color-paper); }
.im { position: relative; display: grid; place-items: center; height: 132px; margin-bottom: 4px; border-radius: 12px; overflow: hidden; }
.bottle { height: 112px; width: auto; filter: drop-shadow(0 8px 8px rgba(14, 13, 12, 0.3)); }
.own { width: 100%; height: 100%; object-fit: cover; }
em { position: absolute; top: 6px; inset-inline-end: 6px; padding: 1px 8px; border-radius: 999px; background: rgba(251, 247, 238, 0.88); color: var(--color-ink); font-size: 0.65rem; font-style: normal; }
.pr { display: flex; justify-content: space-between; align-items: center; margin-top: 4px; font-size: 0.78rem; font-weight: 600; }
.add { position: relative; z-index: 2; display: grid; place-items: center; width: 32px; height: 32px; border-radius: 10px; background: var(--color-ink); color: var(--color-gold-light); }
.add:active { transform: scale(0.92); }
.stretched::after { content: ''; position: absolute; inset: 0; z-index: 1; border-radius: 16px; }
</style>
