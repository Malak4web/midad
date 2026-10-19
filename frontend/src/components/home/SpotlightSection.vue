<script setup>
import { computed, ref, useId } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import ProductSlider from './ProductSlider.vue'
import { useAddToCart } from '@/composables/useAddToCart'
import { readableOn } from '@/utils/contrast'
import { formatMl, formatNumber, formatPrice } from '@/utils/format'
import { WORLDS, photo } from '@/data/photos'

// One category on the home page: its featured perfume as a small bento (the
// photograph, a name tile in the perfume's own colour from the admin, one tile per
// layer of the scent, and a buy tile), then the rest of the category in a slider.
// preview: the admin's live preview, where the placeholder badge belongs (never the storefront)
const props = defineProps({ section: { type: Object, required: true }, preview: { type: Boolean, default: false } })
const add = useAddToCart()
const id = useId()

const pal = computed(() => {
  const p = props.section.palette
  return { ...p, fg: readableOn(p.bg, p.fg), muted: readableOn(p.bg, p.muted, 3) }
})
const sp = computed(() => props.section.spotlight)
const product = computed(() => sp.value?.product)
const total = computed(() => props.section.others.length + (product.value ? 1 : 0))
const slug = computed(() => props.section.category.slug)

const size = ref(50)
const variant = computed(() => product.value?.variants.find((v) => v.ml === size.value))
// the three layers, each in its own scent colour
const LAYER_COLORS = ['var(--color-sand)', 'var(--color-gold-light)', 'var(--color-sand-light)']
const layers = computed(() => {
  const p = sp.value?.pyramid || product.value?.pyramid
  if (!p) return []
  return [
    ['القمة', p.top, 'أول ربع ساعة'],
    ['القلب', p.heart, 'من ساعة إلى أربع'],
    ['القاعدة', p.base, 'ما يبقى للمساء'],
  ].filter(([, notes]) => notes?.length)
})

