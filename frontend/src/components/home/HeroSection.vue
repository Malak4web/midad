<script setup>
import AppIcon from '@/components/ui/AppIcon.vue'
import SiteSearch from '@/components/layout/SiteSearch.vue'
import { ORIGINAL_SEARCHES } from '@/services/home'
import { BOTTLE } from '@/data/photos'
import { pose } from '@/data/attar'
import { formatNumber, formatPrice } from '@/utils/format'

// The first screen as a bento in Midad's ink and gold: the headline tile (ink, the
// title in gold foil, the bottle leaning in a gold ring), the perfume expert waving
// from a sand tile, the discovery set and the four sizes as small tiles, and a
// paper tile to search by the original perfume. Every tile wears the label's frame.
defineProps({ offer: { type: Object, default: () => ({ price: null, samples: 6, sampleMl: 2 }) } })
const attar = pose('hello')
</script>

<template>
  <section class="hero container-midad" aria-labelledby="hero-h" data-first-paint>
    <div class="t main framed">
      <span class="sun" aria-hidden="true" />
      <img :src="BOTTLE.src" alt="زجاجة مداد" class="bottle" width="456" height="783" fetchpriority="high" decoding="async" />
      <h1 id="hero-h" class="foil m-0 font-display font-bold">عطرٌ يُكتَب،<br />فيبقى</h1>
      <div class="flex flex-col gap-4">
        <p class="m-0">ثلاثون عطراً بثبات طويل، في زجاجة واحدة بأربعة أحجام، ودفعٌ عند الاستلام في كل مصر.</p>
        <div class="flex flex-wrap items-center gap-3">
          <RouterLink :to="{ name: 'category', params: { slug: 'men' } }" class="btn-pill is-gold">تسوّق العطور <AppIcon name="arrow-left" :size="18" /></RouterLink>
          <RouterLink :to="{ name: 'finder' }" class="btn-pill is-light">ساعدني في الاختيار</RouterLink>
        </div>
      </div>
    </div>

    <RouterLink :to="{ name: 'finder' }" class="t attar framed no-underline">
      <img v-if="attar" :src="attar.src" :srcset="attar.srcset" sizes="(min-width: 1024px) 300px, 50vw" alt="" decoding="async" />
      <span class="cap"><b class="font-display">اسأل خبير العطور</b><span>٣ أسئلة وتلاقي عطرك</span></span>
    </RouterLink>
    <RouterLink :to="{ name: 'discovery' }" class="t disc framed no-underline">
      <span class="text-[0.8125rem]">مجموعة الاكتشاف</span>
      <b class="font-display">{{ formatNumber(offer.samples) }} عيّنات</b>
      <span v-if="offer.price != null" class="text-[0.8125rem] tabular">{{ formatPrice(offer.price) }}</span>
    </RouterLink>
    <RouterLink :to="{ name: 'category', params: { slug: 'men' } }" class="t sizes framed no-underline">
      <span class="text-[0.8125rem]">أحجام لكل عطر</span>
      <b class="font-display">٤</b>
      <span class="text-[0.8125rem]" dir="rtl">١٠ · ٣٠ · ٥٠ · ١٠٠ مل</span>
    </RouterLink>

    <div class="t find framed">
      <div class="min-w-0 flex-1"><SiteSearch soft big placeholder="ابحث بعطرٍ تعرفه… «بلو دي شانيل»" /></div>
      <ul class="originals m-0 flex list-none gap-2 p-0" aria-label="الأكثر بحثاً">
        <li v-for="o in ORIGINAL_SEARCHES.slice(0, 4)" :key="o"><RouterLink :to="{ name: 'search', query: { q: o } }" class="chip no-underline">{{ o }}</RouterLink></li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
.hero { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); grid-auto-rows: 118px; gap: 12px; padding-top: 14px; }
.t { border-radius: var(--radius-tile); padding: 22px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; color: var(--color-ink); }
a.t { transition: transform 0.3s var(--ease-pop); }
a.t:hover { transform: translateY(-4px) rotate(-0.5deg); }

.main { grid-column: span 4; grid-row: span 4; background: var(--color-ink); color: var(--color-ivory); padding: 36px; }
.main h1 { position: relative; z-index: 1; font-size: clamp(2.75rem, 6.6vw, 6.4rem); line-height: 1.18; max-width: none; }
.main p { position: relative; z-index: 1; max-width: 32ch; font-size: 1rem; line-height: 1.75; }
.main .flex { position: relative; z-index: 1; }
/* a gold ring, the label's seal, behind the bottle */
.sun { position: absolute; left: 4%; top: 9%; width: 44%; aspect-ratio: 1; border-radius: 50%; border: 1.5px solid var(--color-gold); }
.sun::after { content: ''; position: absolute; inset: 12px; border-radius: 50%; border: 1px solid rgba(176, 141, 87, 0.45); }
.bottle { position: absolute; left: 6%; bottom: -5%; height: 94%; width: auto; transform: rotate(-9deg); filter: drop-shadow(0 26px 30px rgba(0, 0, 0, 0.55)); }

.attar { grid-column: span 2; grid-row: span 2; background: var(--color-sand); padding: 0; }
.attar img { position: absolute; bottom: 0; left: 0; height: 116%; width: auto; max-width: none; }
.attar .cap { position: absolute; top: 18px; right: 18px; z-index: 1; max-width: 52%; display: flex; flex-direction: column; font-size: 0.8125rem; }
.attar .cap b { font-size: 1.5rem; line-height: 1.1; margin-bottom: 4px; }
.disc { grid-column: span 1; grid-row: span 2; background: var(--color-sand-light); justify-content: flex-end; gap: 2px; }
.sizes { grid-column: span 1; grid-row: span 2; background: var(--color-ink); color: var(--color-gold-light); justify-content: flex-end; gap: 2px; }
.disc b, .sizes b { font-size: 2.1rem; line-height: 1; }
.sizes b { font-size: 3rem; }

.find { grid-column: span 6; grid-row: span 1; background: var(--color-paper); flex-direction: row; align-items: center; gap: 16px; padding: 14px 16px; overflow: visible; z-index: 2; }
.chip { display: inline-block; white-space: nowrap; padding: 8px 14px; border-radius: 999px; background: var(--color-linen); color: var(--color-ink); font-size: 0.8125rem; }
.chip:hover { background: var(--color-sand); }

@media (max-width: 1023px) {
  .hero { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: 104px; gap: 10px; }
  /* phone: the bottle leans in the top corner beside the headline; the words run full width below */
  .main { grid-column: span 2; grid-row: span 5; padding: 24px; justify-content: flex-start; gap: 18px; }
  .main h1 { font-size: clamp(2.6rem, 12.5vw, 4rem); }
  .main p { max-width: none; }
  .main > .flex { margin-top: auto; }
  .sun { width: 46%; top: -6%; left: -10%; }
  .bottle { height: 46%; top: 2%; bottom: auto; left: 0; }
  .attar { grid-column: span 2; }
  .find { grid-column: span 2; grid-row: span 1; }
  .originals { display: none; }
}
@media (max-width: 1279px) and (min-width: 1024px) { .originals li:nth-child(n + 3) { display: none; } }
@media (prefers-reduced-motion: reduce) { a.t:hover { transform: none; } }
</style>
