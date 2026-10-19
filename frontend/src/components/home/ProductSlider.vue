<script setup>
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import ColorCard from '@/components/catalog/ColorCard.vue'
import { SCENES } from '@/data/photos'
import { familiesOf, scenesFor } from '@/utils/scenes'
import { formatNumber } from '@/utils/format'

// Hand-built slider: native scroll-snap (touch, trackpad, keyboard) + buttons.
// Each perfume is a colour card; the row picks the colours so neighbours differ.
const props = defineProps({
  products: { type: Array, required: true },
  palette: { type: Object, required: true },
  title: { type: String, required: true },
})
const id = useId()
const scenes = computed(() => scenesFor(props.products))
const family = (p) => SCENES[familiesOf(p)[0]]?.label || ''
const track = ref(null)
const atStart = ref(true)
const atEnd = ref(false)

function update() {
  const el = track.value
  if (!el) return
  const x = Math.abs(el.scrollLeft) // RTL scrollLeft is ≤ 0 in modern browsers
  atStart.value = x < 4
  atEnd.value = x + el.clientWidth >= el.scrollWidth - 4
}
function step(dir) {
  const el = track.value
  const card = el.querySelector('li')
  const w = card ? card.getBoundingClientRect().width + 16 : el.clientWidth
  // dir 1 = forward (visually left in RTL)
  const calm = matchMedia('(prefers-reduced-motion: reduce)').matches
  el.scrollBy({ left: -dir * w * Math.max(1, Math.floor(el.clientWidth / w)), behavior: calm ? 'auto' : 'smooth' })
}
let ro
onMounted(() => {
  update()
  ro = new ResizeObserver(update)
  ro.observe(track.value)
})
onBeforeUnmount(() => ro?.disconnect())
</script>

<template>
  <div class="flex flex-col gap-5" role="region" aria-roledescription="سلايدر" :aria-labelledby="`${id}-t`">
    <div class="flex items-end justify-between gap-4">
      <h3 :id="`${id}-t`" class="m-0 font-display text-[1.625rem] font-bold md:text-[2rem]">{{ title }}</h3>
      <div class="flex gap-2">
        <button class="nav-btn" :disabled="atStart" aria-label="السابق" :aria-controls="`${id}-track`" @click="step(-1)"><AppIcon name="chevron-right" /></button>
        <button class="nav-btn" :disabled="atEnd" aria-label="التالي" :aria-controls="`${id}-track`" @click="step(1)"><AppIcon name="chevron-left" /></button>
      </div>
    </div>
    <ul :id="`${id}-track`" ref="track" class="track m-0 flex list-none gap-4 overflow-x-auto p-0" tabindex="0" @scroll.passive="update">
      <li v-for="(p, i) in products" :key="p.slug" class="slide shrink-0" :aria-label="`${formatNumber(i + 1)} من ${formatNumber(products.length)}`">
        <ColorCard :product="p" :scene="scenes[i]" :family="family(p)" />
      </li>
    </ul>
  </div>
</template>

<style scoped>
.track { scroll-snap-type: inline mandatory; scrollbar-width: none; scroll-padding-inline: 2px; }
.track::-webkit-scrollbar { display: none; }
.track { padding-block: 12px 18px; margin-block: -12px -18px; } /* room for a card lifting on hover */
.slide { width: clamp(230px, 23%, 290px); scroll-snap-align: start; }
@media (max-width: 640px) { .slide { width: 68%; } }
.nav-btn {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 999px;
  background: var(--color-paper);
  color: var(--color-ink);
  transition: transform 0.2s var(--ease-pop), opacity 0.2s;
}
.nav-btn:hover:not(:disabled) { transform: scale(1.06); }
.nav-btn:disabled { opacity: 0.3; cursor: default; }
</style>
