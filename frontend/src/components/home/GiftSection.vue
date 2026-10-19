<script setup>
import { computed, ref, useId } from 'vue'
import MidadLogo from '@/components/brand/MidadLogo.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { formatNumber } from '@/utils/format'
import { photo } from '@/data/photos'

// Gifts as a bento: the wrapped box photographed with the live card laid on it,
// and a sand tile where the visitor writes the card and sees it change.
const giftPhoto = photo('gift-ribbon')
const MAX = 120
const id = useId()
const to = ref('سارة')
const msg = ref('كل عام وأنتِ الأثر الذي يبقى.')
const left = computed(() => MAX - msg.value.length)
</script>

<template>
  <section class="gift container-midad py-10 md:py-14" aria-labelledby="gift-h">
    <figure class="t photo framed" aria-label="معاينة كارت الهدية">
      <img :src="giftPhoto.src" :srcset="giftPhoto.srcset" sizes="(min-width: 1024px) 48vw, 100vw" width="512" height="286" alt="" loading="lazy" decoding="async" />
      <div class="card">
        <p class="m-0 font-display text-[clamp(1.125rem,2vw,1.4rem)] font-bold leading-tight">إلى {{ to || '…' }}،</p>
        <p class="m-0 mt-2 max-w-[22ch] break-words text-[clamp(0.875rem,1.4vw,1rem)] leading-snug">{{ msg || 'اكتب رسالتك هنا.' }}</p>
        <div class="stamp" aria-hidden="true"><MidadLogo :height="40" /></div>
      </div>
    </figure>

    <div class="t write framed">
      <h2 id="gift-h" class="m-0 font-display text-[clamp(2.2rem,4.4vw,3.8rem)] font-bold leading-[1.2]">هديةٌ تُقرأ قبل أن تُشَمّ</h2>
      <p class="m-0 max-w-[42ch] text-[0.9375rem] leading-relaxed">اختر العطر، واكتب سطراً لمن تحب. نطبعه على كارت ونغلّف الزجاجة ونختمها.</p>
      <div class="grid gap-3 sm:grid-cols-[1fr_2fr]">
        <div class="flex flex-col gap-1.5">
          <label :for="`${id}-to`" class="text-[0.875rem]">إلى</label>
          <input :id="`${id}-to`" v-model="to" maxlength="30" class="h-12 w-full min-w-0 rounded-2xl bg-paper px-4 outline-none focus:ring-2 focus:ring-ink" />
        </div>
        <div class="flex flex-col gap-1.5">
          <label :for="`${id}-msg`" class="flex justify-between text-[0.875rem]">رسالتك <span class="tabular" aria-live="polite">{{ formatNumber(left) }} حرفاً متبقياً</span></label>
          <textarea :id="`${id}-msg`" v-model="msg" :maxlength="MAX" rows="2" class="w-full min-w-0 resize-none rounded-2xl bg-paper px-4 py-3 outline-none focus:ring-2 focus:ring-ink" />
        </div>
      </div>
      <RouterLink :to="{ name: 'gifts' }" class="btn-pill self-start"><AppIcon name="gift" :size="18" /> اختر عطر الهدية</RouterLink>
    </div>
  </section>
</template>

<style scoped>
.gift { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 12px; }
.t { border-radius: var(--radius-tile); position: relative; overflow: hidden; color: var(--color-ink); }
.photo { margin: 0; min-height: 380px; background: var(--color-ink); }
.photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.card { position: absolute; inset-inline-end: 6%; bottom: 7%; width: min(62%, 320px); aspect-ratio: 148 / 105; padding: 5% 6%; background: var(--color-paper); border-radius: 18px; transform: rotate(-3deg); box-shadow: 0 24px 40px -20px rgba(28, 20, 12, 0.5); }
.stamp { position: absolute; top: 10%; inset-inline-end: 7%; color: var(--color-gold); }
.write { background: var(--color-sand-light); padding: 30px; display: flex; flex-direction: column; gap: 16px; }
@media (max-width: 1023px) {
  .gift { grid-template-columns: 1fr; gap: 10px; }
  .photo { min-height: 0; aspect-ratio: 4 / 3; }
  .write { padding: 22px; }
}
</style>
