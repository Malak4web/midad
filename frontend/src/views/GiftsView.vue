<script setup>
import { computed, onMounted, ref, useId } from 'vue'
import { useRouter } from 'vue-router'
import PageHeading from '@/components/layout/PageHeading.vue'
import SizePicker from '@/components/ui/SizePicker.vue'
import GiftStage from '@/components/gifts/GiftStage.vue'
import { api } from '@/services/api'
import { useCartStore } from '@/stores/cart'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'
import { formatMl, formatNumber, formatPrice } from '@/utils/format'

// FE-28, «العلبة اللي بتتقفل»: four steps (for whom and why, the perfume, the
// card, ready) beside a studio where the gift is made as you go: the bottle
// lowers into the box, the card stands in front of it, the lid comes down.
const MAX = 120
const id = useId()
const cart = useCartStore()
const ui = useUiStore()
const router = useRouter()
const products = ref([])
const step = ref(0)
const who = ref('')
const occasion = ref('birthday')
const slug = ref('')
const size = ref(50)
const to = ref('')
const msg = ref('')
const saving = ref(false)

const WHO = [['her', 'لها', 'نسائي وشرقي'], ['him', 'له', 'رجالي وشرقي'], ['any', 'لأي حد', 'كل العطور']]
const OCCASIONS = [['birthday', 'عيد ميلاد'], ['love', 'خطوبة أو جواز'], ['thanks', 'شكر'], ['success', 'نجاح'], ['eid', 'عيد'], ['any', 'من غير مناسبة']]
const LINES = {
  birthday: ['كل سنة وانت أحلى ريحة في حياتي.', 'سنة جديدة، وأثر جديد يفضل معاك.'],
  love: ['لأن بعض العطور، زيّك، بتفضل.', 'كل يوم معاك عيد.'],
  thanks: ['شكراً على كل حاجة، من قلبي.', 'وجودك فرق معايا.'],
  success: ['مبروك! ده أول عطر لأول نجاح.', 'فخور بيك، والجاي أحلى.'],
  eid: ['كل سنة وانت طيب.', 'عيدك مبارك، وأيامك عطر.'],
  any: ['افتكرتك النهارده.', 'لأنك تستاهل.'],
}
const STEPS = ['لمين', 'العطر', 'الكارت', 'جاهزة']

onMounted(async () => {
  products.value = (await api.products({ per_page: 48 })).data
})
const shown = computed(() => products.value.filter((p) => !who.value || who.value === 'any' || (who.value === 'her' ? p.category !== 'men' : p.category !== 'women')))
const product = computed(() => products.value.find((p) => p.slug === slug.value) || null)
const variant = computed(() => product.value?.variants.find((v) => v.ml === size.value))
const canNext = computed(() => [!!who.value, !!product.value && !!variant.value && variant.value.stock > 0, true, true][step.value])

