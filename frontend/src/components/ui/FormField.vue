<script setup>
import { useId } from 'vue'

const model = defineModel({ type: [String, Number, null], default: '' })
defineProps({
  label: { type: String, required: true },
  type: { type: String, default: 'text' },
  error: { type: [String, Array], default: '' },
  hint: { type: String, default: '' },
  autocomplete: { type: String, default: undefined },
  inputmode: { type: String, default: undefined },
  required: { type: Boolean, default: false },
  maxlength: { type: Number, default: undefined },
  dir: { type: String, default: undefined },
  textarea: { type: Boolean, default: false },
})
const id = useId()
</script>

<template>
  <div class="flex min-w-0 flex-col gap-1.5">
    <label :for="id" class="text-[0.875rem]">{{ label }}<span v-if="required" class="text-gold-ink" aria-hidden="true"> *</span></label>
    <textarea
      v-if="textarea"
      :id="id"
      v-model="model"
      rows="3"
      :maxlength="maxlength"
      :aria-invalid="!!error"
      :aria-describedby="error || hint ? `${id}-d` : undefined"
      class="field resize-y py-2"
    />
    <input
      v-else
      :id="id"
      v-model="model"
      :type="type"
      :autocomplete="autocomplete"
      :inputmode="inputmode"
      :required="required"
      :maxlength="maxlength"
      :dir="dir"
      :aria-invalid="!!error"
      :aria-describedby="error || hint ? `${id}-d` : undefined"
      class="field h-11"
    />
    <p v-if="error || hint" :id="`${id}-d`" class="m-0 text-[0.8125rem]" :class="error ? 'text-danger' : 'text-muted'">{{ Array.isArray(error) ? error[0] : error || hint }}</p>
  </div>
</template>

<style scoped>
.field {
  width: 100%;
  min-width: 0;
  border: 1px solid color-mix(in srgb, var(--color-ink) 25%, transparent);
  background: var(--color-paper);
  padding-inline: 12px;
  outline: none;
  transition: border-color 0.2s;
}
.field:focus { border-color: var(--color-ink); }
.field[aria-invalid='true'] { border-color: var(--color-danger); }
</style>
