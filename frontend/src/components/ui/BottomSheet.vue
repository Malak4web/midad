<script setup>
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'

// The phone's modal surface: rises from the bottom, within thumb reach.
// Closes by dragging the handle (or the header) down, the backdrop, Escape, or ×.
// Focus moves in, is trapped, and returns to whatever opened it.
const open = defineModel({ type: Boolean, default: false })
const props = defineProps({
  title: { type: String, required: true },
  full: { type: Boolean, default: false }, // full height (search)
})
const id = useId()
const panel = ref(null)
const drag = ref(0)
let lastFocus = null
let startY = null
let lockedY = 0

function lock(on) {
  const b = document.body
  if (on) {
    lockedY = scrollY
    Object.assign(b.style, { position: 'fixed', top: `-${lockedY}px`, insetInline: '0', overflow: 'hidden' })
  } else {
    Object.assign(b.style, { position: '', top: '', insetInline: '', overflow: '' })
    scrollTo(0, lockedY)
  }
}

watch(open, async (v) => {
  if (v) {
    lastFocus = document.activeElement
    lock(true)
    await nextTick()
    // a full sheet (search) opens on its field; others on the panel itself
    const first = panel.value?.querySelector('input, textarea')
    ;(props.full && first ? first : panel.value)?.focus()
  } else {
    lock(false)
    drag.value = 0
    lastFocus?.focus?.()
  }
})
onBeforeUnmount(() => open.value && lock(false))

function key(e) {
  if (e.key === 'Escape') open.value = false
  if (e.key !== 'Tab') return
  const f = [...panel.value.querySelectorAll('a[href], button:not(:disabled), input, select, textarea')].filter((n) => n.offsetParent)
  if (!f.length) return
  if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f.at(-1).focus() }
  else if (!e.shiftKey && document.activeElement === f.at(-1)) { e.preventDefault(); f[0].focus() }
}

// drag to dismiss: follows the finger, closes past a third of a short swipe
function down(e) {
  startY = e.clientY
  e.currentTarget.setPointerCapture?.(e.pointerId)
}
function move(e) {
  if (startY === null) return
  drag.value = Math.max(0, e.clientY - startY)
}
function up() {
  if (startY === null) return
  startY = null
  if (drag.value > 90) open.value = false
  else drag.value = 0
}
defineExpose({ panel })
</script>

<template>
  <Teleport to="body">
    <Transition name="sheet">
      <div v-if="open" class="sheet-backdrop fixed inset-0 z-[55] bg-ink/45" @click.self="open = false">
        <section
          ref="panel"
          class="sheet absolute inset-x-0 bottom-0 flex flex-col bg-ivory outline-none"
          :class="full ? 'h-[100dvh]' : 'max-h-[92dvh]'"
          :style="{ transform: drag ? `translateY(${drag}px)` : undefined, transition: drag ? 'none' : undefined }"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="`${id}-t`"
          tabindex="-1"
          @keydown="key"
        >
          <header class="shrink-0 touch-none select-none" @pointerdown="down" @pointermove="move" @pointerup="up" @pointercancel="up">
            <span v-if="!full" data-handle class="mx-auto mt-2.5 block h-1 w-10 rounded-full bg-ink/20" aria-hidden="true" />
            <div class="flex items-center justify-between gap-3 px-5 pb-3" :class="full ? 'pt-[calc(12px+env(safe-area-inset-top,0px))]' : 'pt-3'">
              <h2 :id="`${id}-t`" class="m-0 font-display text-[1.625rem] leading-tight">{{ title }}</h2>
              <button class="-me-2 grid h-11 w-11 place-items-center" aria-label="إغلاق" @click="open = false"><AppIcon name="close" :size="22" /></button>
            </div>
          </header>
          <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pb-[calc(20px+env(safe-area-inset-bottom,0px))]">
            <slot />
          </div>
          <slot name="footer" />
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.sheet { box-shadow: 0 -18px 50px -20px rgba(14, 13, 12, 0.5); transition: transform 0.38s cubic-bezier(0.22, 0.8, 0.24, 1); }
.sheet-enter-active { transition: background-color 0.38s; }
.sheet-leave-active { transition: background-color 0.26s; }
.sheet-enter-from, .sheet-leave-to { background-color: transparent; }
.sheet-enter-from .sheet, .sheet-leave-to .sheet { transform: translateY(100%); }
.sheet-leave-active .sheet { transition-duration: 0.26s; }
</style>