// the photograph: an uploaded scene, else the category's world
const world = computed(() => WORLDS[slug.value] || WORLDS.men)
const pic = computed(() => {
  const s = sp.value?.sceneImage
  if (!s) return photo(world.value.photo)
  return { src: /^(https?:)?\/\//.test(s) || s.startsWith('/') ? s : `/storage/${s}`, srcset: '' }
})
</script>

<template>
  <section class="container-midad flex flex-col gap-5 py-10 md:py-14" :aria-labelledby="`${id}-h`">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <h2 :id="`${id}-h`" class="m-0 font-display text-[clamp(2rem,5vw,3.75rem)] font-bold leading-[1.2]">{{ section.category.name }}</h2>
      <RouterLink :to="{ name: 'category', params: { slug: section.category.slug } }" class="btn-pill is-light min-h-11 px-5 text-[0.9375rem]">
        كل عطور {{ section.category.name }} ({{ formatNumber(total) }}) <AppIcon name="arrow-left" :size="16" />
      </RouterLink>
    </header>

    <article v-if="product" class="spot" :aria-label="`العطر المميز: ${product.name}`">
      <figure class="t photo framed">
        <img :src="pic.src" :srcset="pic.srcset" sizes="(min-width: 1024px) 48vw, 100vw" alt="" loading="lazy" decoding="async" />
        <figcaption class="badge">العطر المميز</figcaption>
      </figure>

      <div class="t name framed" :style="{ background: pal.bg, color: pal.fg }">
        <span v-if="preview && sp.sample" class="self-start rounded-full border border-dashed px-2 py-0.5 text-[0.75rem]" :style="{ borderColor: pal.muted, color: pal.muted }">هوية تجريبية · تُحدَّد من لوحة التحكم</span>
        <span class="text-[0.8125rem]" :style="{ color: pal.muted }">مستوحى من {{ product.inspiredBy }}</span>
        <h3 class="m-0 font-display text-[clamp(2.2rem,4vw,3.6rem)] font-bold leading-[1.2]">{{ product.name }}</h3>
        <p class="m-0 max-w-[36ch] text-[0.9375rem] leading-relaxed">{{ sp.tagline }}</p>
      </div>

      <dl v-if="layers.length" class="layers m-0">
        <div v-for="([label, notes, when], i) in layers" :key="label" class="t layer framed" :style="{ background: LAYER_COLORS[i] }">
          <dt class="text-[0.75rem]">{{ label }} · {{ when }}</dt>
          <dd class="m-0 font-display text-[1.125rem] font-bold leading-tight">{{ notes.join(' و') }}</dd>
        </div>
      </dl>

      <div class="t buy framed">
        <fieldset class="sizes m-0 border-0 p-0">
          <legend class="sr-only">اختر الحجم</legend>
          <label v-for="v in product.variants" :key="v.ml" :class="{ 'is-on': size === v.ml, 'is-out': v.stock <= 0 }">
            <input v-model="size" class="sr-only" type="radio" :name="`${id}-size`" :value="v.ml" :disabled="v.stock <= 0" />
            <span class="tabular">{{ formatMl(v.ml) }}</span>
          </label>
        </fieldset>
        <div class="flex flex-wrap items-center gap-3">
          <button class="btn-pill" :disabled="!variant || variant.stock <= 0" @click="add(product, size)">
            أضف · <span class="tabular">{{ variant ? formatPrice(variant.price) : 'نفد' }}</span>
          </button>
          <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" class="inline-block py-2.5 text-[0.875rem] text-ink underline">تفاصيل العطر</RouterLink>
        </div>
      </div>
    </article>

    <p v-else class="m-0 max-w-[48ch] text-base text-muted md:text-[1.125rem]">
      {{ formatNumber(total) }} عطراً في هذا القسم. اختر بالنوتة التي تحبها، أو ابدأ بمجموعة الاكتشاف.
    </p>

    <ProductSlider class="mt-4" :products="section.others" :palette="pal" :title="product ? `عطور أخرى في ${section.category.name}` : `عطور ${section.category.name}`" />
  </section>
</template>

<style scoped>
/* desktop: the photograph takes the left half; name, layers and buy stack beside it */
.spot { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); grid-auto-rows: 118px; gap: 12px; }
.t { border-radius: var(--radius-tile); padding: 20px; position: relative; overflow: hidden; color: var(--color-ink); }
.photo { grid-column: span 3; grid-row: span 4; margin: 0; padding: 0; background: var(--color-sand); }
.photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 1.2s var(--ease-ink); }
.photo:hover img { transform: scale(1.04); }
.badge { position: absolute; top: 16px; inset-inline-start: 16px; padding: 6px 14px; border-radius: 999px; background: var(--color-paper); font-size: 0.8125rem; font-weight: 600; }
.name { grid-column: span 3; grid-row: span 2; display: flex; flex-direction: column; justify-content: flex-end; gap: 6px; padding: 24px 26px; }
.layers { grid-column: span 3; grid-row: span 1; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.layer { display: flex; flex-direction: column; justify-content: space-between; padding: 14px 16px; }
.buy { grid-column: span 3; grid-row: span 1; background: var(--color-paper); display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; flex-wrap: wrap; }
.sizes { display: flex; flex-wrap: wrap; gap: 4px; }
.sizes label { display: inline-grid; place-items: center; min-width: 54px; height: 40px; padding: 0 12px; border-radius: 999px; background: var(--color-ivory); font-size: 0.875rem; cursor: pointer; transition: background 0.2s; }
.sizes label.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.sizes label.is-out { opacity: 0.4; cursor: not-allowed; text-decoration: line-through; }
.sizes label:has(input:focus-visible) { outline: 2px solid var(--color-ink); outline-offset: 2px; }

/* tablet and phone: photograph, name, layers and buy one under another */
@media (max-width: 1023px) {
  .spot { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: auto; gap: 10px; }
  .photo { grid-column: span 2; aspect-ratio: 4 / 3; }
  .name { grid-column: span 2; min-height: 200px; }
  .layers { grid-column: span 2; gap: 10px; }
  .layer { min-height: 110px; padding: 12px; }
  .buy { grid-column: span 2; }
}
@media (prefers-reduced-motion: reduce) { .photo img { transition: none; } .photo:hover img { transform: none; } }
</style>
