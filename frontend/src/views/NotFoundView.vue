<script setup>
import AttarCameo from '@/components/attar/AttarCameo.vue'
</script>

<template>
  <main id="main" class="container-midad flex min-h-[60vh] flex-col items-center justify-center gap-5 py-20 text-center">
    <h1 class="m-0 font-display text-[2rem] leading-[1.2] md:text-5xl md:leading-[1.2]">الصفحة غير موجودة</h1>
    <AttarCameo pose="thinking" line="يبدو أن هذه الصفحة تبخّرت كعطرٍ بلا قاعدة! دعني أدلّك على غيرها." :to="{ name: 'finder' }" action="اسأل خبير العطور" class="text-start" />
    <p class="m-0 max-w-md text-muted">ربما تغيّر الرابط أو كُتب بشكل غير صحيح. ابدأ من الرئيسية، أو ابحث عن عطرك.</p>
    <div class="flex gap-3">
      <RouterLink to="/" class="rounded-full bg-ink px-6 py-3 text-ivory no-underline">الرئيسية</RouterLink>
      <RouterLink :to="{ name: 'search' }" class="rounded-full border border-ink px-6 py-3 text-ink no-underline">البحث</RouterLink>
    </div>
  </main>
</template>
