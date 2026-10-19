<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import IngredientArt from '@/components/decor/IngredientArt.vue'
import SpotlightSection from '@/components/home/SpotlightSection.vue'
import { admin } from '@/services/admin'
import { api } from '@/services/api'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'
import { contrastRatio } from '@/utils/contrast'
import { formatNumber } from '@/utils/format'

// FE-41: a perfume's identity, edited against a live copy of its homepage band,
// with the contrast rule enforced before saving (the API enforces it too).
const ui = useUiStore()
const cats = ref([])
const active = ref(null)
const products = ref([])
const preview = ref(null)
const saving = ref(false)
const errors = ref({})
const INGREDIENTS = ['vanilla', 'amber', 'lavender', 'cardamom', 'strawberry', 'blackberry', 'jasmine', 'musk', 'incense', 'tobacco', 'oud', 'resin']
const TEXTURES = [['plain', 'بسيط (حبيبات فقط)'], ['resin', 'عنبر وخشب'], ['petals', 'بتلات وحرير'], ['smoke', 'دخان ورخام']]
const ROLES = [['bg', 'الخلفية'], ['surface', 'الكروت'], ['fg', 'النص'], ['muted', 'النص الثانوي'], ['accent', 'اللمسة (الزر والمكونات)']]
const s = reactive({ id: null, product_id: null, tagline: '', palette: { bg: '#3B342C', surface: '#4A4137', fg: '#F2EADB', muted: '#CFC4B2', accent: '#D6B77E' }, texture: 'plain', ingredients: [], is_active: true, starts_at: '', ends_at: '' })

const checks = computed(() => [
  ['النص على الخلفية', contrastRatio(s.palette.bg, s.palette.fg), 4.5],
  ['النص على الكروت', contrastRatio(s.palette.surface, s.palette.fg), 4.5],
  ['الثانوي على الخلفية', contrastRatio(s.palette.bg, s.palette.muted), 3],
])
const readable = computed(() => checks.value.every(([, r, min]) => r >= min))
const category = computed(() => cats.value.find((c) => c.id === active.value))
const section = computed(() => preview.value && category.value && {
  category: { slug: category.value.slug, name: category.value.name },
  spotlight: { sample: false, tagline: s.tagline || 'جملة تعريفية قصيرة.', palette: s.palette, texture: s.texture, ingredients: s.ingredients, pyramid: preview.value.pyramid || { top: [], heart: [], base: [] }, product: preview.value },
  palette: s.palette,
  others: [],
})

async function load() {
  cats.value = (await admin.categories()).data
  if (!active.value) pick(cats.value[0].id)
}
function pick(id) {
  active.value = id
  const c = cats.value.find((x) => x.id === id)
  const cur = c.spotlights.find((x) => x.active) || c.spotlights[0]
  errors.value = {}
  Object.assign(s, cur
    ? { id: cur.id, product_id: cur.productId, tagline: cur.tagline, palette: { ...cur.palette }, texture: cur.texture, ingredients: [...cur.ingredients], is_active: cur.active, starts_at: cur.startsAt?.slice(0, 10) || '', ends_at: cur.endsAt?.slice(0, 10) || '' }
    : { id: null, product_id: null, tagline: '', texture: 'plain', ingredients: [], is_active: true, starts_at: '', ends_at: '' })
  admin.products({ category_id: id, per_page: 50 }).then((r) => {
    products.value = r.data
    if (!s.product_id) s.product_id = r.data[0]?.id
  })
}
watch(() => s.product_id, async (id) => {
  const slug = products.value.find((p) => p.id === id)?.slug
  preview.value = slug ? (await api.product(slug)).data : null
})
watch(products, () => { if (s.product_id && !preview.value) { const slug = products.value.find((p) => p.id === s.product_id)?.slug; if (slug) api.product(slug).then((r) => (preview.value = r.data)) } })
function toggleIng(name) {
  const i = s.ingredients.indexOf(name)
  if (i >= 0) s.ingredients.splice(i, 1)
  else if (s.ingredients.length < 4) s.ingredients.push(name)
}
async function save() {
  if (!readable.value) return
  saving.value = true
  errors.value = {}
  try {
    await admin.saveSpotlight({ category_id: active.value, product_id: s.product_id, tagline: s.tagline, palette: s.palette, texture: s.texture, ingredients: s.ingredients, is_active: s.is_active, starts_at: s.starts_at || null, ends_at: s.ends_at || null }, s.id)
    ui.toast('حُفظت الهوية وظهرت في الرئيسية')
    const keep = active.value
    await load()
    pick(keep)
  } catch (e) {
    errors.value = e.errors || {}
    ui.toast(firstError(e))
  } finally {
    saving.value = false
  }
}
onMounted(load)
</script>

