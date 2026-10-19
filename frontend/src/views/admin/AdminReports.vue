<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import { admin, PAY } from '@/services/admin'
import { formatNumber, formatPrice } from '@/utils/format'

// FE-47. Every chart here is one measure in one hue (magnitude), so no
// categorical palette is needed; values are always written next to the marks.
const today = new Date().toISOString().slice(0, 10)
const monthAgo = new Date(Date.now() - 29 * 864e5).toISOString().slice(0, 10)
const from = ref(monthAgo)
const to = ref(today)
const r = ref(null)
const loading = ref(false)
const asTable = ref(false)
const hover = ref(null)
const scroller = ref(null)
const dayFmt = new Intl.DateTimeFormat('ar-EG', { day: 'numeric', month: 'short' })

async function load() {
  loading.value = true
  try {
    r.value = (await admin.reports({ from: from.value, to: to.value })).data
    // newest days sit at the far (left) end; on a phone open the scroller there
    await nextTick()
    scroller.value?.scrollTo({ left: -scroller.value.scrollWidth })
  } finally {
    loading.value = false
  }
}
onMounted(load)

// column chart geometry (RTL: time runs right → left, newest on the left)
const W = 760
const H = 220
const PAD = { top: 12, bottom: 28, start: 8, end: 56 }
const chart = computed(() => {
  const days = r.value?.daily || []
  const max = Math.max(1, ...days.map((d) => d.sales))
  const step = niceStep(max)
  const top = Math.ceil(max / step) * step
  const innerW = W - PAD.start - PAD.end
  const innerH = H - PAD.top - PAD.bottom
  const slot = innerW / Math.max(1, days.length)
  const bw = Math.max(2, slot - 2) // 2px surface gap between bars
  return {
    ticks: [0, top / 2, top].map((v) => ({ v, y: PAD.top + innerH - (v / top) * innerH })),
    bars: days.map((d, i) => {
      const h = (d.sales / top) * innerH
      const x = W - PAD.end - (i + 1) * slot + (slot - bw) / 2
      return { ...d, x, w: bw, y: PAD.top + innerH - h, h, label: i % Math.ceil(days.length / 6) === 0 }
    }),
    base: PAD.top + innerH,
  }
})
function niceStep(max) {
  const raw = max / 2
  const mag = 10 ** Math.floor(Math.log10(raw))
  return [1, 2, 2.5, 5, 10].map((m) => m * mag).find((s) => s >= raw) || raw
}
const share = (list, key) => {
  const total = list.reduce((n, x) => n + x[key], 0) || 1
  return list.map((x) => ({ ...x, pct: x[key] / total }))
}
// rounded data end at the top only, anchored flat on the baseline
const barPath = (b) => {
  const rr = Math.min(4, b.w / 2, b.h)
  return `M${b.x},${b.y + b.h} V${b.y + rr} Q${b.x},${b.y} ${b.x + rr},${b.y} H${b.x + b.w - rr} Q${b.x + b.w},${b.y} ${b.x + b.w},${b.y + rr} V${b.y + b.h} Z`
}
</script>

