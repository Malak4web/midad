<script setup>
import { useId } from 'vue'
import { formatMl, formatPrice } from '@/utils/format'

const model = defineModel({ type: Number, default: 50 })
defineProps({
  variants: { type: Array, required: true },
  legend: { type: String, default: 'الحجم' },
})
const id = useId()
</script>

<template>
  <fieldset class="m-0 border-0 p-0">
    <legend class="mb-2 p-0 text-[0.875rem] opacity-80">{{ legend }}</legend>
    <div class="grid grid-cols-4 gap-2">
      <label
        v-for="v in variants"
        :key="v.ml"
        :for="`${id}-${v.ml}`"
        class="size relative flex cursor-pointer flex-col items-center gap-0.5 border px-1 py-2 text-center transition-colors"
        :class="[model === v.ml ? 'is-on' : '', v.stock <= 0 ? 'cursor-not-allowed opacity-45' : '']"
      >
        <input :id="`${id}-${v.ml}`" v-model="model" class="sr-only" type="radio" :name="id" :value="v.ml" :disabled="v.stock <= 0" />
        <span class="text-[0.9375rem] font-medium tabular">{{ formatMl(v.ml) }}</span>
        <span class="text-[0.75rem] opacity-80">{{ v.stock > 0 ? formatPrice(v.price) : 'نفد' }}</span>
      </label>
    </div>
  </fieldset>
</template>

<style scoped>
.size { border-color: transparent; background: var(--color-ivory); border-radius: 18px; }
.size:hover { background: var(--color-linen); }
.size.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.size:has(input:focus-visible) { outline: 2px solid currentColor; outline-offset: 2px; }
</style>
