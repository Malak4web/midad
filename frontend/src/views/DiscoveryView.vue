<script setup>
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue'
import { useRouter } from 'vue-router'
import PageHeading from '@/components/layout/PageHeading.vue'
import SampleVial from '@/components/brand/SampleVial.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { api } from '@/services/api'
import { getHome } from '@/services/home'
import { useCartStore } from '@/stores/cart'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'
import { SCENES } from '@/data/photos'
import { familiesOf } from '@/utils/scenes'
import { matches } from '@/utils/search'
import { formatNumber, formatPrice } from '@/utils/format'

// FE-26, «العلبة الحيّة»: every perfume is a tile in its family's tone; picking
// one drops its vial into the open Midad box beside the list, so the box fills
// as you choose. Search and filters stay pinned while the list scrolls. On a
// phone the box becomes a bar above the tab bar.
const NEED = 6
const id = useId()
const cart = useCartStore()
const ui = useUiStore()
const router = useRouter()
const all = ref([])
const price = ref(null)
const saving = ref(false)
const picked = ref([])
const justAdded = ref(null)
const q = ref('')
const cat = ref('all')
const fam = ref('all')
const boxOpen = ref(false)
// phone: smaller vials in the bar
const phone = ref(typeof matchMedia !== 'undefined' && matchMedia('(max-width: 1023px)').matches)
let mq
const onMq = (e) => (phone.value = e.matches)
onMounted(() => {
  mq = matchMedia('(max-width: 1023px)')
  mq.addEventListener('change', onMq)
})
onBeforeUnmount(() => mq?.removeEventListener('change', onMq))

onMounted(async () => {
  const [list, home] = await Promise.all([api.products({ per_page: 48 }), getHome()])
  all.value = list.data.map((p) => ({ ...p, family: familiesOf(p)[0] }))
  price.value = home.discovery.price
})

const CATS = [['all', 'الكل'], ['men', 'رجالي'], ['women', 'نسائي'], ['oriental', 'شرقي ونيش']]
// only the families the catalogue actually has, in a fixed order
const FAMS = computed(() => ['gourmand', 'amber', 'floral', 'fresh', 'oriental', 'woody'].filter((f) => all.value.some((p) => p.family === f)))
const shown = computed(() =>
  all.value.filter(
    (p) =>
      (cat.value === 'all' || p.category === cat.value) &&
      (fam.value === 'all' || p.family === fam.value) &&
      matches([p.name, p.latin, p.inspiredBy, p.signature, ...(p.notes || [])], q.value),
  ),
)
const filtered = computed(() => q.value.trim() || cat.value !== 'all' || fam.value !== 'all')
const left = computed(() => NEED - picked.value.length)
const isPicked = (p) => picked.value.some((x) => x.id === p.id)

function toggle(p) {
  const i = picked.value.findIndex((x) => x.id === p.id)
  justAdded.value = null
  if (i >= 0) picked.value.splice(i, 1)
  else if (picked.value.length < NEED) {
    picked.value.push(p)
    justAdded.value = p.id
  } else ui.toast('اخترت ستّة بالفعل. أزل عينة لتضيف غيرها.')
}
function reset() {
  q.value = ''
  cat.value = 'all'
  fam.value = 'all'
}
async function addBox() {
  saving.value = true
  try {
    await cart.addBox(picked.value.map((p) => p.id))
    ui.toast('أُضيفت مجموعة الاكتشاف إلى السلة', { action: { label: 'إتمام الطلب', run: () => router.push({ name: 'checkout' }) } })
    picked.value = []
    boxOpen.value = false
  } catch (e) {
    ui.toast(firstError(e))
  } finally {
    saving.value = false
  }
}
const tone = (p) => SCENES[p.family] || SCENES.amber
</script>

