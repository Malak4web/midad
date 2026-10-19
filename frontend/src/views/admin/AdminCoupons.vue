<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import FormField from '@/components/ui/FormField.vue'
import { admin } from '@/services/admin'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'
import { formatNumber, formatPrice } from '@/utils/format'

// FE-45: general coupons, and the discovery credits the system issues by itself.
const ui = useUiStore()
const kind = ref('general')
const rows = ref([])
const editing = ref(null)
const errors = ref({})
const c = reactive({})
const TYPES = { percent: 'نسبة ٪', fixed: 'مبلغ ثابت', free_shipping: 'شحن مجاني' }
const load = async () => (rows.value = (await admin.coupons({ kind: kind.value })).data)
onMounted(load)
watch(kind, load)

function edit(row = null) {
  errors.value = {}
  editing.value = row?.id || 'new'
  Object.assign(c, { code: '', type: 'percent', value: 10, min_subtotal: '', max_discount: '', usage_limit: '', per_customer_limit: 1, starts_at: '', ends_at: '', is_active: true },
    row ? { code: row.code, type: row.type, value: row.value, min_subtotal: row.minSubtotal || '', max_discount: row.maxDiscount ?? '', usage_limit: row.usageLimit ?? '', per_customer_limit: row.perCustomerLimit ?? '', starts_at: row.startsAt || '', ends_at: row.endsAt || '', is_active: row.active } : {})
}
async function save() {
  errors.value = {}
  const body = Object.fromEntries(Object.entries(c).map(([k, v]) => [k, v === '' ? null : v]))
  try {
    await admin.saveCoupon(body, editing.value === 'new' ? null : editing.value)
    editing.value = null
    ui.toast('حُفظ الكوبون')
    load()
  } catch (e) {
    errors.value = e.errors || {}
  }
}
async function remove(row) {
  try {
    await admin.deleteCoupon(row.id)
    ui.toast(row.used ? 'أُوقف الكوبون (استُخدم من قبل فلا يُحذف)' : 'حُذف الكوبون')
    load()
  } catch (e) {
    ui.toast(firstError(e))
  }
}
</script>

<template>
  <AdminHeader title="الكوبونات" lead="الكود يُكتب بالإنجليزي، والعميل يكتبه في السلة.">
    <button v-if="kind === 'general'" class="rounded-full bg-ink px-5 py-2.5 text-ivory" @click="edit()">كوبون جديد</button>
  </AdminHeader>
  <div class="mb-6 flex gap-2" role="tablist">
    <button role="tab" :aria-selected="kind === 'general'" class="rounded-full border px-4 py-1.5" :class="kind === 'general' ? 'border-ink bg-ink text-ivory' : 'border-ink/25'" @click="kind = 'general'">كوبونات</button>
    <button role="tab" :aria-selected="kind === 'discovery_credit'" class="rounded-full border px-4 py-1.5" :class="kind === 'discovery_credit' ? 'border-ink bg-ink text-ivory' : 'border-ink/25'" @click="kind = 'discovery_credit'">أرصدة الاكتشاف</button>
  </div>

  <form v-if="editing" class="mb-8 grid max-w-3xl gap-4 border border-ink p-5 sm:grid-cols-3" novalidate @submit.prevent="save">
    <FormField v-model="c.code" label="الكود" dir="ltr" required :error="errors.code" />
    <div class="flex flex-col gap-1.5"><label for="ct" class="text-[0.875rem]">النوع</label><select id="ct" v-model="c.type" class="h-11 rounded-xl border border-sand bg-paper px-3"><option v-for="(l, k) in TYPES" :key="k" :value="k">{{ l }}</option></select></div>
    <FormField v-if="c.type !== 'free_shipping'" v-model="c.value" :label="c.type === 'percent' ? 'النسبة ٪' : 'المبلغ ج.م'" type="number" :error="errors.value" />
    <FormField v-model="c.min_subtotal" label="حد أدنى للطلب ج.م" type="number" :error="errors.min_subtotal" />
    <FormField v-if="c.type === 'percent'" v-model="c.max_discount" label="أقصى خصم ج.م" type="number" :error="errors.max_discount" />
    <FormField v-model="c.usage_limit" label="عدد مرات الاستخدام الكلي" type="number" :error="errors.usage_limit" />
    <FormField v-model="c.per_customer_limit" label="لكل عميل" type="number" :error="errors.per_customer_limit" />
    <FormField v-model="c.starts_at" label="يبدأ" type="date" :error="errors.starts_at" />
    <FormField v-model="c.ends_at" label="ينتهي" type="date" :error="errors.ends_at" />
    <label class="flex items-center gap-2 sm:col-span-3"><input v-model="c.is_active" type="checkbox" class="h-4 w-4 accent-[#0E0D0C]" /> مفعّل</label>
    <div class="flex gap-3 sm:col-span-3"><button class="rounded-full bg-ink px-6 py-2.5 text-ivory">حفظ</button><button type="button" class="rounded-full border border-ink px-6 py-2.5" @click="editing = null">إلغاء</button></div>
  </form>

  <div class="overflow-x-auto">
    <table class="w-full min-w-[760px] border-collapse text-[0.9375rem]">
      <thead><tr class="border-b border-ink text-[0.8125rem] text-muted"><th class="py-2 text-start font-normal">الكود</th><th class="text-start font-normal">الخصم</th><th class="text-start font-normal">الشروط</th><th class="text-start font-normal">الاستخدام</th><th class="text-start font-normal">المدة</th><th /></tr></thead>
      <tbody>
        <tr v-for="r in rows" :key="r.id" class="border-b border-sand" :class="!r.active && 'opacity-55'">
          <td class="py-3 font-meta tracking-[0.06em]" dir="ltr">{{ r.code }}</td>
          <td>{{ r.label }}</td>
          <td class="text-[0.875rem]">{{ r.minSubtotal ? `من ${formatPrice(r.minSubtotal)}` : '—' }}<span v-if="r.phone" class="block text-muted" dir="ltr">{{ r.phone }}</span></td>
          <td class="tabular text-[0.875rem]">{{ formatNumber(r.used) }}{{ r.usageLimit ? ` / ${formatNumber(r.usageLimit)}` : '' }}<span class="block text-muted">{{ formatPrice(r.discountGiven) }} خصم</span></td>
          <td class="text-[0.875rem] text-muted">{{ r.startsAt || '—' }} ← {{ r.endsAt || 'مفتوح' }}</td>
          <td class="whitespace-nowrap text-end text-[0.875rem]"><button v-if="r.kind === 'general'" class="underline" @click="edit(r)">تعديل</button> <button class="ms-3 text-danger underline" @click="remove(r)">{{ r.used ? 'إيقاف' : 'حذف' }}</button></td>
        </tr>
        <tr v-if="!rows.length"><td colspan="6" class="py-8 text-center text-muted">لا توجد كوبونات هنا بعد.</td></tr>
      </tbody>
    </table>
  </div>
</template>
