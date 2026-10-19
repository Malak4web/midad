<script setup>
// The official Midad mark, traced from the client's logo file
// (assets/brand/midad-logo-source.png → scripts/trace-logo.mjs). Never redraw it.
// Draws centred inside a 100×150 box; use within an <svg> or <g transform>.
// Ink strokes take currentColor; the spray and kasra take `kasra` (gold by default).
import logo from '@/assets/brand/midad-logo.json'

defineProps({
  kasra: { type: String, default: '#B08D57' },
  spray: { type: Boolean, default: true },
})

const [x, y, w, h] = logo.viewBox.split(' ').map(Number)
const s = 150 / h
const transform = `translate(${(100 - w * s) / 2} 0) scale(${s}) translate(${-x} ${-y})`
</script>

<template>
  <g :transform="transform">
    <path :d="logo.ink" fill="currentColor" />
    <path v-if="spray" :d="logo.gold" :fill="kasra" />
  </g>
</template>