<template>
  <main id="main">
    <PageHeading title="ابدأ بستّة" :lead="`اختر ست عينات من ٢ مل. جرّبها على مهلك، وثمن العلبة${price ? ` (${formatPrice(price)})` : ''} يعود لك رصيداً على أول زجاجة.`" />

    <div class="container-midad lay pb-52 pt-3 lg:pb-20">
      <section aria-labelledby="pick-title" class="min-w-0">
        <h2 id="pick-title" class="sr-only">اختر عيّناتك</h2>

        <!-- search and filters stay in reach while the list scrolls -->
        <div class="bar framed">
          <form role="search" class="search" @submit.prevent>
            <label :for="`${id}-q`" class="sr-only">ابحث باسم العطر، أو الأصلي، أو نوتة</label>
            <AppIcon name="search" :size="18" class="shrink-0 text-muted" />
            <input :id="`${id}-q`" v-model="q" type="search" autocomplete="off" placeholder="ابحث باسم العطر، أو الأصلي، أو نوتة… «فانيليا»" />
            <button v-if="q" type="button" class="clear" aria-label="مسح البحث" @click="q = ''"><AppIcon name="close" :size="16" /></button>
          </form>
          <div class="rail flex items-center gap-2 lg:flex-wrap" role="group" aria-label="القسم">
            <button v-for="[v, l] in CATS" :key="v" class="chip" :class="cat === v && 'is-on'" :aria-pressed="cat === v" @click="cat = v">{{ l }}</button>
            <span class="sep" aria-hidden="true" />
            <button v-for="f in FAMS" :key="f" class="chip" :class="fam === f && 'is-on'" :aria-pressed="fam === f" @click="fam = fam === f ? 'all' : f">
              <i class="dot" :style="{ background: SCENES[f].color }" aria-hidden="true" />{{ SCENES[f].label }}
            </button>
          </div>
          <p class="m-0 flex items-center gap-3 text-[0.8125rem] text-muted" aria-live="polite">
            <span class="tabular">{{ formatNumber(shown.length) }} عطراً</span>
            <button v-if="filtered" class="underline" @click="reset">إلغاء الفلاتر</button>
          </p>
        </div>

        <ul class="grid-list m-0 list-none p-0" :aria-busy="!all.length">
          <li v-for="p in shown" :key="p.id">
            <button class="pick framed" :class="[isPicked(p) && 'is-on', tone(p).dark && 'is-dark']" :style="{ background: tone(p).color }" :aria-pressed="isPicked(p)" @click="toggle(p)">
              <SampleVial :family="p.family" :size="18" class="vial" />
              <span class="tick" aria-hidden="true"><AppIcon v-if="isPicked(p)" name="check" :size="14" /></span>
              <span class="font-display text-[1.3rem] font-bold leading-tight">{{ p.name }}</span>
              <span class="text-[0.8125rem] opacity-80">مستوحى من {{ p.inspiredBy }}</span>
              <span class="text-[0.8125rem]">{{ p.signature }}</span>
            </button>
          </li>
          <template v-if="!all.length"><li v-for="n in 12" :key="n" class="h-40 animate-pulse rounded-[24px] bg-paper" aria-hidden="true" /></template>
        </ul>
        <div v-if="all.length && !shown.length" class="empty framed">
          <p class="m-0 font-display text-2xl font-bold">لا يوجد عطر بهذا الوصف.</p>
          <p class="m-0 text-muted">جرّب اسم العطر الأصلي، أو نوتة مثل «عود» أو «فانيليا».</p>
          <button class="btn-pill self-start" @click="reset">اعرض كل العطور</button>
        </div>
      </section>

      <!-- the open box: fills as you pick -->
      <aside class="box-col" :class="boxOpen && 'is-open'" aria-label="علبتك">
        <div class="box-card framed">
          <div class="top flex items-baseline justify-between gap-3">
            <h2 class="m-0 font-display text-2xl font-bold">علبتك</h2>
            <span class="text-[0.875rem] text-sand tabular" aria-live="polite">{{ formatNumber(picked.length) }} / {{ formatNumber(NEED) }}</span>
            <button class="toggle lg:hidden" :aria-expanded="boxOpen" @click="boxOpen = !boxOpen">{{ boxOpen ? 'إخفاء' : 'عرض' }}</button>
          </div>
          <div class="box">
            <span class="lid">MIDAD · DISCOVERY</span>
            <ol class="slots m-0 list-none p-0">
              <li v-for="i in NEED" :key="i" class="slot">
                <button v-if="picked[i - 1]" class="slot-btn" :class="justAdded === picked[i - 1].id && 'pop'" :aria-label="`إزالة ${picked[i - 1].name}`" @click="toggle(picked[i - 1])">
                  <SampleVial :family="picked[i - 1].family" :size="phone ? 15 : 22" />
                </button>
                <SampleVial v-else :size="phone ? 15 : 22" />
              </li>
            </ol>
          </div>
          <ol class="names m-0 list-none p-0">
            <li v-for="p in picked" :key="p.id">
              <span class="truncate font-display text-[1.0625rem]">{{ p.name }}</span>
              <button :aria-label="`إزالة ${p.name}`" @click="toggle(p)">إزالة</button>
            </li>
            <li v-if="!picked.length" class="opacity-70">اختر أول عطر من البلاطات.</li>
          </ol>
          <p class="hint m-0 text-[0.8125rem] text-sand">نصيحة خبير العطور: عطران تعرف أنك تحبهما، وأربعة لم تجرّبها قط.</p>
          <button class="add btn-pill is-gold w-full disabled:opacity-40" :disabled="left > 0 || saving" @click="addBox">
            {{ saving ? 'جارٍ الإضافة…' : left > 0 ? `أضف العلبة · باقي ${formatNumber(left)}` : `أضف العلبة${price ? ` · ${formatPrice(price)}` : ''}` }}
          </button>
        </div>
      </aside>
    </div>
  </main>
