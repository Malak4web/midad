<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import TagInput from '@/components/admin/TagInput.vue'
import StockAdjust from '@/components/admin/StockAdjust.vue'
import FormField from '@/components/ui/FormField.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { admin } from '@/services/admin'
import { api } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'
import { formatNumber } from '@/utils/format'

// FE-40 editor: one form for facts, sizes, notes, pyramid; images and stock act immediately.
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ui = useUiStore()
const isNew = computed(() => route.name === 'admin-product-new')
const canEdit = computed(() => auth.user?.role === 'admin')
const SIZES = [10, 30, 50, 100]
const p = ref(null)
const errors = ref({})
const saving = ref(false)
const allNotes = ref([])
const f = reactive({ category_id: 1, name: '', latin_name: '', inspired_by: '', signature_note: '', description: '', is_active: true, notes: [], pyramid: { top: [], heart: [], base: [] }, sizes: {} })
SIZES.forEach((ml) => (f.sizes[ml] = { on: ml !== 10, price: '', compare_at: '', stock: 0, low_stock_threshold: 5 }))

function fill(d) {
  p.value = d
  Object.assign(f, { category_id: d.categoryId, name: d.name, latin_name: d.latin, inspired_by: d.inspiredBy || '', signature_note: d.signature || '', description: d.description || '', is_active: d.active, notes: [...d.notes], pyramid: { top: [...d.pyramid.top], heart: [...d.pyramid.heart], base: [...d.pyramid.base] } })
  SIZES.forEach((ml) => {
    const v = d.variants.find((x) => x.ml === ml)
    f.sizes[ml] = v ? { on: v.active, price: v.price, compare_at: v.compareAt ?? '', stock: v.stock, low_stock_threshold: v.lowThreshold } : { on: false, price: '', compare_at: '', stock: 0, low_stock_threshold: 5 }
  })
}
onMounted(async () => {
  allNotes.value = (await api.notes().catch(() => ({ data: [] }))).data.map((n) => n.name)
  if (!isNew.value) fill((await admin.product(route.params.id)).data)
})

async function save() {
  saving.value = true
  errors.value = {}
  const body = {
    ...f,
    sizes: undefined,
    variants: SIZES.filter((ml) => f.sizes[ml].on).map((ml) => ({ size_ml: ml, price: +f.sizes[ml].price, compare_at: f.sizes[ml].compare_at === '' ? null : +f.sizes[ml].compare_at, stock: isNew.value ? +f.sizes[ml].stock : undefined, low_stock_threshold: +f.sizes[ml].low_stock_threshold, is_active: true })),
  }
  try {
    const { data } = await admin.saveProduct(body, isNew.value ? null : p.value.id)
    ui.toast('تم الحفظ')
    if (isNew.value) router.replace({ name: 'admin-product', params: { id: data.id } })
    fill(data)
  } catch (e) {
    errors.value = e.errors || {}
    ui.toast(firstError(e))
  } finally {
    saving.value = false
  }
}
async function upload(e) {
  for (const file of e.target.files) {
    try {
      fill((await admin.uploadImage(p.value.id, file, f.name)).data)
    } catch (err) {
      ui.toast(firstError(err))
    }
  }
  e.target.value = ''
}
async function removeImage(img) {
  fill((await admin.deleteImage(p.value.id, img.id)).data)
}
async function moveImage(i, dir) {
  const ids = p.value.images.map((x) => x.id)
  ;[ids[i], ids[i + dir]] = [ids[i + dir], ids[i]]
  fill((await admin.sortImages(p.value.id, ids)).data)
}
async function hide() {
  await admin.deleteProduct(p.value.id)
  ui.toast('أُخفي العطر من المتجر')
  router.push({ name: 'admin-products' })
}
</script>