function chooseWho(w) {
  who.value = w
  // a perfume chosen before stays only if it still fits
  if (product.value && !shown.value.includes(product.value)) slug.value = ''
}
function choose(p) {
  slug.value = p.slug
  const inStock = p.variants.find((v) => v.ml === size.value && v.stock > 0) || p.variants.find((v) => v.ml === 50 && v.stock > 0) || p.variants.find((v) => v.stock > 0)
  if (inStock) size.value = inStock.ml
}
// phone: the studio sits above the steps, so each step brings it into view to watch
const stage = ref(null)
function go(n) {
  if (!(n <= step.value || (n === step.value + 1 && canNext.value))) return
  step.value = n
  if (matchMedia('(max-width: 1023px)').matches) {
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches
    stage.value?.$el?.scrollIntoView({ behavior: calm ? 'auto' : 'smooth', block: 'start' })
  }
}
async function addGift() {
  if (!product.value) return
  saving.value = true
  try {
    await cart.add(product.value.slug, size.value, 1)
    await cart.setGift(true, to.value, msg.value)
    ui.toast('أُضيفت الهدية بكارتها إلى السلة', { action: { label: 'إتمام الطلب', run: () => router.push({ name: 'checkout' }) } })
  } catch (e) {
    ui.toast(firstError(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <main id="main">
    <PageHeading title="هديةٌ تُقرأ قبل أن تُشَمّ" lead="ثلاث خطوات، وهديتك بتتجهّز قدامك: علبة مداد بشريطة دهب وختم، وكارت مطبوع برسالتك." />

    <div class="container-midad lay pb-20 pt-3">
      <section class="min-w-0" aria-labelledby="gift-steps">
        <h2 id="gift-steps" class="sr-only">خطوات تجهيز الهدية</h2>
        <ol class="steps m-0 list-none p-0">
          <li v-for="(l, i) in STEPS" :key="l">
            <button class="step" :class="[step === i && 'is-on', i < step && 'is-done']" :aria-current="step === i ? 'step' : undefined" :disabled="i > step" @click="go(i)">
              <b class="font-display">{{ formatNumber(i + 1) }}</b><span>{{ l }}</span>
            </button>
          </li>
        </ol>

        <div class="panel framed">
          <!-- 1 · for whom, and why -->
          <template v-if="step === 0">
            <h3 class="font-display">الهدية لمين؟</h3>
            <div class="who" role="group" aria-label="لمين">
              <button v-for="[k, l, d] in WHO" :key="k" :class="who === k && 'is-on'" :aria-pressed="who === k" @click="chooseWho(k)"><b class="font-display">{{ l }}</b><span>{{ d }}</span></button>
            </div>
            <h3 class="font-display text-[1.375rem]">وبمناسبة إيه؟</h3>
            <div class="flex flex-wrap gap-2" role="group" aria-label="المناسبة">
              <button v-for="[k, l] in OCCASIONS" :key="k" class="chip" :class="occasion === k && 'is-on'" :aria-pressed="occasion === k" @click="occasion = k">{{ l }}</button>
            </div>
            <p class="sub">هنقترح لك كلام للكارت على حسب المناسبة.</p>
          </template>

          <!-- 2 · the perfume and its size -->
          <template v-else-if="step === 1">
            <h3 class="font-display">اختار العطر</h3>
            <ul class="plist m-0 list-none p-0" :aria-busy="!products.length">
              <li v-for="p in shown" :key="p.slug">
                <button :class="slug === p.slug && 'is-on'" :aria-pressed="slug === p.slug" @click="choose(p)">
                  <span class="min-w-0"><b class="font-display">{{ p.name }}</b><small>مستوحى من {{ p.inspiredBy }} · {{ p.signature }}</small></span>
                  <small class="tabular">من {{ formatPrice(p.fromPrice ?? Math.min(...p.variants.map((v) => v.price))) }}</small>
                </button>
              </li>
            </ul>
            <SizePicker v-if="product" v-model="size" :variants="product.variants" legend="الحجم" />
          </template>

          <!-- 3 · the card -->
          <template v-else-if="step === 2">
            <h3 class="font-display">اكتب الكارت</h3>
            <div class="flex flex-col gap-1.5">
              <label :for="`${id}-to`" class="text-[0.875rem]">إلى</label>
              <input :id="`${id}-to`" v-model="to" maxlength="60" placeholder="اسم من تهديه" class="field" />
            </div>
            <div class="flex flex-col gap-1.5">
              <label :for="`${id}-msg`" class="flex justify-between text-[0.875rem]">رسالتك <span class="tabular text-muted" aria-live="polite">{{ formatNumber(MAX - msg.length) }} حرفاً متبقياً</span></label>
              <textarea :id="`${id}-msg`" v-model="msg" :maxlength="MAX" rows="3" placeholder="سطر واحد يكفي" class="field resize-none" />
            </div>
            <p class="sub">أو اختار جملة جاهزة:</p>
            <div class="flex flex-col gap-1.5" role="group" aria-label="جمل جاهزة">
              <button v-for="l in LINES[occasion]" :key="l" class="line font-display" :class="msg === l && 'is-on'" :aria-pressed="msg === l" @click="msg = l">{{ l }}</button>
            </div>
          </template>

          <!-- 4 · ready -->
          <template v-else>
            <h3 class="font-display">هديتك جاهزة</h3>
            <p class="sub">مغلّفة بعلبة مداد بشريطة دهب وختم، والكارت مطبوع برسالتك. مش هنكتب السعر جوه العلبة.</p>
            <dl class="sum m-0">
              <dt>العطر</dt><dd>{{ product ? `${product.name} · ${formatMl(size)}` : '—' }}</dd>
              <dt>الكارت</dt><dd>إلى {{ to || '…' }}</dd>
              <dt>التغليف</dt><dd>مجاناً</dd>
              <dt class="total font-display">الإجمالي</dt><dd class="total font-display tabular">{{ variant ? formatPrice(variant.price) : '—' }}</dd>
            </dl>
            <button class="btn-pill self-start" :disabled="!product || saving" @click="addGift">{{ saving ? 'جارٍ الإضافة…' : 'أضف الهدية إلى السلة' }}</button>
          </template>

          <div class="nav">
            <button v-if="step > 0" class="btn-pill is-light" @click="go(step - 1)">رجوع</button>
            <span v-else />
            <button v-if="step < 3" class="btn-pill is-gold" :disabled="!canNext" @click="go(step + 1)">{{ step === 2 ? 'اقفل العلبة' : 'التالي' }}</button>
          </div>
        </div>
      </section>

      <GiftStage ref="stage" class="stage-col" :product="step >= 1 ? product : null" :size="size" :to="to" :message="msg" :carded="step >= 2" :closed="step === 3" />
    </div>
  </main>
</template>

<style scoped>
.lay { display: grid; grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr); gap: 12px; align-items: start; }
.stage-col { position: sticky; top: 92px; scroll-margin-top: 12px; }
.steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; margin-bottom: 10px; }
.step { display: flex; align-items: center; gap: 8px; width: 100%; min-height: 54px; padding: 0 12px; border-radius: 16px; background: var(--color-paper); font-size: 0.875rem; }
.step b { display: grid; place-items: center; flex: none; width: 28px; height: 28px; border-radius: 50%; background: var(--color-linen); font-size: 1rem; }
.step.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.step.is-on b { background: var(--color-gold-light); color: var(--color-ink); }
.step.is-done b { background: var(--color-ink); color: var(--color-gold-light); }
.step:disabled { cursor: default; }
.panel { display: flex; flex-direction: column; gap: 14px; min-height: 560px; padding: 24px; border-radius: var(--radius-tile); background: var(--color-paper); }
.panel h3 { margin: 0; font-size: 1.75rem; font-weight: 700; line-height: 1.2; }
.sub { margin: 0; font-size: 0.875rem; color: var(--color-muted); }
.who { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
.who button { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; min-height: 84px; border-radius: 18px; background: var(--color-ivory); font-size: 0.8125rem; }
.who button b { font-size: 1.4rem; }
.who button.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.chip { min-height: 40px; padding: 0 14px; border-radius: 999px; background: var(--color-ivory); font-size: 0.875rem; }
.chip.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.plist { display: flex; flex-direction: column; gap: 6px; max-height: 320px; overflow-y: auto; padding-inline-end: 2px; }
.plist button { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 8px; width: 100%; padding: 10px 14px; border-radius: 16px; background: var(--color-ivory); text-align: start; }
.plist b { display: block; font-size: 1.2rem; line-height: 1.2; }
.plist small { display: block; font-size: 0.75rem; color: var(--color-muted); }
.plist button.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.plist button.is-on small { color: var(--color-sand); }
.field { width: 100%; min-width: 0; padding: 12px 14px; border: 0; border-radius: 16px; background: var(--color-ivory); font-size: 1rem; outline: none; box-shadow: inset 0 0 0 1px var(--color-sand); }
.field:focus { box-shadow: inset 0 0 0 2px var(--color-ink); }
.line { padding: 9px 14px; border-radius: 14px; background: var(--color-ivory); text-align: start; font-size: 1.0625rem; }
.line.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.sum { display: grid; grid-template-columns: 1fr auto; gap: 8px; padding: 14px; border-radius: 16px; background: var(--color-ivory); font-size: 0.875rem; }
.sum dt { color: var(--color-muted); }
.sum dd { margin: 0; }
.sum .total { color: var(--color-ink); font-size: 1.25rem; font-weight: 700; padding-top: 6px; border-top: 1px solid var(--color-sand); }
.nav { display: flex; justify-content: space-between; gap: 10px; margin-top: auto; }

/* phone: the studio comes first, the steps under it */
@media (max-width: 1023px) {
  .lay { grid-template-columns: 1fr; }
  .lay { gap: 8px; }
  .stage-col { position: relative; top: auto; }
  section { display: contents; }
  .steps { order: -2; margin-bottom: 0; }
  .stage-col { order: -1; }
  .panel { min-height: 0; }
  .step span { display: none; }
  .step { justify-content: center; }
}
</style>
