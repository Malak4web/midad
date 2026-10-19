<script setup>
import { computed } from 'vue'
import { studioFor } from '@/data/photos'

// The bottle as photographed in the studio, for thumbnails (cart, search,
// finder results, empty states). An uploaded product image wins when given.
const props = defineProps({
  size: { type: Number, default: 56 },
  dark: { type: Boolean, default: false },
  image: { type: Object, default: null },
})
const pic = computed(() => props.image || studioFor(props.dark))
</script>

<template>
  <img :src="pic.src" :srcset="pic.srcset" :sizes="`${size}px`" :width="size" :height="size" alt="" loading="lazy" decoding="async" class="shrink-0 object-cover" :style="{ width: `${size}px`, height: `${size}px` }" />
</template>
