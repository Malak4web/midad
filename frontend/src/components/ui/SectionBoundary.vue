<script setup>
import { onErrorCaptured, ref } from 'vue'

// Data-driven sections (admin-edited spotlights) render inside this: if one
// throws, it disappears quietly instead of blanking the whole page.
const failed = ref(false)
onErrorCaptured((err) => {
  failed.value = true
  console.error('[section]', err)
  return false
})
</script>

<template>
  <slot v-if="!failed" />
</template>
