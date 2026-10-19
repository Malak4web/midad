<script setup>
// Inner pages open with one ink tile framed in the label's gold hairline: the
// title in gold foil, a line under it, and anything the page slots in.
defineProps({
  title: { type: String, required: true },
  lead: { type: String, default: '' },
})
</script>

<template>
  <section class="container-midad pt-2 md:pt-4">
    <div class="heading framed flex flex-col gap-2 lg:gap-3">
      <slot name="above" />
      <h1 class="foil m-0 font-display text-[clamp(1.75rem,6vw,4.6rem)] font-bold leading-[1.2]">{{ title }}</h1>
      <p v-if="lead" class="m-0 max-w-[52ch] text-[0.875rem] leading-relaxed text-sand md:text-[1.0625rem]">{{ lead }}</p>
      <slot />
    </div>
  </section>
</template>

<style scoped>
.heading { border-radius: var(--radius-tile); padding: clamp(18px, 4vw, 46px) clamp(20px, 4vw, 46px); background: var(--color-ink); color: var(--color-ivory); }
/* phone: no tile, just a compact title under the app bar, so the content starts in the first view */
@media (max-width: 1023px) {
  .heading { padding: 0 0 6px; background: none; color: var(--color-ink); border-radius: 0; }
  .heading.framed::before, .heading.framed::after { display: none; }
  .heading h1 { font-size: 1.5rem; line-height: 1.3; background: none; -webkit-text-fill-color: currentColor; color: var(--color-ink); }
  .heading p { font-size: 0.8125rem; color: var(--color-muted); }
}
</style>
