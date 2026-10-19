<script setup>
import { computed, watch } from 'vue'
import PageHeading from '@/components/layout/PageHeading.vue'
import NotFoundView from './NotFoundView.vue'
import { pages } from '@/data/pages'
import { photo } from '@/data/photos'

const props = defineProps({ page: { type: String, required: true } })
const content = computed(() => pages[props.page])
const isDev = import.meta.env.DEV
const photosAfter = (i) => (content.value.photos || []).filter(([at]) => at === i).map(([, name, alt], k) => ({ ...photo(name), alt }))
watch(content, (c) => c && (document.title = `${c.title} | مداد`), { immediate: true })
</script>

<template>
  <NotFoundView v-if="!content" />
  <main v-else id="main">
    <PageHeading :title="content.title" :lead="content.lead || ''" />
    <article class="container-midad flex max-w-3xl flex-col gap-8 pb-24 pt-4">
      <p v-if="isDev && content.draft" class="m-0 self-start border border-dashed border-muted px-2 py-0.5 text-[0.75rem] text-muted">نص مبدئي · يُراجع قبل الإطلاق</p>
      <section v-for="([h, p], i) in content.body" :key="i" class="flex flex-col gap-2">
        <h2 v-if="h" class="m-0 font-display text-3xl">{{ h }}</h2>
        <p class="m-0 max-w-[65ch] text-base md:text-[1.0625rem] leading-[1.95] text-lead">{{ p }}</p>
        <figure v-for="ph in photosAfter(i)" :key="ph.src" class="story-photo m-0 mt-6" :class="i % 2 ? 'self-start' : 'self-end'">
          <img :src="ph.src" :srcset="ph.srcset" sizes="(min-width: 768px) 640px, 92vw" :alt="ph.alt" loading="lazy" decoding="async" class="block w-full" />
        </figure>
      </section>
    </article>
  </main>
</template>

<style scoped>
.story-photo { width: min(100%, 640px); }
.story-photo img { border-radius: var(--radius-tile); }
</style>
