<script setup>
import MidadLogo from '@/components/brand/MidadLogo.vue'

// Auth pages: a quiet sheet on ivory, the mark above the form.
defineProps({ title: { type: String, required: true }, lead: { type: String, default: '' } })
</script>

<template>
  <main id="main" class="container-midad flex min-h-[72vh] items-start justify-center pb-14 pt-5 lg:py-14">
    <div class="w-full max-w-md">
      <MidadLogo :height="64" class="mb-6 text-ink max-lg:hidden" />
      <h1 class="m-0 font-display text-[2rem] font-bold md:text-[2.75rem] leading-tight">{{ title }}</h1>
      <p v-if="lead" class="m-0 mt-2 text-muted">{{ lead }}</p>
      <div class="mt-5 lg:mt-8"><slot /></div>
    </div>
  </main>
</template>
