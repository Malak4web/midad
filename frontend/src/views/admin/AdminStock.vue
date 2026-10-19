<script setup>
import { onMounted, ref } from 'vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import StockAdjust from '@/components/admin/StockAdjust.vue'
import { admin } from '@/services/admin'
import { formatNumber } from '@/utils/format'

// FE-42: everything at or under its alert line, with a restock right there.
const rows = ref(null)
const load = async () => (rows.value = (await admin.lowStock()).data)
onMounted(load)
</script>

<template>
  <AdminHeader title="المخزون" lead="الأحجام التي وصلت لحد التنبيه أو أقل. سجّل التوريد مباشرة من هنا." />
  <div v-if="!rows" class="rounded-[28px] h-40 animate-pulse bg-paper" aria-busy="true" />
  <p v-else-if="!rows.length" class="m-0 rounded-[28px] bg-paper p-6 text-muted">كل الأحجام فوق حد التنبيه.</p>
  <ul v-else class="m-0 list-none p-0">
    <li v-for="r in rows" :key="r.variantId" class="grid gap-3 border-b border-sand py-4 md:grid-cols-[260px_1fr] md:items-center">
      <div>
        <RouterLink :to="{ name: 'admin-products', query: { q: r.product } }" class="font-display text-xl font-bold text-ink no-underline">{{ r.product }}</RouterLink>
        <span class="block text-[0.875rem]"><span class="tabular" :class="r.available <= 0 ? 'font-medium text-danger' : ''">{{ formatNumber(r.ml) }} مل · متاح {{ formatNumber(r.available) }}</span><span class="text-muted"> / حد التنبيه {{ formatNumber(r.threshold) }}</span></span>
      </div>
      <StockAdjust :variant="{ id: r.variantId }" :label="`${r.product} ${r.ml} مل`" @saved="load" />
    </li>
  </ul>
</template>
