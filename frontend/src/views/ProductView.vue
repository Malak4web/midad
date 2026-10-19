<script setup>
import AttarCameo from '@/components/attar/AttarCameo.vue'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import SizePicker from '@/components/ui/SizePicker.vue'
import ScentPyramid from '@/components/home/ScentPyramid.vue'
import ProductSlider from '@/components/home/ProductSlider.vue'
import { useAddToCart } from '@/composables/useAddToCart'
import { api } from '@/services/api'
import { formatMl, formatNumber, formatPrice } from '@/utils/format'
import { PAPER } from '@/utils/palettes'
import { photo as brandPhoto, SCENES, WORLDS } from '@/data/photos'
import { familiesOf } from '@/utils/scenes'

// FE-24. The product page as a bento: the gallery on its scent colour, the facts
// in paper tiles beside it, the pyramid and the perfume expert on sand.
const route = useRoute()
const product = ref(null)
const similar = ref([])
const error = ref('')
const size = ref(50)
const qty = ref(1)
const adding = ref(false)
const add = useAddToCart()

// The gallery: the product's own uploaded photos; until it has some, the brand
// photographs: its category's world, the studio bottle, the sizes, the spray.
const photoAt = ref(0)
const photos = computed(() => {
  const own = product.value?.images || []
  if (own.length) return own
  const world = WORLDS[product.value?.category]?.photo || 'world-men'
  return [
    { ...brandPhoto(world), alt: '', focus: `${(WORLDS[product.value?.category]?.focus?.[0] ?? 0.5) * 100}% 50%` },
    { ...brandPhoto('studio-dark'), alt: '' },
    { ...brandPhoto('hero-sizes'), alt: 'الأحجام الأربعة', focus: '28% 50%' },
    { ...brandPhoto('spray-wrist'), alt: '' },
  ].filter((p) => p.src)
})
const photo = computed(() => photos.value[photoAt.value] || photos.value[0])
const tone = computed(() => (product.value ? SCENES[familiesOf(product.value)[0]]?.color : null) || 'var(--color-gold-light)')
const variant = computed(() => product.value?.variants.find((v) => v.ml === size.value))
const about = computed(() => {
  const p = product.value
  if (!p) return ''
  if (p.description) return p.description
  const first = p.pyramid?.top?.join(' و') || p.notes?.[0]
  const last = p.pyramid?.base?.join(' و') || p.notes?.at(-1)
  const lead = p.signature ? `${p.name} عطرٌ ${p.signature}` : p.name
  const arc = first && last ? `، تبدأ رائحته بـ${first}، ويستقر على ${last}` : ''
  return `${lead}${arc}. تركيزه ماء عطر لثبات طويل على البشرة والملابس.`
})

async function load() {
  error.value = ''
  product.value = null
  photoAt.value = 0
  try {
    const res = await api.product(route.params.slug)
    product.value = res.data
    similar.value = res.meta.similar
    const firstInStock = res.data.variants.find((v) => v.ml === 50 && v.stock > 0) || res.data.variants.find((v) => v.stock > 0)
    size.value = firstInStock?.ml ?? 50
    qty.value = 1
    document.title = `${res.data.name} | مداد`
  } catch (e) {
    error.value = e.status === 404 ? 'هذا العطر غير موجود أو لم يعد متاحاً.' : e.message
  }
}
async function addToCart() {
  adding.value = true
  await add(product.value, size.value, qty.value)
  adding.value = false
}
watch(() => route.params.slug, (s) => s && load(), { immediate: true })

// phone: once the main add-to-cart scrolls away, a buy bar keeps it in reach
const buyRow = ref(null)
const picker = ref(null)
const showBar = ref(false)
// a scroll check (one measure per frame) rather than an IntersectionObserver:
// it behaves the same in every engine, Safari included
let ticking = false
function checkBar() {
  if (ticking) return
  ticking = true
  requestAnimationFrame(() => {
    ticking = false
    // whenever the add button is out of view: below the photos at first, or scrolled past
    const r = buyRow.value?.getBoundingClientRect()
    showBar.value = !!r && (r.bottom < 0 || r.top > innerHeight - 80)
  })
}
onMounted(() => {
  addEventListener('scroll', checkBar, { passive: true })
  checkBar()
})
watch(product, () => requestAnimationFrame(checkBar))
onBeforeUnmount(() => removeEventListener('scroll', checkBar))
function toSizes() {
  picker.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}
</script>

