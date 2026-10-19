<script setup>
import { computed } from 'vue'
import { STATUS } from '@/services/admin'

// State is encoded in shape and tone, not colour alone: filled = needs action.
const props = defineProps({ status: { type: String, required: true } })
const tone = computed(() => ({
  placed: 'act', pending_payment: 'wait', confirmed: 'go', preparing: 'go', shipped: 'go', delivered: 'done', cancelled: 'off', returned: 'off',
}[props.status] || 'go'))
</script>

<template>
  <span class="pill inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[0.8125rem]" :class="`t-${tone}`">
    <span class="dot" aria-hidden="true" />{{ STATUS[status] || status }}
  </span>
</template>

<style scoped>
.dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.t-act { background: var(--color-ink); color: var(--color-ivory); border-color: var(--color-ink); }
.t-wait { border-style: dashed; color: #8a6a2e; border-color: currentColor; }
.t-go { color: var(--color-ink); border-color: color-mix(in srgb, var(--color-ink) 35%, transparent); }
.t-done { color: #2f5d3a; border-color: currentColor; }
.t-off { color: var(--color-muted); border-color: color-mix(in srgb, var(--color-muted) 40%, transparent); text-decoration: line-through; }
</style>