<template>
  <AdminHeader title="التقارير" :lead="r ? `من ${r.from} إلى ${r.to}` : ''">
    <a v-if="r" :href="admin.ordersCsvUrl({ from, to })" class="rounded-full border border-ink px-4 py-2 text-ink no-underline">تصدير الطلبات CSV</a>
  </AdminHeader>

  <form class="mb-8 flex flex-wrap items-end gap-3" @submit.prevent="load">
    <label class="flex flex-col gap-1 text-[0.875rem]">من<input v-model="from" type="date" class="h-10 rounded-xl border border-sand bg-paper px-2" /></label>
    <label class="flex flex-col gap-1 text-[0.875rem]">إلى<input v-model="to" type="date" class="h-10 rounded-xl border border-sand bg-paper px-2" /></label>
    <button class="rounded-full h-10 bg-ink px-5 text-ivory disabled:opacity-50" :disabled="loading">عرض</button>
  </form>

  <div v-if="!r" class="rounded-[28px] h-64 animate-pulse bg-paper" aria-busy="true" />
  <template v-else>
    <section class="grid gap-3 sm:grid-cols-3" aria-label="الإجمالي">
      <div class="rounded-[28px] bg-paper p-5"><p class="m-0 text-muted">المبيعات</p><p class="m-0 font-display text-4xl tabular">{{ formatPrice(r.totals.sales) }}</p></div>
      <div class="rounded-[28px] bg-paper p-5"><p class="m-0 text-muted">عدد الطلبات</p><p class="m-0 font-display text-4xl tabular">{{ formatNumber(r.totals.orders) }}</p></div>
      <div class="rounded-[28px] bg-paper p-5"><p class="m-0 text-muted">متوسط الطلب</p><p class="m-0 font-display text-4xl tabular">{{ formatPrice(r.totals.average) }}</p></div>
    </section>
    <p class="m-0 mt-2 text-[0.8125rem] text-muted">تُحسب الطلبات المؤكدة وما بعدها فقط؛ لا تدخل الملغاة ولا غير المدفوعة إلكترونياً.</p>

    <section class="mt-10" aria-labelledby="daily-h">
      <div class="mb-3 flex items-end justify-between gap-3">
        <h2 id="daily-h" class="m-0 font-display text-2xl">المبيعات اليومية</h2>
        <button v-if="r.totals.orders" class="text-[0.875rem] underline" @click="asTable = !asTable">{{ asTable ? 'عرض كرسم' : 'عرض كجدول' }}</button>
      </div>
      <p v-if="!r.totals.orders" class="m-0 border border-dashed border-sand py-12 text-center text-muted">لا مبيعات في هذه الفترة. جرّب مدة أطول.</p>
      <div v-else-if="!asTable" ref="scroller" class="relative overflow-x-auto">
        <svg :viewBox="`0 0 ${W} ${H}`" class="block w-full min-w-[560px]" role="img" :aria-label="`مبيعات يومية من ${r.from} إلى ${r.to}`" @mouseleave="hover = null">
          <g v-for="t in chart.ticks" :key="t.v">
            <line :x1="PAD.start" :x2="W - PAD.end" :y1="t.y" :y2="t.y" stroke="#D8CAB0" :stroke-width="t.v === 0 ? 1 : 0.6" />
            <text :x="W - PAD.end + 6" :y="t.y + 4" font-size="11" fill="#5F564B" text-anchor="start">{{ formatNumber(t.v) }}</text>
          </g>
          <g v-for="b in chart.bars" :key="b.date">
            <path v-if="b.h > 0" :d="barPath(b)" :fill="hover?.date === b.date ? '#0E0D0C' : '#3B342C'" />
            <rect :x="b.x - 1" :y="PAD.top" :width="b.w + 2" :height="chart.base - PAD.top" fill="transparent" tabindex="0" role="img" :aria-label="`${dayFmt.format(new Date(b.date))}: ${formatPrice(b.sales)}، ${formatNumber(b.orders)} طلب`" @mouseenter="hover = b" @focus="hover = b" @blur="hover = null" />
            <text v-if="b.label" :x="b.x + b.w / 2" :y="H - 8" font-size="11" fill="#5F564B" text-anchor="middle">{{ dayFmt.format(new Date(b.date)) }}</text>
          </g>
        </svg>
        <div v-if="hover" class="pointer-events-none absolute top-0 z-10 border border-sand bg-paper px-3 py-2 text-[0.8125rem] shadow-[0_18px_40px_-20px_rgba(14,13,12,.45)]" :style="{ insetInlineEnd: `${Math.min(80, Math.max(0, ((W - hover.x) / W) * 100))}%` }" role="status">
          <span class="block font-medium">{{ dayFmt.format(new Date(hover.date)) }}</span>
          <span class="block tabular">{{ formatPrice(hover.sales) }} · {{ formatNumber(hover.orders) }} طلب</span>
        </div>
      </div>
      <div v-else class="max-h-80 overflow-auto">
        <table class="w-full border-collapse text-[0.9375rem]">
          <thead><tr class="border-b border-ink text-[0.8125rem] text-muted"><th class="py-2 text-start font-normal">اليوم</th><th class="text-start font-normal">الطلبات</th><th class="text-end font-normal">المبيعات</th></tr></thead>
          <tbody><tr v-for="d in r.daily" :key="d.date" class="border-b border-sand"><td class="py-1.5">{{ dayFmt.format(new Date(d.date)) }}</td><td class="tabular">{{ formatNumber(d.orders) }}</td><td class="text-end tabular">{{ formatPrice(d.sales) }}</td></tr></tbody>
        </table>
      </div>
    </section>

    <div class="mt-12 grid gap-12 xl:grid-cols-2">
      <section aria-labelledby="top-h">
        <h2 id="top-h" class="m-0 mb-4 font-display text-2xl">الأكثر مبيعاً</h2>
        <p v-if="!r.topProducts.length" class="m-0 text-muted">لا مبيعات في هذه الفترة.</p>
        <ol class="m-0 flex list-none flex-col gap-3 p-0">
          <li v-for="(p, i) in share(r.topProducts, 'qty')" :key="i" class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1">
            <span class="min-w-0 truncate">{{ p.name }} <span class="text-muted">· {{ formatNumber(p.ml) }} مل</span></span>
            <span class="text-[0.875rem] tabular">{{ formatNumber(p.qty) }} قطعة · {{ formatPrice(p.total) }}</span>
            <span class="col-span-2 h-2 bg-sand/50"><span class="block h-full rounded-e-[2px] bg-smoke" :style="{ width: `${(p.qty / r.topProducts[0].qty) * 100}%` }" /></span>
          </li>
        </ol>
      </section>
      <section class="flex flex-col gap-10">
        <div aria-labelledby="gov-h">
          <h2 id="gov-h" class="m-0 mb-4 font-display text-2xl">المحافظات</h2>
          <p v-if="!r.governorates.length" class="m-0 text-muted">لا طلبات بعد.</p>
          <ol class="m-0 flex list-none flex-col gap-3 p-0">
            <li v-for="g in share(r.governorates.slice(0, 8), 'orders')" :key="g.name" class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1">
              <span>{{ g.name }}</span><span class="text-[0.875rem] tabular">{{ formatNumber(g.orders) }} طلب · {{ formatPrice(g.sales) }}</span>
              <span class="col-span-2 h-2 bg-sand/50"><span class="block h-full rounded-e-[2px] bg-smoke" :style="{ width: `${(g.orders / r.governorates[0].orders) * 100}%` }" /></span>
            </li>
          </ol>
        </div>
        <div aria-labelledby="pay-h">
          <h2 id="pay-h" class="m-0 mb-4 font-display text-2xl">طرق الدفع</h2>
          <p v-if="!r.paymentMethods.length" class="m-0 text-muted">لا طلبات بعد.</p>
          <ol class="m-0 flex list-none flex-col gap-3 p-0">
            <li v-for="m in share(r.paymentMethods, 'orders')" :key="m.method" class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1">
              <span>{{ PAY[m.method] }}</span><span class="text-[0.875rem] tabular">{{ formatNumber(Math.round(m.pct * 100)) }}٪ · {{ formatNumber(m.orders) }} طلب</span>
              <span class="col-span-2 h-2 bg-sand/50"><span class="block h-full rounded-e-[2px] bg-smoke" :style="{ width: `${m.pct * 100}%` }" /></span>
            </li>
          </ol>
        </div>
      </section>
    </div>
  </template>
</template>