<template>
  <main id="main">
    <section class="container-midad pt-3 md:pt-4">
      <div v-if="error" class="tile-pad framed flex min-h-[40vh] flex-col items-start justify-center gap-4 bg-gold-light">
        <h1 class="m-0 font-display text-[2rem] font-bold md:text-4xl">{{ error }}</h1>
        <RouterLink to="/" class="btn-pill">تصفّح العطور</RouterLink>
      </div>

      <div v-else-if="!product" class="grid gap-3 lg:grid-cols-2" aria-busy="true">
        <div class="aspect-square animate-pulse rounded-[28px] bg-paper" />
        <div class="flex flex-col gap-3"><div class="h-72 animate-pulse rounded-[28px] bg-paper" /><div class="h-40 animate-pulse rounded-[28px] bg-paper" /></div>
      </div>

      <article v-else class="grid items-start gap-3 lg:grid-cols-[1fr_1fr]">
        <figure class="tile-pad framed relative m-0 flex flex-col gap-3 lg:sticky lg:top-24" :style="{ background: tone }">
          <div class="relative aspect-square overflow-hidden rounded-[22px]">
            <img
              v-if="photo"
              :key="photo.src"
              :src="photo.src"
              :srcset="photo.srcset"
              sizes="(min-width: 1024px) 560px, 92vw"
              :alt="photo.alt || product.name"
              fetchpriority="high"
              class="h-full w-full object-cover"
              :style="{ objectPosition: photo.focus || '50% 50%' }"
            />
          </div>
          <div v-if="photos.length > 1" class="flex justify-center gap-2" role="group" aria-label="صور العطر">
            <button v-for="(ph, i) in photos" :key="ph.src" class="h-16 w-16 overflow-hidden rounded-2xl p-0 ring-offset-2 transition" :class="i === photoAt ? 'ring-2 ring-ink' : 'opacity-75 hover:opacity-100'" :aria-pressed="i === photoAt" :aria-label="`صورة ${i + 1}`" @click="photoAt = i">
              <img :src="ph.src" alt="" class="h-full w-full object-cover" :style="{ objectPosition: ph.focus || '50% 50%' }" loading="lazy" />
            </button>
          </div>
          <figcaption class="text-center text-[0.75rem] tracking-[0.18em]" dir="ltr">{{ size }} ML · {{ product.concentration }}</figcaption>
        </figure>

        <div class="flex flex-col gap-3">
          <div class="tile-pad framed flex flex-col gap-5 bg-paper">
            <nav class="flex flex-wrap gap-2 text-[0.8125rem]" aria-label="مسار التنقل">
              <RouterLink to="/" class="rounded-full bg-ivory px-3 py-1.5 text-ink no-underline hover:bg-linen">الرئيسية</RouterLink>
              <RouterLink :to="{ name: 'category', params: { slug: product.category } }" class="rounded-full bg-ivory px-3 py-1.5 text-ink no-underline hover:bg-linen">{{ { men: 'رجالي', women: 'نسائي', oriental: 'شرقي ونيش' }[product.category] }}</RouterLink>
            </nav>
            <div>
              <h1 class="m-0 font-display text-[clamp(2.2rem,5.6vw,4.4rem)] font-bold leading-[1.2]">{{ product.name }}</h1>
              <p class="m-0 mt-2 text-[0.875rem] tracking-[0.14em] text-gold-ink">{{ product.latin.toUpperCase() }}</p>
            </div>
            <p class="m-0 text-base text-lead md:text-[1.0625rem]">مستوحى من {{ product.inspiredBy }} · {{ product.signature }}</p>
            <p class="m-0 font-display text-[clamp(1.6rem,3vw,2.4rem)] font-bold tabular" aria-live="polite">{{ variant ? formatPrice(variant.price) : '' }}<span class="ms-3 font-sans text-base font-normal text-muted">{{ formatMl(size) }}</span></p>

            <SizePicker ref="picker" v-model="size" :variants="product.variants" legend="اختر الحجم" class="max-w-md" />

            <div ref="buyRow" class="flex flex-wrap items-center gap-3">
              <div class="inline-flex h-[52px] items-center rounded-full bg-ivory px-1">
                <button class="grid h-11 w-11 place-items-center rounded-full hover:bg-linen" :disabled="qty <= 1" aria-label="إنقاص الكمية" @click="qty--"><AppIcon name="minus" :size="16" /></button>
                <span class="w-8 text-center tabular" aria-live="polite">{{ formatNumber(qty) }}</span>
                <button class="grid h-11 w-11 place-items-center rounded-full hover:bg-linen" :disabled="qty >= Math.min(10, variant?.stock || 0)" aria-label="زيادة الكمية" @click="qty++"><AppIcon name="plus" :size="16" /></button>
              </div>
              <button class="btn-pill flex-1 sm:flex-none sm:px-9" :disabled="!variant || variant.stock <= 0 || adding" @click="addToCart">
                <AppIcon name="bag" :size="18" /> {{ variant && variant.stock <= 0 ? 'نفد هذا الحجم' : adding ? 'جارٍ الإضافة…' : 'أضف إلى السلة' }}
              </button>
            </div>
            <ul class="m-0 flex list-none flex-wrap gap-2 p-0 text-[0.8125rem]">
              <li class="inline-flex items-center gap-2 rounded-full bg-sand px-3 py-1.5"><AppIcon name="cash" :size="16" />الدفع عند الاستلام</li>
              <li class="inline-flex items-center gap-2 rounded-full bg-sand-deep px-3 py-1.5"><AppIcon name="truck" :size="16" />توصيل لكل المحافظات</li>
              <li class="inline-flex items-center gap-2 rounded-full bg-sand-light px-3 py-1.5"><AppIcon name="gift" :size="16" />تغليف هدية بكارت مكتوب</li>
            </ul>
          </div>

          <div v-if="['top', 'heart', 'base'].some((k) => product.pyramid?.[k]?.length)" class="tile-pad framed bg-sand">
            <h2 class="m-0 mb-2 font-display text-2xl font-bold">الهرم العطري</h2>
            <ScentPyramid :pyramid="product.pyramid" />
          </div>
          <div class="tile-pad framed bg-sand-light">
            <AttarCameo pose="offer" :line="`${product.name} لمن يحب ${product.signature}. لم يقنعك؟ أعرف غيره.`" :to="{ name: 'finder' }" action="اسأل خبير العطور" :size="104" />
          </div>

          <div class="tile-pad framed flex flex-col gap-4 bg-paper">
            <details class="group" open>
              <summary class="cursor-pointer list-none font-display text-2xl font-bold">عن العطر</summary>
              <p class="m-0 mt-2 max-w-[60ch] leading-[1.9] text-lead">{{ about }}</p>
            </details>
            <details class="border-t border-sand pt-4">
              <summary class="cursor-pointer list-none font-display text-2xl font-bold">طريقة الاستخدام والحفظ</summary>
              <ul class="m-0 mt-2 flex flex-col gap-1.5 ps-5 leading-[1.8] text-lead">
                <li>رشّتان على نقاط النبض: الرسغ، خلف الأذن، وأسفل الرقبة، من مسافة ١٥ سم.</li>
                <li>لا تفرك الرسغين بعد الرش؛ اترك العطر يجف وحده ليحتفظ بطبقاته.</li>
                <li>احفظ الزجاجة بعيداً عن الشمس والحرارة والرطوبة، ويُفضّل في علبتها.</li>
                <li>العطر يكتمل بعد أسبوعين من فتح الزجاجة؛ هذا طبيعي.</li>
              </ul>
            </details>
          </div>
        </div>
      </article>
    </section>

    <section v-if="similar.length" class="container-midad py-12">
      <ProductSlider :products="similar" :palette="PAPER" title="قد يعجبك أيضاً" />
    </section>
    <Transition enter-from-class="translate-y-full" leave-to-class="translate-y-full" enter-active-class="transition-transform duration-300 ease-[var(--ease-ink)]" leave-active-class="transition-transform duration-200">
      <div v-if="product && showBar" class="buybar fixed inset-x-3 z-30 flex items-center gap-3 rounded-full bg-ink py-2 pe-2 ps-5 text-ivory shadow-[0_14px_30px_-12px_rgba(28,20,12,.5)] lg:hidden">
        <button class="flex min-w-0 flex-1 flex-col items-start text-start" aria-label="تغيير الحجم" @click="toSizes">
          <span class="max-w-full truncate font-display text-[1.0625rem] font-bold leading-tight">{{ product.name }}</span>
          <span class="text-[0.8125rem] text-sand tabular">{{ formatMl(size) }} · {{ variant ? formatPrice(variant.price) : '' }} <span class="underline">تغيير</span></span>
        </button>
        <button class="btn-pill is-gold h-12 min-h-0 shrink-0 px-5" :disabled="!variant || variant.stock <= 0 || adding" @click="addToCart">
          <AppIcon name="bag" :size="18" /> {{ variant && variant.stock <= 0 ? 'نفد' : 'أضف' }}
        </button>
      </div>
    </Transition>
  </main>
</template>

<style scoped>
.buybar { bottom: calc(68px + env(safe-area-inset-bottom, 0px)); }
.tile-pad { border-radius: var(--radius-tile); padding: clamp(18px, 3vw, 30px); }
details summary::-webkit-details-marker { display: none; }
details summary::after { content: '+'; float: inline-end; opacity: 0.6; }
details[open] summary::after { content: '−'; }
</style>
