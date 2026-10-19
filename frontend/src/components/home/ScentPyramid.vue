<script setup>
import { computed } from 'vue'

// A perfume can be saved without its pyramid; then only the tiers it has show,
// and nothing renders at all when it has none (it must never break a page).
const props = defineProps({ pyramid: { type: Object, default: null } })
const TIERS = [
  ['top', 'القمة', 'TOP', 'أول ربع ساعة'],
  ['heart', 'القلب', 'HEART', 'من ساعة إلى أربع'],
  ['base', 'القاعدة', 'BASE', 'ما يبقى للمساء'],
]
const tiers = computed(() => TIERS.filter(([k]) => props.pyramid?.[k]?.length))
</script>

<template>
  <dl v-if="tiers.length" class="m-0 grid gap-0">
    <div v-for="[k, ar, en, when] in tiers" :key="k" class="tier grid grid-cols-[86px_1fr] items-baseline gap-4 py-2.5">
      <dt class="flex flex-col">
        <span class="text-[0.9375rem] font-medium">{{ ar }}</span>
        <span class="font-meta text-[0.75rem] tracking-[0.2em] opacity-70" dir="ltr">{{ en }}</span>
      </dt>
      <dd class="m-0 flex flex-col">
        <span class="font-display text-[1.25rem] leading-snug md:text-[1.375rem]">{{ pyramid[k].join('، ') }}</span>
        <span class="text-[0.8125rem] opacity-70">{{ when }}</span>
      </dd>
    </div>
  </dl>
</template>

<style scoped>
.tier + .tier { border-top: 1px solid color-mix(in srgb, currentColor 18%, transparent); }
</style>