<template>
  <AdminHeader title="الأقسام والهويات" lead="العطر المميز في كل قسم، بألوانه وملمسه ومكوناته. ما تراه في المعاينة هو ما يظهر في الرئيسية." />

  <div class="mb-6 flex flex-wrap gap-2" role="tablist" aria-label="القسم">
    <button v-for="c in cats" :key="c.id" role="tab" :aria-selected="active === c.id" class="rounded-full border px-4 py-1.5" :class="active === c.id ? 'border-ink bg-ink text-ivory' : 'border-ink/25'" @click="pick(c.id)">
      {{ c.name }} <span class="text-[0.8125rem] opacity-70">· {{ formatNumber(c.products) }}</span>
    </button>
  </div>

  <form v-if="category" class="grid items-start gap-8 2xl:grid-cols-[420px_1fr]" novalidate @submit.prevent="save">
    <fieldset class="m-0 flex flex-col gap-5 border-0 p-0">
      <div class="flex flex-col gap-1.5">
        <label for="sp-p" class="text-[0.875rem]">العطر المميز</label>
        <select id="sp-p" v-model="s.product_id" class="h-11 rounded-xl border border-sand bg-paper px-3"><option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option></select>
        <p v-if="errors.product_id" class="m-0 text-[0.8125rem] text-danger">{{ errors.product_id[0] }}</p>
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="sp-t" class="flex justify-between text-[0.875rem]">الجملة التعريفية <span class="text-muted tabular">{{ formatNumber(200 - s.tagline.length) }}</span></label>
        <textarea id="sp-t" v-model="s.tagline" rows="2" maxlength="200" class="rounded-xl border border-sand bg-paper p-3" />
      </div>

      <fieldset class="m-0 border-0 p-0">
        <legend class="mb-2 p-0 text-[0.875rem]">الألوان</legend>
        <div class="grid grid-cols-1 gap-2">
          <label v-for="[k, l] in ROLES" :key="k" class="flex items-center gap-3">
            <input v-model="s.palette[k]" type="color" class="h-9 w-12 cursor-pointer rounded-xl border border-sand bg-paper p-0.5" :aria-label="l" />
            <input v-model="s.palette[k]" class="h-9 w-28 rounded-xl border border-sand bg-paper px-2 font-meta text-[0.8125rem] uppercase" dir="ltr" maxlength="7" :aria-label="`${l} (hex)`" />
            <span class="text-[0.875rem]">{{ l }}</span>
          </label>
        </div>
        <ul class="m-0 mt-3 flex list-none flex-col gap-1 p-0 text-[0.8125rem]" aria-live="polite">
          <li v-for="[l, r, min] in checks" :key="l" class="flex items-center gap-2" :class="r >= min ? 'text-[#2f5d3a]' : 'text-danger'">
            <span class="inline-block h-2 w-2 rotate-[18deg]" :class="r >= min ? 'bg-[#2f5d3a]' : 'bg-danger'" aria-hidden="true" />
            {{ l }}: <span class="tabular" dir="ltr">{{ r.toFixed(1) }}:1</span> {{ r >= min ? 'مقروء' : `أقل من ${min}، غيّر اللون` }}
          </li>
        </ul>
      </fieldset>

      <div class="flex flex-col gap-1.5">
        <label for="sp-x" class="text-[0.875rem]">الملمس</label>
        <select id="sp-x" v-model="s.texture" class="h-11 rounded-xl border border-sand bg-paper px-3"><option v-for="[v, l] in TEXTURES" :key="v" :value="v">{{ l }}</option></select>
      </div>

      <fieldset class="m-0 border-0 p-0">
        <legend class="mb-2 p-0 text-[0.875rem]">المكونات المرسومة (حتى ٤)</legend>
        <div class="grid grid-cols-4 gap-2">
          <button v-for="n in INGREDIENTS" :key="n" type="button" class="flex flex-col items-center gap-1 border p-2 text-ink" :class="s.ingredients.includes(n) ? 'border-ink bg-paper' : 'border-sand'" :aria-pressed="s.ingredients.includes(n)" @click="toggleIng(n)">
            <IngredientArt :name="n" :size="40" />
          </button>
        </div>
      </fieldset>

      <div class="grid grid-cols-2 gap-3">
        <label class="flex flex-col gap-1.5 text-[0.875rem]">يبدأ من<input v-model="s.starts_at" type="date" class="h-10 rounded-xl border border-sand bg-paper px-2" /></label>
        <label class="flex flex-col gap-1.5 text-[0.875rem]">ينتهي في<input v-model="s.ends_at" type="date" class="h-10 rounded-xl border border-sand bg-paper px-2" /></label>
      </div>
      <label class="flex items-center gap-2"><input v-model="s.is_active" type="checkbox" class="h-4 w-4 accent-[#0E0D0C]" /> مفعّل</label>

      <button class="rounded-full bg-ink py-3.5 text-ivory disabled:opacity-40" :disabled="saving || !readable || !s.tagline.trim()">{{ !readable ? 'الألوان غير مقروءة' : saving ? 'جارٍ الحفظ…' : 'حفظ ونشر في الرئيسية' }}</button>
    </fieldset>

    <section class="min-w-0 overflow-hidden border border-sand" aria-label="معاينة حيّة">
      <p class="m-0 bg-paper px-4 py-2 text-[0.8125rem] text-muted">معاينة حيّة بين القسمين المجاورين</p>
      <div class="pointer-events-none origin-top">
        <div v-if="section" class="rounded-[28px] bg-ivory py-2">
          <SpotlightSection :section="section" preview />
        </div>
        <div v-else class="rounded-[28px] h-96 animate-pulse" :style="{ background: s.palette.bg }" />
        <div class="h-10 bg-ivory" />
      </div>
    </section>
  </form>
</template>
