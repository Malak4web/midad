<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import { admin, PAY, PAY_STATUS, STATUS } from '@/services/admin'
import { formatNumber, formatPrice } from '@/utils/format'

// FE-43: filters live in the URL so a filtered list can be bookmarked.
const route = useRoute()
const router = useRouter()
const rows = ref([])
const meta = ref(null)
const loading = ref(false)
const q = ref(route.query.q || '')
const when = new Intl.DateTimeFormat('ar-EG', { dateStyle: 'short', timeStyle: 'short' })
const f = computed(() => ({ status: route.query.status, payment_method: route.query.payment_method, from: route.query.from, to: route.query.to, q: route.query.q, page: route.query.page }))
const set = (k, v) => router.replace({ query: { ...route.query, [k]: v || undefined, page: undefined } })

async function load() {
  loading.value = true
  try {
    const res = await admin.orders(f.value)
    rows.value = res.data
    meta.value = res.meta
  } finally {
    loading.value = false
  }
}
watch(() => route.query, load, { immediate: true })
</script>

<template>
  <AdminHeader title="الطلبات" :lead="meta ? `${formatNumber(meta.total)} طلب في هذا العرض` : ''" />

  <div class="mb-5 flex flex-wrap gap-2" role="group" aria-label="الحالة">
    <button class="tab" :class="!f.status && 'is-on'" @click="set('status', '')">الكل</button>
    <button v-for="(l, s) in STATUS" :key="s" class="tab" :class="f.status === s && 'is-on'" :aria-pressed="f.status === s" @click="set('status', s)">
      {{ l }} <span class="tabular opacity-70">{{ formatNumber(meta?.counts?.[s] || 0) }}</span>
    </button>
  </div>
  <div class="mb-6 flex flex-wrap items-end gap-3">
    <form class="flex gap-2" @submit.prevent="set('q', q.trim())">
      <label for="oq" class="sr-only">بحث</label>
      <input id="oq" v-model="q" placeholder="رقم الطلب، الموبايل، أو الاسم" class="h-10 w-64 rounded-xl border border-sand bg-paper px-3" />
      <button class="rounded-full border border-ink px-4">بحث</button>
    </form>
    <label class="flex items-center gap-2 text-[0.875rem]">الدفع
      <select class="h-10 rounded-xl border border-sand bg-paper px-2" :value="f.payment_method || ''" @change="set('payment_method', $event.target.value)"><option value="">الكل</option><option v-for="(l, k) in PAY" :key="k" :value="k">{{ l }}</option></select>
    </label>
    <label class="flex items-center gap-2 text-[0.875rem]">من <input type="date" class="h-10 rounded-xl border border-sand bg-paper px-2" :value="f.from" @change="set('from', $event.target.value)" /></label>
    <label class="flex items-center gap-2 text-[0.875rem]">إلى <input type="date" class="h-10 rounded-xl border border-sand bg-paper px-2" :value="f.to" @change="set('to', $event.target.value)" /></label>
  </div>

  <div class="overflow-x-auto" :aria-busy="loading">
    <table class="w-full min-w-[820px] border-collapse text-[0.9375rem]">
      <thead><tr class="border-b border-ink text-[0.8125rem] text-muted"><th class="py-2 text-start font-normal">الطلب</th><th class="text-start font-normal">التاريخ</th><th class="text-start font-normal">العميل</th><th class="text-start font-normal">المحافظة</th><th class="text-start font-normal">الدفع</th><th class="text-start font-normal">الحالة</th><th class="text-end font-normal">الإجمالي</th></tr></thead>
      <tbody>
        <tr v-for="o in rows" :key="o.number" class="cursor-pointer border-b border-sand hover:bg-paper" @click="router.push({ name: 'admin-order', params: { number: o.number } })">
          <td class="py-3"><RouterLink :to="{ name: 'admin-order', params: { number: o.number } }" class="font-meta text-[0.8125rem] text-ink" dir="ltr" @click.stop>{{ o.number }}</RouterLink></td>
          <td class="text-[0.875rem] text-muted">{{ when.format(new Date(o.createdAt)) }}</td>
          <td>{{ o.customer }}<span class="block text-[0.8125rem] text-muted" dir="ltr">{{ o.phone }}</span></td>
          <td>{{ o.governorate }}</td>
          <td class="text-[0.875rem]">{{ PAY[o.paymentMethod] }}<span class="block text-muted">{{ PAY_STATUS[o.paymentStatus] }}</span></td>
          <td><StatusPill :status="o.status" /></td>
          <td class="text-end tabular">{{ formatPrice(o.total) }}</td>
        </tr>
        <tr v-if="!loading && !rows.length"><td colspan="7" class="py-10 text-center text-muted">لا توجد طلبات بهذه الفلاتر.</td></tr>
      </tbody>
    </table>
  </div>
  <nav v-if="meta && meta.last_page > 1" class="mt-6 flex items-center gap-3" aria-label="الصفحات">
    <button class="rounded-full border border-ink px-4 py-2 disabled:opacity-30" :disabled="meta.current_page <= 1" @click="router.replace({ query: { ...route.query, page: meta.current_page - 1 } })">السابق</button>
    <span class="tabular text-muted">{{ formatNumber(meta.current_page) }} / {{ formatNumber(meta.last_page) }}</span>
    <button class="rounded-full border border-ink px-4 py-2 disabled:opacity-30" :disabled="meta.current_page >= meta.last_page" @click="router.replace({ query: { ...route.query, page: meta.current_page + 1 } })">التالي</button>
  </nav>
</template>

<style scoped>
.tab { border: 1px solid color-mix(in srgb, var(--color-ink) 25%, transparent); border-radius: 9999px; padding: 5px 14px; font-size: 0.875rem; }
.tab.is-on { background: var(--color-ink); color: var(--color-ivory); border-color: var(--color-ink); }
</style>
