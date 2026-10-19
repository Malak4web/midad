<script setup>
defineProps({ title: { type: String, required: true }, lead: { type: String, default: '' } })
</script>

<template>
  <header class="mb-8 flex flex-wrap items-end justify-between gap-4 border-b border-sand pb-5">
    <div class="min-w-0">
      <h1 class="m-0 font-display text-[clamp(2rem,4vw,2.75rem)] font-bold leading-tight">{{ title }}</h1>
      <p v-if="lead" class="m-0 mt-1 text-muted">{{ lead }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2"><slot /></div>
  </header>
</template>
