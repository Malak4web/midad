<script setup>
import { ref, useId } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'

// Notes and pyramid tiers: type a note, press Enter (or ،) to add it.
const model = defineModel({ type: Array, default: () => [] })
const props = defineProps({ label: { type: String, required: true }, max: { type: Number, default: 12 }, suggestions: { type: Array, default: () => [] } })
const id = useId()
const draft = ref('')
function add() {
  const v = draft.value.replace(/[،,]/g, '').trim()
  if (v && !model.value.includes(v) && model.value.length < props.max) model.value = [...model.value, v]
  draft.value = ''
}
function key(e) {
  if (e.key === 'Enter' || e.key === '،' || e.key === ',') { e.preventDefault(); add() }
  if (e.key === 'Backspace' && !draft.value && model.value.length) model.value = model.value.slice(0, -1)
}
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label :for="id" class="text-[0.875rem]">{{ label }}</label>
    <div class="flex min-h-11 flex-wrap items-center gap-1.5 rounded-xl border border-sand bg-paper px-2 py-1.5 focus-within:border-ink">
      <span v-for="t in model" :key="t" class="inline-flex items-center gap-1 rounded-full bg-ink px-2.5 py-0.5 text-[0.8125rem] text-ivory">
        {{ t }}<button type="button" class="text-sand" :aria-label="`إزالة ${t}`" @click="model = model.filter((x) => x !== t)"><AppIcon name="close" :size="12" /></button>
      </span>
      <input :id="id" v-model="draft" :list="`${id}-l`" class="min-w-24 flex-1 bg-transparent outline-none" @keydown="key" @blur="add" />
      <datalist :id="`${id}-l`"><option v-for="s in suggestions" :key="s" :value="s" /></datalist>
    </div>
  </div>
</template>
