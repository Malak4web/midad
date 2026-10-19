<script setup>
import { computed } from 'vue'
import { formatPrice } from '@/utils/format'

// Totals exactly as the server priced them.
const props = defineProps({
  summary: { type: Object, required: true },
  coupon: { type: Object, default: null },
  shippingHint: { type: String, default: 'تُحسب عند اختيار المحافظة' },
})
// read aloud when the governorate (or a coupon) changes the bill; sighted users see the rows change
const spoken = computed(() =>
  props.summary.shipping === null ? '' : `الشحن ${props.summary.shipping === 0 ? 'مجاني' : formatPrice(props.summary.shipping)}، الإجمالي ${formatPrice(props.summary.total)}`,
)
</script>

<template>
  <dl class="m-0 flex flex-col gap-2.5 text-[0.9375rem]">
    <div class="flex justify-between"><dt>المجموع الفرعي</dt><dd class="m-0 tabular">{{ formatPrice(summary.subtotal) }}</dd></div>
    <div v-if="summary.discount" class="flex justify-between text-gold-ink"><dt>{{ coupon?.label || 'الخصم' }}</dt><dd class="m-0 tabular">− {{ formatPrice(summary.discount) }}</dd></div>
    <div v-if="summary.credit" class="flex justify-between text-gold-ink"><dt>رصيد مجموعة الاكتشاف</dt><dd class="m-0 tabular">− {{ formatPrice(summary.credit) }}</dd></div>
    <div class="flex justify-between">
      <dt>الشحن</dt>
      <dd class="m-0 tabular">{{ summary.shipping === null ? shippingHint : summary.shipping === 0 ? 'مجاني' : formatPrice(summary.shipping) }}</dd>
    </div>
    <div v-if="summary.gift" class="flex justify-between"><dt>تغليف الهدية</dt><dd class="m-0 tabular">{{ formatPrice(summary.gift) }}</dd></div>
    <div class="mt-2 flex items-baseline justify-between border-t border-sand pt-3">
      <dt class="font-medium">الإجمالي</dt>
      <dd class="m-0 font-display text-3xl tabular">{{ formatPrice(summary.total) }}</dd>
    </div>
  </dl>
  <p class="sr-only" aria-live="polite">{{ spoken }}</p>
</template>
