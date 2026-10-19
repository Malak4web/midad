<script setup>
import LogoGlyph from './LogoGlyph.vue'

// Approved colorways (guide p.6): ink on ivory, gold on black, ivory on smoke, black mono.
defineProps({
  height: { type: Number, default: 64 }, // the guide's on-screen minimum (p.8)
  wordmark: { type: Boolean, default: false },
  kasra: { type: String, default: '#B08D57' },
  label: { type: String, default: 'مداد' },
})
</script>

<template>
  <span class="inline-flex items-center gap-3" :aria-label="label" role="img">
    <svg :height="height" :width="(height * 100) / 150" viewBox="0 0 100 150" aria-hidden="true">
      <LogoGlyph :kasra="kasra" :spray="height >= 32" />
    </svg>
    <span v-if="wordmark" class="font-latin text-[0.8125rem] tracking-[0.34em]" aria-hidden="true">MIDAD</span>
  </span>
</template>
