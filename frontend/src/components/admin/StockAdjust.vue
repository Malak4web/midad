<script setup>
import { ref } from 'vue'
import { admin } from '@/services/admin'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'
import { formatNumber } from '@/utils/format'

// Inline stock change for one size; every change is a logged movement.
const props = defineProps({ variant: { type: Object, required: true }, label: { type: String, default: '' } })
const emit = defineEmits(['saved'])
const ui = useUiStore()
const change = ref('')
const reason = ref('restock')
const note = ref('')
const busy = ref(false)
const history = ref(null)
const REASONS = { restock: 'توريد', adjustment: 'جرد / تصحيح', damage: 'تالف' }
const when = new Intl.DateTimeFormat('ar-EG', { dateStyle: 'short', timeStyle: 'short' })

async function save() {
  const n = parseInt(change.value, 10)
  if (!n) return
  busy.value = true
  try {
    const res = await admin.adjustStock(props.variant.id, n, reason.value, note.value || null)
    emit('saved', res.data)
    ui.toast(`${props.label}: ${n > 0 ? '+' : ''}${formatNumber(n)}`)
    change.value = ''
    note.value = ''
    if (history.value) history.value = (await admin.movements(props.variant.id)).data
  } catch (e) {
    ui.toast(firstError(e))
  } finally {
    busy.value = false
  }
}
async function toggleHistory() {
  history.value = history.value ? null : (await admin.movements(props.variant.id)).data
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <form class="flex flex-wrap items-center gap-2" @submit.prevent="save">
      <label class="sr-only" :for="`chg-${variant.id}`">الكمية لـ {{ label }}</label>
      <input :id="`chg-${variant.id}`" v-model="change" type="number" step="1" class="h-9 w-24 rounded-xl border border-sand bg-paper px-2 tabular" placeholder="+٢٠ / -٣" />
      <select v-model="reason" class="h-9 rounded-xl border border-sand bg-paper px-2" :aria-label="`سبب التغيير لـ ${label}`"><option v-for="(l, k) in REASONS" :key="k" :value="k">{{ l }}</option></select>
      <input v-model="note" maxlength="255" class="h-9 w-40 rounded-xl border border-sand bg-paper px-2" placeholder="ملاحظة" :aria-label="`ملاحظة لـ ${label}`" />
      <button class="rounded-full h-9 border border-ink px-3 disabled:opacity-40" :disabled="busy || !parseInt(change, 10)">تسجيل</button>
      <button type="button" class="text-[0.8125rem] text-muted underline" @click="toggleHistory">{{ history ? 'إخفاء السجل' : 'السجل' }}</button>
    </form>
    <ul v-if="history" class="m-0 list-none p-0 text-[0.8125rem]">
      <li v-for="(m, i) in history" :key="i" class="flex gap-3 border-b border-sand py-1">
        <span class="w-14 tabular" :class="m.change > 0 ? 'text-[#2f5d3a]' : 'text-danger'" dir="ltr">{{ m.change > 0 ? '+' : '' }}{{ m.change }}</span>
        <span>{{ { sale: 'بيع', release: 'إلغاء', return: 'مرتجع', ...REASONS }[m.reason] || m.reason }}</span>
        <span class="text-muted">{{ m.note }}</span>
        <span class="ms-auto text-muted">{{ when.format(new Date(m.at)) }}</span>
      </li>
      <li v-if="!history.length" class="py-1 text-muted">لا حركات بعد.</li>
    </ul>
  </div>
</template>
