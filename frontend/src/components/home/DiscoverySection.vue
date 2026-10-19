<script setup>
import AppIcon from '@/components/ui/AppIcon.vue'
import { formatNumber, formatPrice } from '@/utils/format'
import { photo } from '@/data/photos'

// The discovery set as a bento: the box photographed, a sand tile that says what
// it is, and the three steps as small tiles, all framed like the label.
defineProps({ offer: { type: Object, required: true } })
const box = photo('discovery-set')
const steps = [
  ['اختر ستّة', 'من الثلاثين عطراً، كل عينة ٢ مل تكفي أياماً من التجربة.', 'var(--color-sand-deep)'],
  ['جرّبها على مهلك', 'على بشرتك، في يومك العادي، لا في متجر مزدحم.', 'var(--color-sand)'],
  ['خذ زجاجتك بخصم', 'ثمن العلبة يعود إليك كرصيد على أول زجاجة.', 'var(--color-gold-light)'],
]
</script>

<template>
  <section class="disc container-midad py-10 md:py-14" aria-labelledby="disc-h">
    <figure class="t photo framed">
      <img :src="box.src" :srcset="box.srcset" sizes="(min-width: 1024px) 48vw, 100vw" width="512" height="286" alt="علبة مجموعة الاكتشاف مفتوحة وبداخلها ست عينات" loading="lazy" decoding="async" />
    </figure>
    <div class="t lead framed">
      <span class="text-[0.875rem]">مجموعة الاكتشاف</span>
      <h2 id="disc-h" class="m-0 font-display text-[clamp(2.4rem,5vw,4.4rem)] font-bold leading-[1.2]">ابدأ بستّة</h2>
      <p class="m-0 max-w-[40ch] text-[0.9375rem] leading-relaxed">العطر لا يُشَمّ عبر الشاشة. لذلك نرسل لك {{ formatNumber(offer.samples) }} عينات تختارها بنفسك، تجرّبها في بيتك، ثم تقرّر.</p>
      <div class="flex flex-wrap items-center gap-4">
        <RouterLink :to="{ name: 'discovery' }" class="btn-pill">اختر عيّناتك <AppIcon name="arrow-left" :size="18" /></RouterLink>
        <span v-if="offer.price != null" class="font-display text-2xl font-bold tabular">{{ formatPrice(offer.price) }}</span>
      </div>
    </div>
    <ol class="steps m-0 list-none p-0">
      <li v-for="([t, d, c], i) in steps" :key="t" class="t step framed" :style="{ background: c }">
        <span class="num font-display">{{ formatNumber(i + 1) }}</span>
        <b class="font-display text-[1.125rem]">{{ t }}</b>
        <span class="text-[0.8125rem] leading-relaxed">{{ d }}</span>
      </li>
    </ol>
  </section>
</template>

<style scoped>
.disc { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; }
.t { border-radius: var(--radius-tile); padding: 22px; position: relative; overflow: hidden; color: var(--color-ink); }
.photo { grid-column: span 3; grid-row: span 2; margin: 0; padding: 0; min-height: 300px; background: var(--color-ink); }
.photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.lead { grid-column: span 3; background: var(--color-sand-light); display: flex; flex-direction: column; gap: 12px; padding: 28px; }
.steps { grid-column: span 3; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.step { display: flex; flex-direction: column; gap: 4px; padding: 16px; }
.num { font-size: 2rem; line-height: 1; font-weight: 700; }
@media (max-width: 1023px) {
  .disc { grid-template-columns: 1fr; gap: 10px; }
  .photo, .lead, .steps { grid-column: 1; grid-row: auto; }
  .photo { aspect-ratio: 16 / 9; min-height: 0; }
  .steps { grid-template-columns: 1fr; gap: 10px; }
  .step { display: grid; grid-template-columns: auto 1fr; column-gap: 14px; }
  .step .num { grid-row: span 2; align-self: center; }
}
</style>
