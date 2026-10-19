<script setup>
import AppIcon from '@/components/ui/AppIcon.vue'

// A warm-black ticker: the order's promises run past in ivory, saffron marks between. Screen readers
// get the plain list once; reduced motion keeps it still.
defineProps({ items: { type: Array, default: () => [] } })
const icons = ['cash', 'drop', 'truck']
</script>

<template>
  <div class="ticker bg-ink text-ivory">
    <ul class="sr-only" aria-label="مزايا الطلب"><li v-for="t in items" :key="t">{{ t }}</li></ul>
    <div class="run" aria-hidden="true">
      <span v-for="n in 4" :key="n" class="lane">
        <span v-for="(t, i) in items" :key="t" class="item"><AppIcon :name="icons[i % icons.length]" :size="16" />{{ t }}<span class="star">✦</span></span>
      </span>
    </div>
  </div>
</template>

<style scoped>
.ticker { overflow: hidden; font-size: 0.8125rem; font-weight: 600; }
.run { display: flex; width: max-content; animation: run 40s linear infinite; }
.lane { display: flex; }
.item { display: inline-flex; align-items: center; gap: 8px; padding: 9px 0; white-space: nowrap; }
.star { margin: 0 18px; color: var(--color-gold-light); }
.item :deep(svg) { color: var(--color-gold-light); }
/* four identical lanes: moving by half the run loops without a seam */
@keyframes run { to { transform: translateX(50%); } }
@media (prefers-reduced-motion: reduce) { .run { animation: none; } }
</style>
