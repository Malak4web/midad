<script setup>
import AppIcon from '@/components/ui/AppIcon.vue'
import { useUiStore } from '@/stores/ui'
const ui = useUiStore()
defineProps({ aboveTabs: { type: Boolean, default: false } })
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 z-[60] flex flex-col items-center gap-2 px-4" :class="aboveTabs ? 'top-[calc(12px+env(safe-area-inset-top,0px))] lg:top-auto lg:bottom-4' : 'bottom-4'" role="status" aria-live="polite">
    <TransitionGroup enter-from-class="-translate-y-3 opacity-0 lg:translate-y-3" leave-to-class="opacity-0" enter-active-class="transition duration-300 ease-[var(--ease-ink)]" leave-active-class="transition duration-200">
      <div v-for="t in ui.toasts" :key="t.id" class="rounded-full pointer-events-auto flex max-w-md items-center gap-3 bg-ink px-4 py-3 text-ivory shadow-[0_12px_30px_-12px_rgba(14,13,12,.6)]">
        <AppIcon name="check" :size="18" class="shrink-0 text-gold-light" />
        <span class="text-[0.9375rem]">{{ t.message }}</span>
        <button v-if="t.action" class="ms-2 text-[0.875rem] text-gold-light underline" @click="t.action.run(); ui.dismiss(t.id)">{{ t.action.label }}</button>
      </div>
    </TransitionGroup>
  </div>
</template>
