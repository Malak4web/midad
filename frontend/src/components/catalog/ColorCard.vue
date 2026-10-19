<script setup>
import { computed } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { useAddToCart } from '@/composables/useAddToCart'
import { formatPrice } from '@/utils/format'
import { lowestPrice } from '@/data/catalog'
import { BOTTLE, SCENES } from '@/data/photos'

// A perfume in the bento: a rounded tile in its scent family's tone, framed in a
// gold hairline, the bottle in a gold ring, the name, the price and an add button.
// A perfume with its own photograph shows that instead of the bottle.
// `scene` is chosen by the row (utils/scenes) so neighbours never share a colour.
const props = defineProps({
  product: { type: Object, required: true },
  scene: { type: String, default: 'woody' },
  family: { type: String, default: '' }, // the chip
})
const add = useAddToCart()
const from = computed(() => lowestPrice(props.product))
const tone = computed(() => SCENES[props.scene] || SCENES.woody)
</script>

<template>
  <article class="card framed group relative flex h-full flex-col" :class="tone.dark && 'is-dark'" :style="{ background: tone.color }">
    <span v-if="family" class="chip relative z-[2] self-start">{{ family }}</span>
    <div class="stage relative">
      <span class="halo" aria-hidden="true" />
      <img v-if="product.image" :src="product.image.src" :srcset="product.image.srcset" sizes="(min-width: 1024px) 22vw, 45vw" :alt="product.image.alt || ''" loading="lazy" decoding="async" class="own" />
      <img v-else :src="BOTTLE.src" alt="" loading="lazy" decoding="async" class="bottle" />
    </div>
    <h3 class="m-0 font-display text-[1.375rem] font-bold leading-tight md:text-[1.5rem]">
      <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" class="stretched no-underline">{{ product.name }}</RouterLink>
    </h3>
    <p class="m-0 line-clamp-2 text-[0.8125rem] leading-snug opacity-75">مستوحى من {{ product.inspiredBy }}</p>
    <div class="mt-3 flex items-center justify-between">
      <span class="text-[0.9375rem] font-semibold tabular">من {{ formatPrice(from) }}</span>
      <button class="btn-round relative z-[2]" :aria-label="`أضف ${product.name} ٥٠ مل إلى السلة`" @click="add(product, 50)">
        <AppIcon name="plus" :size="18" />
      </button>
    </div>
  </article>
</template>

<style scoped>
.card { border-radius: var(--radius-tile); padding: 16px; color: var(--color-ink); transition: transform 0.3s var(--ease-pop); }
.card:hover { transform: translateY(-6px) rotate(-0.6deg); }
.chip { font-size: 0.75rem; background: var(--color-paper); color: var(--color-ink); border-radius: 999px; padding: 3px 11px; }
.stage { flex: 1; min-height: 190px; display: grid; place-items: center; margin: 4px 0 10px; }
.halo { position: absolute; width: min(72%, 180px); aspect-ratio: 1; border-radius: 50%; border: 1px solid rgba(118, 90, 48, 0.45); }
.card.is-dark { color: var(--color-ivory); }
.card.is-dark .halo { border-color: rgba(214, 183, 126, 0.5); }
.card.is-dark .btn-round { background: var(--color-gold-light); color: var(--color-ink); }
.stretched { color: inherit; }
.bottle { position: relative; height: 180px; width: auto; filter: drop-shadow(0 16px 16px rgba(14, 13, 12, 0.35)); transition: transform 0.45s var(--ease-pop); }
.group:hover .bottle { transform: translateY(-6px) rotate(-3deg); }
.own { position: relative; width: 82%; aspect-ratio: 1; object-fit: cover; border-radius: 22px; }
.stretched::after { content: ''; position: absolute; inset: 0; z-index: 1; border-radius: var(--radius-tile); }
@media (max-width: 640px) { .stage { min-height: 150px; } .bottle { height: 140px; } }
@media (prefers-reduced-motion: reduce) { .card:hover, .group:hover .bottle { transform: none; } }
</style>
