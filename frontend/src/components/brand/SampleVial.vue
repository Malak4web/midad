<script setup>
// A 2 ml sample vial, drawn in CSS: a glass tube with the scent's colour inside
// and a gold cap. Without a family it is an empty, dashed slot.
defineProps({
  family: { type: String, default: '' }, // utils/scenes family key
  size: { type: Number, default: 24 }, // width in px; the height follows
})
const LIQUID = { gourmand: '#D59A4A', amber: '#B9792F', floral: '#D4A59A', fresh: '#A9C4BE', oriental: '#5B2C1A', woody: '#8A6A44', gift: '#C9B48E' }
</script>

<template>
  <span class="vial" :class="!family && 'is-empty'" :style="{ '--w': `${size}px`, '--l': LIQUID[family] || LIQUID.amber }" aria-hidden="true" />
</template>

<style scoped>
.vial { position: relative; display: inline-block; flex: none; width: var(--w); height: calc(var(--w) * 3.3); }
.vial::before {
  content: '';
  position: absolute;
  inset: 22% 8% 0;
  border-radius: 3px 3px 9px 9px;
  background:
    linear-gradient(90deg, rgba(255, 255, 255, 0.55), rgba(255, 255, 255, 0.12) 40%, rgba(255, 255, 255, 0.4)),
    linear-gradient(0deg, var(--l) 0 70%, rgba(255, 255, 255, 0.35) 70%);
  box-shadow: inset 0 0 0 1px rgba(14, 13, 12, 0.25);
}
.vial::after { content: ''; position: absolute; inset: 0 4% 78%; border-radius: 3px; background: linear-gradient(90deg, #9a7440, #f3dda6 35%, #be9455 65%, #a27d43); }
.vial.is-empty::before { background: rgba(255, 255, 255, 0.25); box-shadow: none; outline: 1px dashed rgba(118, 90, 48, 0.55); outline-offset: -1px; }
.vial.is-empty::after { background: rgba(14, 13, 12, 0.12); }
</style>
