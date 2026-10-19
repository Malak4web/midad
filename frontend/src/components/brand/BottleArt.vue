<script setup>
import { computed, useId } from 'vue'
import LogoGlyph from './LogoGlyph.vue'

// Drawn from the guide's technical drawings (pp.16–19), units in mm.
const SPECS = {
  10: { body: [17, 80], collar: null, cap: [17, 26], logo: 12, base: 3, oz: '0.34', slim: true },
  30: { body: [38, 58], collar: [16, 8], cap: [25, 13], logo: 24, base: 8, oz: '1.0' },
  50: { body: [45, 68], collar: [18, 9], cap: [27, 14], logo: 28, base: 9, oz: '1.7' },
  100: { body: [55, 82], collar: [22, 11], cap: [32, 16], logo: 34, base: 10, oz: '3.4' },
}

const props = defineProps({
  ml: { type: Number, default: 100 },
  width: { type: Number, default: 120 },
  label: { type: Boolean, default: true },
  glow: { type: String, default: '' },
})

const id = useId()
const s = computed(() => SPECS[props.ml] || SPECS[100])
const box = computed(() => {
  const [bw, bh] = s.value.body
  const capH = s.value.cap[1] + (s.value.collar ? s.value.collar[1] : 0)
  return { w: bw + 2, h: bh + capH + 1 }
})
const height = computed(() => (props.width * box.value.h) / box.value.w)
const geo = computed(() => {
  const { body, collar, cap, logo, base, slim } = s.value
  const cx = box.value.w / 2
  const capY = 0.5
  const collarY = capY + cap[1]
  const bodyY = collarY + (collar ? collar[1] : 0)
  return {
    cx,
    cap: { x: cx - cap[0] / 2, y: capY, w: cap[0], h: cap[1], r: slim ? 1.4 : 2.2 },
    collar: collar && { x: cx - collar[0] / 2, y: collarY, w: collar[0], h: collar[1] },
    body: { x: cx - body[0] / 2, y: bodyY, w: body[0], h: body[1], r: slim ? 1.6 : 3.4 },
    base, // glass base thickness from the drawings (pp.16–19)
    logoH: logo,
    logoY: bodyY + body[1] * (slim ? 0.2 : 0.18),
    textY: bodyY + body[1] * (slim ? 0.2 : 0.18) + logo + (slim ? 3.5 : 5.5),
    fs: slim ? 2.6 : body[0] * 0.105,
    slim,
  }
})
</script>

<template>
  <svg :width="width" :height="height" :viewBox="`0 0 ${box.w} ${box.h}`" role="img" :aria-label="`زجاجة مداد ${ml} مل`" class="block overflow-visible">
    <defs>
      <linearGradient :id="`${id}-gold`" x1="0" x2="1">
        <stop offset="0" stop-color="#6E5228" />
        <stop offset=".28" stop-color="#E6CB93" />
        <stop offset=".52" stop-color="#B08D57" />
        <stop offset=".78" stop-color="#D9BC84" />
        <stop offset="1" stop-color="#5E4522" />
      </linearGradient>
      <linearGradient :id="`${id}-glass`" x1="0" x2="1">
        <stop offset="0" stop-color="#1A1612" />
        <stop offset=".18" stop-color="#4B4237" />
        <stop offset=".45" stop-color="#3B342C" />
        <stop offset=".8" stop-color="#2A241F" />
        <stop offset="1" stop-color="#14110E" />
      </linearGradient>
      <linearGradient :id="`${id}-shine`" x1="0" x2="0" y1="0" y2="1">
        <stop offset="0" stop-color="#fff" stop-opacity=".22" />
        <stop offset="1" stop-color="#fff" stop-opacity="0" />
      </linearGradient>
      <radialGradient v-if="glow" :id="`${id}-glow`" cx=".5" cy=".7" r=".6">
        <stop offset="0" :stop-color="glow" stop-opacity=".55" />
        <stop offset="1" :stop-color="glow" stop-opacity="0" />
      </radialGradient>
    </defs>
    <ellipse v-if="glow" :cx="geo.cx" :cy="geo.body.y + geo.body.h * 0.6" :rx="geo.body.w * 1.1" :ry="geo.body.h * 0.6" :fill="`url(#${id}-glow)`" />
    <!-- body -->
    <rect :x="geo.body.x" :y="geo.body.y" :width="geo.body.w" :height="geo.body.h" :rx="geo.body.r" :fill="`url(#${id}-glass)`" />
    <rect :x="geo.body.x" :y="geo.body.y + geo.body.h - geo.base" :width="geo.body.w" :height="geo.base" :rx="geo.body.r" fill="#0E0B09" opacity=".55" />
    <rect :x="geo.body.x + geo.body.w * 0.12" :y="geo.body.y + 1.5" :width="geo.body.w * 0.07" :height="geo.body.h - geo.base - 3" rx="1" :fill="`url(#${id}-shine)`" />
    <rect :x="geo.body.x + 0.25" :y="geo.body.y + 0.25" :width="geo.body.w - 0.5" :height="geo.body.h - 0.5" :rx="geo.body.r" fill="none" stroke="#D6B77E" stroke-opacity=".16" stroke-width=".5" />
    <!-- collar + cap -->
    <template v-if="geo.collar">
      <rect :x="geo.collar.x" :y="geo.collar.y" :width="geo.collar.w" :height="geo.collar.h" :fill="`url(#${id}-gold)`" />
      <rect :x="geo.collar.x" :y="geo.collar.y + geo.collar.h * 0.55" :width="geo.collar.w" height=".35" fill="#F3E2BC" opacity=".8" />
    </template>
    <rect :x="geo.cap.x" :y="geo.cap.y" :width="geo.cap.w" :height="geo.cap.h" :rx="geo.cap.r" :fill="`url(#${id}-gold)`" />
    <rect :x="geo.cap.x + geo.cap.w * 0.2" :y="geo.cap.y + 0.8" :width="geo.cap.w * 0.08" :height="geo.cap.h - 1.6" rx=".6" fill="#FFF6DF" opacity=".55" />
    <!-- print -->
    <!-- the print is Latin: lay it out left-to-right even inside the RTL page -->
    <g v-if="label" color="#D6B77E" direction="ltr">
      <g :transform="`translate(${geo.cx - (geo.logoH * 100) / 150 / 2} ${geo.logoY}) scale(${geo.logoH / 150})`">
        <!-- one gold, as the guide's gold-on-black variant; the spray stays down to 12 mm (p.8) -->
        <LogoGlyph kasra="#D6B77E" />
      </g>
      <text :x="geo.cx" :y="geo.textY" text-anchor="middle" fill="currentColor" font-family="Cinzel, serif" :font-size="geo.fs" letter-spacing=".25">MIDAD</text>
      <text :x="geo.cx" :y="geo.textY + geo.fs * 1.5" text-anchor="middle" fill="currentColor" opacity=".8" font-family="Montserrat, sans-serif" :font-size="geo.fs * 0.42" letter-spacing=".35">EAU DE PARFUM</text>
      <text :x="geo.cx" :y="geo.textY + geo.fs * (geo.slim ? 2.3 : 2.1)" text-anchor="middle" fill="currentColor" opacity=".8" font-family="Montserrat, sans-serif" :font-size="geo.fs * 0.42" letter-spacing=".35">{{ ml }} ml · {{ s.oz }} FL.OZ.</text>
    </g>
  </svg>
</template>