</template>

<style scoped>
.lay { display: grid; gap: 12px; grid-template-columns: minmax(0, 1fr) 340px; align-items: start; }

/* pinned search and filters */
.bar { position: sticky; top: 92px; z-index: 20; display: flex; flex-direction: column; gap: 10px; padding: 14px; margin-bottom: 12px; border-radius: var(--radius-tile); background: color-mix(in srgb, var(--color-paper) 92%, transparent); backdrop-filter: blur(10px); box-shadow: 0 14px 28px -24px rgba(14, 13, 12, 0.6); }
.search { display: flex; align-items: center; gap: 10px; height: 52px; padding: 0 16px; border-radius: 999px; background: var(--color-ivory); box-shadow: inset 0 0 0 1px var(--color-sand); }
.search:focus-within { box-shadow: inset 0 0 0 2px var(--color-ink); }
.search input { flex: 1; min-width: 0; height: 100%; border: 0; background: none; outline: none; font-size: 1rem; }
/* the pill shows focus for the field */
.search input:focus-visible { outline: none; box-shadow: none; }
.search input::placeholder { color: var(--color-muted); }
.clear { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 999px; background: var(--color-linen); }
/* the bar already spans the screen: its rail must not reach past it */
.bar .rail { margin-inline: 0; padding-inline: 0; }
.chip { display: inline-flex; align-items: center; gap: 6px; min-height: 40px; padding: 6px 14px; border-radius: 999px; background: var(--color-ivory); font-size: 0.875rem; white-space: nowrap; transition: background 0.2s; }
.chip:hover { background: var(--color-linen); }
.chip.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.dot { width: 10px; height: 10px; border-radius: 50%; box-shadow: inset 0 0 0 1px rgba(14, 13, 12, 0.25); }
.sep { width: 1px; height: 24px; background: var(--color-sand); flex: none; }

/* the perfumes */
.grid-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; }
.pick { position: relative; display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-end; gap: 3px; width: 100%; height: 100%; min-height: 168px; padding: 16px; border-radius: var(--radius-tile); text-align: start; color: var(--color-ink); transition: transform 0.25s var(--ease-pop), box-shadow 0.2s; }
.pick:hover { transform: translateY(-3px); }
.pick.is-dark { color: var(--color-ivory); }
.pick.is-on { box-shadow: inset 0 0 0 2px var(--color-ink); }
.pick.is-dark.is-on { box-shadow: inset 0 0 0 2px var(--color-gold-light); }
.pick .vial { position: absolute; top: 16px; inset-inline-end: 16px; }
.tick { position: absolute; top: 14px; inset-inline-start: 14px; display: grid; place-items: center; width: 26px; height: 26px; border-radius: 999px; box-shadow: inset 0 0 0 1.5px currentColor; opacity: 0.55; }
.pick.is-on .tick { background: var(--color-ink); color: var(--color-gold-light); box-shadow: none; opacity: 1; }
.pick.is-dark.is-on .tick { background: var(--color-gold-light); color: var(--color-ink); }
.empty { display: flex; flex-direction: column; gap: 10px; padding: 30px; border-radius: var(--radius-tile); background: var(--color-paper); }

