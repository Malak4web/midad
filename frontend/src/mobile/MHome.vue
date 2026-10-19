<script setup>
import { onMounted, ref } from 'vue'
import MidadLogo from '@/components/brand/MidadLogo.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import MProductCard from './MProductCard.vue'
import { getHome } from '@/services/home'
import { useUiStore } from '@/stores/ui'
import { BOTTLE } from '@/data/photos'
import { formatNumber, formatPrice } from '@/utils/format'

// The phone home: search in reach, the sections as chips, a compact bento
// (the brand, the discovery set, the perfume expert), then each category as a
// row of small cards to swipe.
const ui = useUiStore()
const home = ref(null)
onMounted(async () => {
  home.value = await getHome().catch(() => null)
})
const CHIPS = [
  [{ name: 'category', params: { slug: 'men' } }, 'رجالي'],
  [{ name: 'category', params: { slug: 'women' } }, 'نسائي'],
  [{ name: 'category', params: { slug: 'oriental' } }, 'شرقي ونيش'],
  [{ name: 'discovery' }, 'الاكتشاف'],
  [{ name: 'gifts' }, 'الهدايا'],
]
const rows = (s) => [s.spotlight?.product, ...s.others].filter(Boolean).slice(0, 8)
</script>

<template>
  <main id="main" class="m">
    <header class="top">
      <RouterLink to="/" aria-label="مداد، الصفحة الرئيسية" class="text-ink"><MidadLogo :height="40" label="مداد" /></RouterLink>
      <button class="search" @click="ui.sheet = 'search'"><AppIcon name="search" :size="18" /> ابحث بعطرٍ تعرفه…</button>
    </header>

    <nav class="chips" aria-label="الأقسام">
      <RouterLink v-for="[to, l] in CHIPS" :key="l" :to="to" class="chip">{{ l }}</RouterLink>
    </nav>

    <section class="bento" aria-label="مداد">
      <RouterLink :to="{ name: 'category', params: { slug: 'men' } }" class="t big framed no-underline">
        <h1 class="foil m-0 font-display text-[1.5rem] font-bold leading-[1.3]">عطرٌ يُكتَب،<br />فيبقى</h1>
        <span class="text-[0.75rem] text-sand">٣٠ عطراً · دفع عند الاستلام</span>
        <span class="go">تسوّق العطور <AppIcon name="arrow-left" :size="14" /></span>
        <img :src="BOTTLE.src" alt="" class="bt" />
      </RouterLink>
      <RouterLink :to="{ name: 'discovery' }" class="t sm no-underline" style="background: var(--color-sand)">
        <span>مجموعة الاكتشاف</span>
        <b class="font-display">{{ formatNumber(home?.discovery?.samples || 6) }} عيّنات</b>
        <small v-if="home?.discovery?.price" class="tabular">{{ formatPrice(home.discovery.price) }}</small>
      </RouterLink>
      <RouterLink :to="{ name: 'finder' }" class="t sm dark no-underline">
        <span>اسأل خبير العطور</span>
        <b class="font-display">٣ أسئلة</b>
        <small>ويرشّح لك عطرك</small>
      </RouterLink>
    </section>

    <template v-if="home">
      <section v-for="s in home.sections" :key="s.category.slug" class="sec" :aria-labelledby="`h-${s.category.slug}`">
        <div class="ttl">
          <h2 :id="`h-${s.category.slug}`" class="m-0 font-display text-[1.25rem] font-bold leading-[1.3]">{{ s.category.name }}</h2>
          <RouterLink :to="{ name: 'category', params: { slug: s.category.slug } }" class="more">الكل</RouterLink>
        </div>
        <ul class="rail m-0 list-none p-0">
          <li v-for="p in rows(s)" :key="p.slug"><MProductCard :product="p" /></li>
        </ul>
      </section>
    </template>
    <div v-else class="sec" aria-busy="true"><div class="h-6 w-24 animate-pulse rounded-full bg-paper" /><div class="mt-3 flex gap-2"><div v-for="n in 2" :key="n" class="h-56 flex-1 animate-pulse rounded-2xl bg-paper" /></div></div>

    <RouterLink :to="{ name: 'gifts' }" class="gift no-underline">
      <AppIcon name="gift" :size="22" />
      <span class="flex-1"><b class="font-display text-[1rem]">هدية بكارت مكتوب</b><small>علبة مداد بشريطة دهب ورسالتك</small></span>
      <AppIcon name="chevron-left" :size="18" />
    </RouterLink>
  </main>
</template>

<style scoped>
.m { padding-bottom: 20px; font-size: 0.875rem; }
.top { position: sticky; top: 0; z-index: 30; display: flex; align-items: center; gap: 10px; padding: calc(8px + env(safe-area-inset-top, 0px)) 16px 8px; background: color-mix(in srgb, var(--color-ivory) 92%, transparent); backdrop-filter: blur(14px); }
.search { flex: 1; display: flex; align-items: center; gap: 8px; height: 42px; padding: 0 12px; border-radius: 12px; background: var(--color-paper); color: var(--color-muted); font-size: 0.8125rem; text-align: start; }
.chips { display: flex; gap: 6px; padding: 2px 16px 10px; overflow-x: auto; scrollbar-width: none; }
.chips::-webkit-scrollbar { display: none; }
.chip { flex: none; display: inline-flex; align-items: center; height: 34px; padding: 0 14px; border-radius: 999px; background: var(--color-paper); color: var(--color-ink); font-size: 0.8125rem; text-decoration: none; white-space: nowrap; }
.bento { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 0 16px; }
.t { position: relative; overflow: hidden; border-radius: 18px; padding: 14px; color: var(--color-ink); }
.big { grid-column: span 2; min-height: 168px; display: flex; flex-direction: column; align-items: flex-start; gap: 4px; background: var(--color-ink); --radius-tile: 18px; }
.big .bt { position: absolute; left: 10px; bottom: -22px; height: 168px; width: auto; transform: rotate(-8deg); filter: drop-shadow(0 16px 14px rgba(0, 0, 0, 0.5)); }
.go { position: relative; z-index: 4; margin-top: auto; display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 14px; border-radius: 999px; background: var(--color-gold-light); color: var(--color-ink); font-size: 0.8125rem; font-weight: 600; }
.sm { min-height: 96px; display: flex; flex-direction: column; justify-content: flex-end; gap: 1px; font-size: 0.75rem; }
.sm b { font-size: 1.125rem; line-height: 1.3; }
.sm small { font-size: 0.7rem; opacity: 0.8; }
.dark { background: var(--color-ink); color: var(--color-gold-light); }
.sec { padding: 18px 16px 0; }
.ttl { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
.more { display: inline-flex; align-items: center; min-height: 32px; padding-inline: 8px; margin-inline-end: -8px; font-size: 0.8125rem; color: var(--color-gold-ink); }
.rail { display: flex; gap: 8px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; margin-inline: -16px; padding-inline: 16px; }
.rail::-webkit-scrollbar { display: none; }
.rail li { flex: none; width: 44%; scroll-snap-align: start; }
.gift { display: flex; align-items: center; gap: 12px; margin: 18px 16px 0; padding: 14px; border-radius: 16px; background: var(--color-paper); color: var(--color-ink); }
.gift small { display: block; font-size: 0.75rem; color: var(--color-muted); }
</style>
