<script setup>
import { onMounted, ref } from 'vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import { admin } from '@/services/admin'
import { formatNumber, formatPrice } from '@/utils/format'

// FE-39: what needs a hand today, first; the numbers after.
const d = ref(null)
const when = new Intl.DateTimeFormat('ar-EG', { dateStyle: 'short', timeStyle: 'short' })
onMounted(async () => (d.value = (await admin.dashboard()).data))
</script>

<template>
  <AdminHeader title="الرئيسية" lead="ما يحتاج تدخلك اليوم، ثم الأرقام." />
  <div v-if="!d" class="grid gap-4 md:grid-cols-4" aria-busy="true"><div v-for="n in 4" :key="n" class="rounded-[28px] h-28 animate-pulse bg-paper" /></div>
  <template v-else>
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="يحتاج تدخلاً">
      <RouterLink v-for="[k, l, q] in [['toConfirm', 'طلبات جديدة للتأكيد', { status: 'placed' }], ['toShip', 'جاهزة للتجهيز والشحن', { status: 'confirmed' }], ['awaitingPayment', 'بانتظار الدفع الإلكتروني', { status: 'pending_payment' }]]" :key="k" :to="{ name: 'admin-orders', query: q }" class="tile flex flex-col gap-1 border p-5 text-ink no-underline" :class="d[k] ? 'border-ink bg-paper' : 'border-sand'">
        <span class="font-display text-4xl tabular">{{ formatNumber(d[k]) }}</span>
        <span class="text-[0.9375rem]">{{ l }}</span>
      </RouterLink>
      <RouterLink :to="{ name: 'admin-stock' }" class="tile flex flex-col gap-1 border p-5 text-ink no-underline" :class="d.lowStock ? 'border-danger/60 bg-danger/5' : 'border-sand'">
        <span class="font-display text-4xl tabular" :class="d.lowStock && 'text-danger'">{{ formatNumber(d.lowStock) }}</span>
        <span class="text-[0.9375rem]">أحجام مخزونها منخفض</span>
      </RouterLink>
    </section>

    <section class="mt-8 grid gap-3 sm:grid-cols-2" aria-label="المبيعات">
      <div class="rounded-[28px] bg-paper p-5"><p class="m-0 text-muted">مبيعات اليوم</p><p class="m-0 font-display text-3xl tabular">{{ formatPrice(d.today.sales) }}</p><p class="m-0 text-[0.875rem] text-muted">{{ formatNumber(d.today.orders) }} طلب</p></div>
      <div class="rounded-[28px] bg-paper p-5"><p class="m-0 text-muted">آخر ٧ أيام</p><p class="m-0 font-display text-3xl tabular">{{ formatPrice(d.week.sales) }}</p><p class="m-0 text-[0.875rem] text-muted">{{ formatNumber(d.week.orders) }} طلب</p></div>
    </section>

    <div class="mt-10 grid gap-10 xl:grid-cols-[1.6fr_1fr]">
      <section>
        <h2 class="m-0 mb-3 font-display text-2xl">أحدث الطلبات</h2>
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-[0.9375rem]">
            <thead><tr class="border-b border-ink text-start text-[0.8125rem] text-muted"><th class="py-2 text-start font-normal">الطلب</th><th class="text-start font-normal">العميل</th><th class="text-start font-normal">الحالة</th><th class="text-end font-normal">الإجمالي</th></tr></thead>
            <tbody>
              <tr v-for="o in d.latest" :key="o.number" class="border-b border-sand">
                <td class="py-2.5"><RouterLink :to="{ name: 'admin-order', params: { number: o.number } }" class="font-meta text-[0.8125rem] text-ink" dir="ltr">{{ o.number }}</RouterLink></td>
                <td>{{ o.customer }}</td>
                <td><StatusPill :status="o.status" /></td>
                <td class="text-end tabular">{{ formatPrice(o.total) }}</td>
              </tr>
              <tr v-if="!d.latest.length"><td colspan="4" class="py-6 text-center text-muted">لا توجد طلبات بعد.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
      <section>
        <h2 class="m-0 mb-3 font-display text-2xl">آخر التعديلات</h2>
        <ul class="m-0 list-none p-0 text-[0.875rem]">
          <li v-for="(a, i) in d.activity" :key="i" class="border-b border-sand py-2.5"><span class="font-medium">{{ a.who }}</span> <span class="text-muted" dir="ltr">{{ a.action }}</span><span class="block text-[0.75rem] text-muted">{{ when.format(new Date(a.at)) }}</span></li>
          <li v-if="!d.activity.length" class="py-6 text-muted">لا توجد تعديلات بعد.</li>
        </ul>
      </section>
    </div>
  </template>
</template>

<style scoped>
.tile { transition: border-color 0.2s; }
.tile:hover { border-color: var(--color-ink); }
</style>