<template>
  <div v-if="!isNew && !p" class="rounded-[28px] h-64 animate-pulse bg-paper" aria-busy="true" />
  <template v-else>
    <AdminHeader :title="isNew ? 'عطر جديد' : f.name" :lead="p ? `/p/${p.slug}` : 'الاسم اللاتيني يصنع رابط العطر.'">
      <RouterLink v-if="p" :to="{ name: 'product', params: { slug: p.slug } }" target="_blank" class="rounded-full border border-ink px-4 py-2 text-ink no-underline">عرض في المتجر</RouterLink>
    </AdminHeader>

    <form class="grid items-start gap-10 xl:grid-cols-[1.4fr_1fr]" novalidate @submit.prevent="save">
      <fieldset class="m-0 flex flex-col gap-6 border-0 p-0" :disabled="!canEdit">
        <div class="grid gap-4 sm:grid-cols-2">
          <FormField v-model="f.name" label="الاسم بالعربي" required :error="errors.name" />
          <FormField v-model="f.latin_name" label="الاسم اللاتيني" dir="ltr" required :error="errors.latin_name" />
          <FormField v-model="f.inspired_by" label="مستوحى من" :error="errors.inspired_by" />
          <FormField v-model="f.signature_note" label="النوتة الأبرز" hint="سطر قصير يظهر على الكارت." :error="errors.signature_note" />
          <div class="flex flex-col gap-1.5">
            <label for="pcat" class="text-[0.875rem]">القسم</label>
            <select id="pcat" v-model="f.category_id" class="h-11 rounded-xl border border-sand bg-paper px-3"><option :value="1">رجالي</option><option :value="2">نسائي</option><option :value="3">شرقي ونيش</option></select>
          </div>
          <label class="flex items-center gap-2 self-end pb-3"><input v-model="f.is_active" type="checkbox" class="h-4 w-4 accent-[#0E0D0C]" /> ظاهر في المتجر</label>
        </div>
        <FormField v-model="f.description" label="الوصف" textarea :maxlength="3000" hint="لو تركته فارغاً، يُكتب وصف تلقائي من الهرم العطري." :error="errors.description" />

        <section aria-labelledby="sz-h">
          <h2 id="sz-h" class="m-0 mb-3 font-display text-2xl">الأحجام والأسعار</h2>
          <p v-if="errors.variants" class="m-0 mb-2 text-danger">{{ errors.variants[0] }}</p>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse text-[0.9375rem]">
              <thead><tr class="border-b border-ink text-[0.8125rem] text-muted"><th class="py-2 text-start font-normal">الحجم</th><th class="text-start font-normal">السعر ج.م</th><th class="text-start font-normal">قبل الخصم</th><th class="text-start font-normal">{{ isNew ? 'مخزون أول' : 'حد التنبيه' }}</th></tr></thead>
              <tbody>
                <tr v-for="ml in SIZES" :key="ml" class="border-b border-sand" :class="!f.sizes[ml].on && 'opacity-50'">
                  <td class="py-2"><label class="flex items-center gap-2"><input v-model="f.sizes[ml].on" type="checkbox" class="h-4 w-4 accent-[#0E0D0C]" /> {{ formatNumber(ml) }} مل</label></td>
                  <td><input v-model="f.sizes[ml].price" type="number" min="1" :disabled="!f.sizes[ml].on" class="h-9 w-28 rounded-xl border border-sand bg-paper px-2 tabular" :aria-label="`سعر ${ml} مل`" /></td>
                  <td><input v-model="f.sizes[ml].compare_at" type="number" min="1" :disabled="!f.sizes[ml].on" class="h-9 w-28 rounded-xl border border-sand bg-paper px-2 tabular" :aria-label="`السعر قبل الخصم ${ml} مل`" /></td>
                  <td>
                    <input v-if="isNew" v-model="f.sizes[ml].stock" type="number" min="0" :disabled="!f.sizes[ml].on" class="h-9 w-24 rounded-xl border border-sand bg-paper px-2 tabular" :aria-label="`مخزون ${ml} مل`" />
                    <input v-else v-model="f.sizes[ml].low_stock_threshold" type="number" min="0" :disabled="!f.sizes[ml].on" class="h-9 w-24 rounded-xl border border-sand bg-paper px-2 tabular" :aria-label="`حد التنبيه ${ml} مل`" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="flex flex-col gap-4" aria-labelledby="nt-h">
          <h2 id="nt-h" class="m-0 font-display text-2xl">النوتات والهرم العطري</h2>
          <TagInput v-model="f.notes" label="كل النوتات (للبحث والفلترة)" :suggestions="allNotes" :max="20" />
          <div class="grid gap-4 md:grid-cols-3">
            <TagInput v-model="f.pyramid.top" label="القمة" :suggestions="allNotes" :max="8" />
            <TagInput v-model="f.pyramid.heart" label="القلب" :suggestions="allNotes" :max="8" />
            <TagInput v-model="f.pyramid.base" label="القاعدة" :suggestions="allNotes" :max="8" />
          </div>
        </section>

        <div v-if="canEdit" class="flex flex-wrap gap-3 border-t border-sand pt-5">
          <button class="rounded-full bg-ink px-8 py-3 text-ivory disabled:opacity-50" :disabled="saving">{{ saving ? 'جارٍ الحفظ…' : isNew ? 'إنشاء العطر' : 'حفظ التعديلات' }}</button>
          <button v-if="p" type="button" class="rounded-full border border-danger/60 px-5 py-3 text-danger" @click="hide">إخفاء وحذف من المتجر</button>
        </div>
      </fieldset>

      <aside v-if="p" class="flex flex-col gap-8">
        <section aria-labelledby="img-h">
          <h2 id="img-h" class="m-0 mb-3 font-display text-2xl">الصور</h2>
          <ul class="m-0 grid list-none grid-cols-3 gap-2 p-0">
            <li v-for="(img, i) in p.images" :key="img.id" class="relative border border-sand">
              <img :src="img.url" :alt="img.alt" class="aspect-[4/5] w-full object-cover" />
              <div v-if="canEdit" class="flex justify-between bg-paper px-1">
                <button type="button" :disabled="i === 0" aria-label="تقديم" class="p-1 disabled:opacity-20" @click="moveImage(i, -1)"><AppIcon name="chevron-right" :size="14" /></button>
                <button type="button" class="p-1 text-danger" aria-label="حذف الصورة" @click="removeImage(img)"><AppIcon name="close" :size="14" /></button>
                <button type="button" :disabled="i === p.images.length - 1" aria-label="تأخير" class="p-1 disabled:opacity-20" @click="moveImage(i, 1)"><AppIcon name="chevron-left" :size="14" /></button>
              </div>
            </li>
          </ul>
          <label v-if="canEdit" class="mt-3 inline-flex cursor-pointer items-center gap-2 border border-dashed border-ink/40 px-4 py-3 text-[0.9375rem]">
            <AppIcon name="plus" :size="16" /> رفع صور (JPG / PNG / WebP، حتى ٥ ميجا)
            <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" @change="upload" />
          </label>
          <p class="m-0 mt-2 text-[0.8125rem] text-muted">الأولى تظهر على الكارت. تتحول تلقائياً لـ WebP بثلاثة مقاسات.</p>
        </section>

        <section aria-labelledby="st-h">
          <h2 id="st-h" class="m-0 mb-3 font-display text-2xl">المخزون</h2>
          <div v-for="v in p.variants.filter((x) => x.active)" :key="v.id" class="border-b border-sand py-3">
            <p class="m-0 mb-2 flex justify-between"><span class="font-medium">{{ formatNumber(v.ml) }} مل</span><span class="tabular text-[0.875rem]" :class="v.available <= v.lowThreshold && 'text-danger'">متاح {{ formatNumber(v.available) }} · محجوز {{ formatNumber(v.reserved) }}</span></p>
            <StockAdjust :variant="v" :label="`${f.name} ${v.ml} مل`" @saved="fill" />
          </div>
        </section>
      </aside>
    </form>
  </template>
</template>
