<script setup>
import { computed } from 'vue'
import { pose as poseOf } from '@/data/attar'

// The Attar dropping in elsewhere in the store: a still pose in a small arch,
// with one line in his voice and, if given, somewhere to go next.
const props = defineProps({
  pose: { type: String, default: 'portrait' }, // thinking · hello · samples · gift · approve · heart · offer · portrait · smelling
  line: { type: String, required: true },
  to: { type: [String, Object], default: null },
  action: { type: String, default: '' },
  size: { type: Number, default: 128 },
  dark: { type: Boolean, default: false }, // on an ink ground
})
const pic = computed(() => poseOf(props.pose))
</script>

<template>
  <aside class="cameo flex items-end gap-4" :class="dark && 'is-dark'" aria-label="خبير العطور">
    <span class="arch shrink-0" :style="{ width: `${size}px`, height: `${size * 1.2}px` }">
      <img v-if="pic" :src="pic.src" :srcset="pic.srcset" :sizes="`${size}px`" alt="" loading="lazy" decoding="async" />
    </span>
    <div class="flex min-w-0 flex-col items-start gap-2 pb-2">
      <p class="bubble m-0">{{ line }}</p>
      <RouterLink v-if="to && action" :to="to" class="text-[0.9375rem] underline" :class="dark ? 'text-ivory' : 'text-ink'">{{ action }}</RouterLink>
    </div>
  </aside>
</template>

<style scoped>
.arch {
  position: relative;
  overflow: hidden;
  border-radius: 999px 999px 0 0 / 48% 48% 0 0;
  background: linear-gradient(180deg, #d9c19f, #e8d5b9);
  border-bottom: 3px solid #4a3424;
  box-shadow: inset 0 0 0 1px rgba(176, 141, 87, 0.6);
}
.arch img { position: absolute; inset: 6% 0 0; width: 100%; height: 100%; object-fit: cover; object-position: 50% 8%; }
.bubble {
  position: relative;
  max-width: 34ch;
  padding: 10px 14px;
  background: #fbf6ec;
  color: var(--color-ink);
  border: 1px solid var(--color-sand);
  border-end-start-radius: 0;
  font-family: var(--font-display);
  font-size: 1.125rem;
  line-height: 1.6;
}
.is-dark .bubble { border-color: transparent; }
</style>
