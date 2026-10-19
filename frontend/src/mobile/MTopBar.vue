<script setup>
import { useRouter } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'

// The phone screen's own top bar: back, the screen's title, and room for one
// action at the end. Sticks to the top while the screen scrolls under it.
defineProps({
  title: { type: String, default: '' },
  sub: { type: String, default: '' },
  back: { type: Boolean, default: true },
})
const router = useRouter()
function goBack() {
  // a deep link has no in-app history: go home instead of leaving the site
  if (history.state?.back) router.back()
  else router.push('/')
}
</script>

<template>
  <header class="bar">
    <button v-if="back" class="round" aria-label="رجوع" @click="goBack"><AppIcon name="arrow-right" :size="20" /></button>
    <div class="min-w-0 flex-1">
      <slot name="title">
        <h1 class="m-0 truncate font-display text-[1.375rem] font-bold leading-[1.3]">{{ title }}</h1>
        <p v-if="sub" class="m-0 text-[0.75rem] text-muted">{{ sub }}</p>
      </slot>
    </div>
    <slot />
  </header>
</template>

<style scoped>
.bar { position: sticky; top: 0; z-index: 30; display: flex; align-items: center; gap: 10px; min-height: 58px; padding: calc(6px + env(safe-area-inset-top, 0px)) 16px 6px; background: color-mix(in srgb, var(--color-ivory) 92%, transparent); backdrop-filter: blur(14px); }
.round { display: grid; place-items: center; flex: none; width: 40px; height: 40px; border-radius: 999px; background: var(--color-paper); color: var(--color-ink); }
.round:active { transform: scale(0.94); }
</style>
