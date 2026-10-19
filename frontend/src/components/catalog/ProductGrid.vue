<script setup>
import { computed } from 'vue'
import ColorCard from './ColorCard.vue'
import { SCENES } from '@/data/photos'
import { familiesOf, scenesFor } from '@/utils/scenes'

// Catalog pages: colour cards in a grid; neighbours along a row differ in colour.
const props = defineProps({
  products: { type: Array, required: true },
  loading: { type: Boolean, default: false },
})
const scenes = computed(() => scenesFor(props.products))
const family = (p) => SCENES[familiesOf(p)[0]]?.label || ''
</script>

<template>
  <ul class="m-0 grid list-none grid-cols-2 gap-3 p-0 md:grid-cols-3 md:gap-4 xl:grid-cols-4" :aria-busy="loading">
    <li v-for="(p, i) in products" :key="p.slug" class="min-w-0"><ColorCard :product="p" :scene="scenes[i]" :family="family(p)" /></li>
    <template v-if="loading && !products.length">
      <li v-for="n in 8" :key="'s' + n" class="aspect-[4/6] animate-pulse rounded-[28px] bg-paper" aria-hidden="true" />
    </template>
  </ul>
</template>
