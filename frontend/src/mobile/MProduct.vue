<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import MTopBar from './MTopBar.vue'
import MProductCard from './MProductCard.vue'
import { useAddToCart } from '@/composables/useAddToCart'
import { api } from '@/services/api'
import { photo as brandPhoto, SCENES, WORLDS } from '@/data/photos'
import { familiesOf } from '@/utils/scenes'
import { formatMl, formatNumber, formatPrice } from '@/utils/format'

// A perfume on the phone: the photos to swipe, the name, its three layers as
// small tiles, the sizes as chips, and the price with the add button pinned
// above the tab bar the whole time.
const route = useRoute()
const add = useAddToCart()
const product = ref(null)
const similar = ref([])
const error = ref('')
const size = ref(50)
const at = ref(0)
const adding = ref(false)
const aboutOpen = ref(false)

async function load() {
  error.value = ''
  product.value = null
  at.value = 0
  try {
    const res = await api.product(route.params.slug)
    product.value = res.data
    similar.value = res.meta.similar || []
    const ok = res.data.variants.find((v) => v.ml === 50 && v.stock > 0) || res.data.variants.find((v) => v.stock > 0)
    size.value = ok?.ml ?? 50
    document.title = `${res.data.name} | مداد`
  } catch (e) {
    error.value = e.status === 404 ? 'هذا العطر غير موجود أو لم يعد متاحاً.' : e.message
  }
}
watch(() => route.params.slug, (s) => s && route.name === 'product' && load(), { immediate: true })

const photos = computed(() => {
  const own = product.value?.images || []
  if (own.length) return own
  const w = WORLDS[product.value?.category]
  return [
    { ...brandPhoto(w?.photo || 'world-men'), focus: `${(w?.focus?.[0] ?? 0.5) * 100}% 50%` },
    { ...brandPhoto('studio-dark') },
    { ...brandPhoto('hero-sizes'), focus: '28% 50%' },
  ].filter((p) => p.src)
})
const variant = computed(() => product.value?.variants.find((v) => v.ml === size.value))
const tone = computed(() => (product.value ? SCENES[familiesOf(product.value)[0]] : null))
const layers = computed(() => {
  const p = product.value?.pyramid
  if (!p) return []
  return [['القمة', p.top], ['القلب', p.heart], ['القاعدة', p.base]].filter(([, n]) => n?.length)
})
const about = computed(() => {
  const p = product.value
  if (!p) return ''
  if (p.description) return p.description
  const first = p.pyramid?.top?.join(' و') || p.notes?.[0]
  const last = p.pyramid?.base?.join(' و') || p.notes?.at(-1)
  return `${p.name} عطرٌ ${p.signature}${first && last ? `، تبدأ رائحته بـ${first}، ويستقر على ${last}` : ''}. تركيزه ماء عطر لثبات طويل.`
})
function onScroll(e) {
  const el = e.target
  at.value = Math.round(Math.abs(el.scrollLeft) / el.clientWidth)
}
async function addToCart() {
  adding.value = true
  await add(product.value, size.value, 1)
  adding.value = false
}
</script>