/* the box */
.box-col { position: sticky; top: 92px; }
.box-card { display: flex; flex-direction: column; gap: 14px; padding: 22px; border-radius: var(--radius-tile); background: var(--color-ink); color: var(--color-ivory); }
.box { padding: 14px; border-radius: 16px; background: linear-gradient(180deg, #1b1714, #0b0908); box-shadow: inset 0 0 0 1px rgba(214, 183, 126, 0.35); }
.lid { display: block; margin-bottom: 10px; text-align: center; font-family: var(--font-latin); font-size: 10px; letter-spacing: 0.3em; color: var(--color-gold-light); }
.slots { display: grid; grid-template-columns: repeat(6, 1fr); align-items: end; gap: 6px; min-height: 108px; padding: 10px 6px; border-radius: 10px; background: #f4eada; box-shadow: inset 0 4px 10px rgba(0, 0, 0, 0.25); }
.slot { display: flex; justify-content: center; }
.slot-btn { display: block; padding: 0; background: none; border: 0; cursor: pointer; }
.pop { animation: pop 0.45s var(--ease-pop); }
@keyframes pop { 0% { transform: translateY(-30px) scale(0.5); opacity: 0; } 100% { transform: none; opacity: 1; } }
.names { display: flex; flex-direction: column; gap: 4px; min-height: 40px; font-size: 0.875rem; color: var(--color-sand); }
.names li { display: flex; justify-content: space-between; gap: 10px; }
.names button { flex: none; color: var(--color-gold-light); font-size: 0.75rem; }
.toggle { margin-inline-start: auto; min-height: 44px; padding: 0 10px; color: var(--color-gold-light); font-size: 0.875rem; text-decoration: underline; }

/* phone: filters pin to the top; the box is a bar above the tab bar that opens */
@media (max-width: 1023px) {
  .lay { grid-template-columns: 1fr; }
  /* pinned, the filters sit over the app bar: they are what the visitor needs here */
  .bar { top: 0; z-index: 31; margin-inline: calc(-1 * clamp(16px, 4vw, 48px)); border-radius: 0 0 22px 22px; padding-top: calc(14px + env(safe-area-inset-top, 0px)); }
  .bar::before { display: none; }
  .box-col { position: fixed; inset: auto 10px calc(100px + env(safe-area-inset-bottom, 0px)); z-index: 25; }
  /* one compact row: the vials, then the button; the title row and the list open on demand */
  .box-card { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 6px 10px; padding: 8px 12px 10px; border-radius: 18px; box-shadow: 0 -10px 30px -12px rgba(14, 13, 12, 0.55); }
  .box-card::before { display: none; }
  .top { grid-column: 1 / -1; align-items: center; }
  .top h2 { font-size: 1rem; line-height: 1.3; }
  .top .toggle { min-height: 32px; font-size: 0.8125rem; }
  .box { padding: 4px; }
  .lid, .hint, .names { display: none; }
  .slots { min-height: 0; padding: 6px 4px; gap: 2px; }
  .add { width: auto; min-height: 44px; padding: 0 14px; font-size: 0.8125rem; }
  .bar { gap: 8px; padding: calc(10px + env(safe-area-inset-top, 0px)) 16px 10px; }
  .search { height: 44px; padding: 0 14px; }
  .search input { font-size: 0.875rem; }
  .chip { min-height: 36px; padding: 4px 12px; font-size: 0.8125rem; }
  .grid-list { grid-template-columns: 1fr 1fr; gap: 8px; }
  .pick { min-height: 128px; padding: 12px; gap: 1px; border-radius: 18px; --radius-tile: 18px; }
  .pick .vial { top: 12px; inset-inline-end: 12px; }
  .tick { top: 10px; inset-inline-start: 10px; width: 24px; height: 24px; }
  .pick > span:nth-of-type(n) { font-size: 0.75rem; }
  .pick .font-display { font-size: 1.0625rem !important; line-height: 1.3; }
  .is-open .names, .is-open .hint { display: flex; grid-column: 1 / -1; }
}
@media (prefers-reduced-motion: reduce) { .pick:hover { transform: none; } .pop { animation: none; } }
</style>
