<script setup>
import { defineAsyncComponent, onBeforeUnmount, onMounted, ref } from 'vue'
import HeroSection from '@/components/home/HeroSection.vue'
import DiscoverySection from '@/components/home/DiscoverySection.vue'
import SpotlightSection from '@/components/home/SpotlightSection.vue'
// the Attar's conversation (and his videos) load when the page needs them, not with the first screen
const FinderSection = defineAsyncComponent(() => import('@/components/home/FinderSection.vue'))
import GiftSection from '@/components/home/GiftSection.vue'
import SiteFooter from '@/components/layout/SiteFooter.vue'
import SectionBoundary from '@/components/ui/SectionBoundary.vue'
import { getHome } from '@/services/home'

// The home page, top to bottom: the bento hero, the discovery set, one bento per
// category (its featured perfume, then the rest in colour cards), the Attar, and
// gifts. Until the data lands, the category sections are quiet placeholder tiles.
const home = ref(null)
const ctrl = new AbortController()
onMounted(async () => {
  try {
    home.value = await getHome({ signal: ctrl.signal })
  } catch {
    /* navigated away */
  }
})
onBeforeUnmount(() => ctrl.abort())
</script>

<template>
  <main id="main" class="home">
    <HeroSection :offer="home?.discovery" />
    <DiscoverySection :offer="home?.discovery || { price: null, samples: 6, sampleMl: 2 }" />
    <template v-if="home">
      <SectionBoundary v-for="s in home.sections" :key="s.category.slug"><SpotlightSection :section="s" /></SectionBoundary>
    </template>
    <div v-else class="container-midad flex flex-col gap-5 py-10" aria-busy="true">
      <div class="h-12 w-48 animate-pulse rounded-full bg-paper" />
      <div class="grid gap-3 lg:grid-cols-2">
        <div class="aspect-[4/3] animate-pulse rounded-[28px] bg-paper" />
        <div class="flex flex-col gap-3"><div class="h-56 animate-pulse rounded-[28px] bg-paper" /><div class="h-28 animate-pulse rounded-[28px] bg-paper" /></div>
      </div>
    </div>
    <FinderSection />
    <GiftSection />
    <SiteFooter />
  </main>
</template>