<template>
  <main id="main" class="m">
    <MTopBar>
      <template #title><span class="lat">{{ product?.latin?.toUpperCase() }}</span></template>
    </MTopBar>

    <div v-if="error" class="px-4 py-10"><h1 class="m-0 font-display text-[1.375rem] font-bold leading-[1.3]">{{ error }}</h1><RouterLink to="/" class="mt-4 inline-block underline">تصفّح العطور</RouterLink></div>
    <div v-else-if="!product" class="px-4" aria-busy="true"><div class="h-72 animate-pulse rounded-3xl bg-paper" /><div class="mt-3 h-24 animate-pulse rounded-2xl bg-paper" /></div>

    <article v-else class="pv">
      <div class="gal" :style="{ background: tone?.color }">
        <ul class="slides m-0 list-none p-0" tabindex="0" aria-label="صور العطر" @scroll.passive="onScroll">
          <li v-for="(ph, i) in photos" :key="ph.src"><img :src="ph.src" :srcset="ph.srcset" sizes="100vw" :alt="i ? '' : product.name" :style="{ objectPosition: ph.focus || '50% 50%' }" :fetchpriority="i ? undefined : 'high'" /></li>
        </ul>
        <span v-if="photos.length > 1" class="dots" aria-hidden="true"><i v-for="(ph, i) in photos" :key="i" :class="i === at && 'on'" /></span>
      </div>

      <div>
        <h1 class="m-0 font-display text-[1.5rem] font-bold leading-[1.3]">{{ product.name }}</h1>
        <p class="m-0 text-[0.8125rem] text-muted">مستوحى من {{ product.inspiredBy }} · {{ product.signature }}</p>
      </div>

      <ul v-if="layers.length" class="tiles m-0 list-none p-0" aria-label="الهرم العطري">
        <li v-for="[l, n] in layers" :key="l"><small>{{ l }}</small><b class="font-display">{{ n.join(' و') }}</b></li>
      </ul>

      <fieldset class="sizes m-0 border-0 p-0">
        <legend class="mb-2 p-0 text-[0.8125rem] text-muted">اختر الحجم</legend>
        <div class="row">
          <label v-for="v in product.variants" :key="v.ml" :class="[size === v.ml && 'is-on', v.stock <= 0 && 'is-out']">
            <input v-model="size" class="sr-only" type="radio" name="m-size" :value="v.ml" :disabled="v.stock <= 0" />
            <b class="tabular">{{ formatMl(v.ml) }}</b>
            <small class="tabular">{{ v.stock > 0 ? formatPrice(v.price) : 'نفد' }}</small>
          </label>
        </div>
      </fieldset>

      <ul class="perks m-0 list-none p-0">
        <li><AppIcon name="cash" :size="16" />الدفع عند الاستلام</li>
        <li><AppIcon name="truck" :size="16" />توصيل لكل المحافظات</li>
      </ul>

      <section class="about">
        <button class="ab" :aria-expanded="aboutOpen" @click="aboutOpen = !aboutOpen"><span class="font-display text-[1.0625rem] font-bold">عن العطر</span><AppIcon :name="aboutOpen ? 'minus' : 'plus'" :size="16" /></button>
        <p v-if="aboutOpen" class="m-0 text-[0.875rem] leading-[1.8] text-lead">{{ about }}</p>
      </section>

      <section v-if="similar.length" aria-labelledby="m-sim">
        <h2 id="m-sim" class="m-0 mb-2 font-display text-[1.125rem] font-bold leading-[1.3]">قد يعجبك أيضاً</h2>
        <ul class="rail m-0 list-none p-0">
          <li v-for="p in similar" :key="p.slug"><MProductCard :product="p" /></li>
        </ul>
      </section>
    </article>

    <div v-if="product" class="buybar">
      <span class="price"><small>{{ formatMl(size) }}</small><b class="tabular">{{ variant ? formatPrice(variant.price) : '' }}</b></span>
      <button class="go" :disabled="!variant || variant.stock <= 0 || adding" @click="addToCart">
        <AppIcon name="bag" :size="18" /> {{ variant && variant.stock <= 0 ? 'نفد هذا الحجم' : adding ? 'جارٍ الإضافة…' : 'أضف إلى السلة' }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.m { padding-bottom: 130px; font-size: 0.875rem; }
.lat { font-family: var(--font-latin); font-size: 0.6875rem; letter-spacing: 0.2em; color: var(--color-gold-ink); }
.pv { display: flex; flex-direction: column; gap: 14px; padding: 0 16px; }
.gal { position: relative; height: 300px; border-radius: 22px; overflow: hidden; }
.slides { display: flex; height: 100%; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; }
.slides:focus-visible { outline: 2px solid var(--color-ink); outline-offset: -4px; border-radius: 22px; }
.slides::-webkit-scrollbar { display: none; }
.slides li { flex: none; width: 100%; height: 100%; scroll-snap-align: center; }
.slides img { width: 100%; height: 100%; object-fit: cover; }
.dots { position: absolute; bottom: 10px; left: 50%; display: flex; gap: 5px; transform: translateX(-50%); }
.dots i { width: 6px; height: 6px; border-radius: 99px; background: rgba(251, 247, 238, 0.6); transition: width 0.2s; }
.dots i.on { width: 16px; background: var(--color-paper); }
.tiles { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; }
.tiles li { display: flex; flex-direction: column; padding: 8px 10px; border-radius: 12px; background: var(--color-paper); }
.tiles small { font-size: 0.6875rem; color: var(--color-muted); }
.tiles b { font-size: 0.875rem; line-height: 1.35; }
.sizes .row { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; }
.sizes label { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 50px; border-radius: 12px; background: var(--color-paper); line-height: 1.25; cursor: pointer; }
.sizes b { font-size: 0.8125rem; }
.sizes small { font-size: 0.6875rem; color: var(--color-muted); }
.sizes label.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.sizes label.is-on small { color: var(--color-sand); }
.sizes label.is-out { opacity: 0.4; }
.sizes label:has(input:focus-visible) { outline: 2px solid var(--color-ink); outline-offset: 2px; }
.perks { display: flex; gap: 6px; flex-wrap: wrap; font-size: 0.75rem; }
.perks li { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: var(--color-linen); }
.about { padding: 4px 14px 10px; border-radius: 14px; background: var(--color-paper); }
.ab { display: flex; justify-content: space-between; align-items: center; width: 100%; min-height: 44px; }
.rail { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; margin-inline: -16px; padding-inline: 16px; }
.rail::-webkit-scrollbar { display: none; }
.rail li { flex: none; width: 44%; }
/* the price and the add button, always in reach above the tab bar */
.buybar { position: fixed; inset-inline: 12px; bottom: calc(100px + env(safe-area-inset-bottom, 0px)); z-index: 35; display: grid; grid-template-columns: auto 1fr; gap: 8px; }
.price { display: flex; flex-direction: column; justify-content: center; min-width: 96px; height: 50px; padding: 0 14px; border-radius: 14px; background: var(--color-paper); line-height: 1.2; box-shadow: 0 8px 20px -10px rgba(14, 13, 12, 0.4); }
.price small { font-size: 0.6875rem; color: var(--color-muted); }
.price b { font-size: 1rem; }
.go { display: flex; align-items: center; justify-content: center; gap: 8px; height: 50px; border-radius: 14px; background: var(--color-ink); color: var(--color-gold-light); font-size: 0.9375rem; font-weight: 600; box-shadow: 0 8px 20px -10px rgba(14, 13, 12, 0.6); }
.go:disabled { opacity: 0.5; }
</style>
