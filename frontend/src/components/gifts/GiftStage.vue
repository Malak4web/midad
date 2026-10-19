<script setup>
import { computed, useId } from 'vue'
import MidadLogo from '@/components/brand/MidadLogo.vue'
import { BOTTLE } from '@/data/photos'
import { formatMl } from '@/utils/format'

// The gift being made, on a lit studio table: a black Midad box with an ivory
// insert. Choosing the perfume lowers the bottle into the insert (its foot hides
// behind the front wall); writing the card stands it, large and readable, at the
// box's corner; closing brings the lid down with an off-centre gold ribbon, a bow
// and a wax seal, so the box's mark stays in view the whole time.
const props = defineProps({
  product: { type: Object, default: null },
  size: { type: Number, default: 50 },
  to: { type: String, default: '' },
  message: { type: String, default: '' },
  carded: { type: Boolean, default: false }, // the card step has been reached
  closed: { type: Boolean, default: false },
})
const id = useId()
const placed = computed(() => !!props.product)
const showCard = computed(() => props.carded || !!(props.to || props.message))
const caption = computed(() => {
  if (props.closed) return 'اتقفلت بالشريطة والختم'
  if (showCard.value) return 'الكارت واقف قدام العلبة'
  if (placed.value) return `${props.product.name} في مكانها`
  return 'العلبة مفتوحة ومستنية هديتك'
})
</script>

<template>
  <figure class="stage framed m-0" aria-label="معاينة الهدية" :class="[placed && 'is-placed', showCard && 'is-carded', closed && 'is-closed']">
    <figcaption class="cap">
      <b class="font-display">{{ caption }}</b>
      <span v-if="placed" class="ml" dir="ltr">{{ formatMl(size) }}</span>
    </figcaption>
    <div class="set" aria-hidden="true">
      <span class="floor" />
      <span class="shadow" />
      <div class="box">
        <span class="back" />
        <span class="insert" />
        <img class="bottle" :src="BOTTLE.src" alt="" decoding="async" />
        <div class="front"><span class="mark"><MidadLogo :height="64" kasra="#D6B77E" label="" class="text-[#E2C48A]" /><span class="word">MIDAD</span></span></div>
        <span class="rib" />
        <div class="lid">
          <span class="face" />
          <span class="rib" />
          <svg class="bow" viewBox="0 0 120 70">
            <defs>
              <linearGradient :id="`${id}-g`" x1="0" x2="1"><stop offset="0" stop-color="#8D6A38" /><stop offset=".45" stop-color="#F3DDA6" /><stop offset=".6" stop-color="#C9A262" /><stop offset="1" stop-color="#8D6A38" /></linearGradient>
            </defs>
            <path d="M60 52C44 30 18 14 8 22 0 30 14 52 60 52Z" :fill="`url(#${id}-g)`" />
            <path d="M60 52C76 30 102 14 112 22 120 30 106 52 60 52Z" :fill="`url(#${id}-g)`" />
            <path d="M60 52 46 70M60 52 74 70" :stroke="`url(#${id}-g)`" stroke-width="7" stroke-linecap="round" fill="none" />
            <circle cx="60" cy="50" r="8" :fill="`url(#${id}-g)`" />
          </svg>
          <span class="seal"><MidadLogo :height="22" kasra="#E7B2B2" label="" class="text-[#E7B2B2]" /></span>
        </div>
      </div>
      <div v-if="closed" class="sparkle"><i v-for="(p, i) in [[22, 30], [74, 26], [64, 54], [30, 58], [50, 18]]" :key="i" :style="{ left: `${p[0]}%`, top: `${p[1]}%`, animationDelay: `${1.4 + i * 0.12}s` }" /></div>
    </div>
    <!-- the card, as it will be printed -->
    <div class="card" :class="!(to || message) && 'is-empty'">
      <span class="card-mark" aria-hidden="true"><MidadLogo :height="44" label="" class="text-gold" /></span>
      <p class="to m-0 font-display">إلى {{ to || '…' }}،</p>
      <p class="msg m-0 font-display">{{ message || 'اكتب رسالتك هنا.' }}</p>
      <span class="sig">MIDAD</span>
    </div>
  </figure>
</template>

<style scoped>
/* the studio: a warm ivory wall, a key light from the upper right, a sand table */
.stage { position: relative; display: flex; flex-direction: column; min-height: 600px; padding: 22px; overflow: hidden; border-radius: var(--radius-tile); color: var(--color-smoke); background: radial-gradient(ellipse 75% 60% at 50% 30%, #efe5d3 0%, #d9c9ac 100%); }
.cap { position: relative; z-index: 10; display: flex; justify-content: space-between; align-items: center; gap: 10px; font-size: 0.8125rem; }
.cap b { font-size: 1.25rem; color: var(--color-gold-ink); }
.ml { font-family: var(--font-latin); letter-spacing: 0.24em; font-size: 0.6875rem; color: var(--color-gold-ink); }
.set { position: relative; flex: 1; min-height: 520px; margin-top: 10px; }
.set::before { content: ''; position: absolute; inset: -20% -10% 30% 20%; background: radial-gradient(ellipse 45% 50% at 60% 30%, rgba(255, 250, 235, 0.55), transparent 70%); pointer-events: none; }
.floor { position: absolute; inset: auto 0 0; height: 34%; background: linear-gradient(180deg, #e6d8be, #cdb995); box-shadow: 0 -1px 0 rgba(255, 255, 255, 0.35); }
.shadow { position: absolute; left: 43%; bottom: 24%; width: 60%; height: 9%; transform: translateX(-50%); background: radial-gradient(closest-side, rgba(20, 12, 4, 0.55), transparent); filter: blur(6px); }

/* the box, in its own coordinates: back wall, insert, bottle, front wall, ribbon, lid */
.box { position: absolute; left: 43%; bottom: 26%; width: min(310px, 48%); aspect-ratio: 1 / 0.74; transform: translateX(-50%); --edge: #d6b77e; --shine: rgba(255, 240, 210, 0.35); --a: #24201c; --b: #0d0b09; }
.back { position: absolute; inset: -16% 3% 50% 3%; background: linear-gradient(180deg, var(--b), var(--a)); border-radius: 4px 4px 0 0; box-shadow: inset 0 0 0 1px var(--edge); }
.insert { position: absolute; inset: -4% 6% 40% 6%; background: radial-gradient(ellipse 60% 70% at 50% 65%, #f7efdf, #d9c7a3); border-radius: 3px; box-shadow: inset 0 8px 14px rgba(0, 0, 0, 0.35), inset 0 0 0 1px rgba(176, 141, 87, 0.4); }
.bottle { position: absolute; left: 50%; bottom: 26%; height: 146%; width: auto; max-width: none; z-index: 3; opacity: 0; transform: translate(-50%, -70%); transition: transform 1s cubic-bezier(0.3, 1.25, 0.5, 1), opacity 0.5s; filter: drop-shadow(0 18px 16px rgba(30, 18, 6, 0.45)); }
.is-placed .bottle { opacity: 1; transform: translate(-50%, 0); }
.is-closed .bottle { opacity: 0; transition-delay: 0s, 0.55s; }
.front { position: absolute; inset: 0; z-index: 4; display: grid; place-items: center; border-radius: 3px;
  background: linear-gradient(115deg, var(--shine) 0%, transparent 22%, transparent 60%, rgba(255, 255, 255, 0.08) 72%, transparent 80%), linear-gradient(180deg, var(--a) 0%, var(--b) 100%);
  box-shadow: inset 0 0 0 1.5px var(--edge), inset 0 2px 0 var(--shine), inset 0 -14px 24px rgba(0, 0, 0, 0.25), 0 40px 50px -26px rgba(30, 18, 6, 0.75), 0 4px 8px rgba(30, 18, 6, 0.25); }
.mark { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.word { font-family: var(--font-latin); letter-spacing: 0.42em; font-size: 0.75rem; background: var(--foil); -webkit-background-clip: text; background-clip: text; color: transparent; }
.rib { position: absolute; top: 0; bottom: 0; left: 82%; width: 9%; transform: translateX(-50%); z-index: 5; opacity: 0; background: linear-gradient(90deg, #7d5c2c, #f3dda6 45%, #c9a262 60%, #7d5c2c); box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.15); transition: opacity 0.5s 0.6s; }
.is-closed .rib { opacity: 1; }
/* the lid: open, propped against the box's side; closed, it caps the box */
.lid { position: absolute; left: 50%; bottom: 8%; width: 106%; aspect-ratio: 1 / 0.24; z-index: 6; transform: translateX(-104%) rotate(-66deg); transform-origin: 50% 100%; transition: bottom 0.9s cubic-bezier(0.5, 0, 0.3, 1), transform 0.9s cubic-bezier(0.5, 0, 0.3, 1); }
.is-closed .lid { bottom: 96%; transform: translateX(-50%) rotate(0); }
.face { position: absolute; inset: 0; border-radius: 4px; background: linear-gradient(115deg, var(--shine) 0%, transparent 25%, transparent 70%, rgba(255, 255, 255, 0.1) 82%, transparent 90%), linear-gradient(180deg, var(--a), var(--b)); box-shadow: inset 0 0 0 1.5px var(--edge), inset 0 2px 0 var(--shine), 0 18px 24px -14px rgba(30, 18, 6, 0.7); }
.lid .rib { left: 80.2%; width: 8.5%; transition-delay: 0.8s; }
.bow { position: absolute; left: 80.2%; bottom: 62%; width: 40%; height: auto; transform: translateX(-50%) scale(0); transform-origin: 50% 100%; transition: transform 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) 1.05s; z-index: 2; filter: drop-shadow(0 6px 6px rgba(30, 18, 6, 0.4)); }
.seal { position: absolute; left: 80.2%; top: 50%; z-index: 3; display: grid; place-items: center; width: 44px; height: 44px; border-radius: 50%; transform: translate(-50%, -50%) scale(0); background: radial-gradient(circle at 35% 35%, #8a2a2a, #4a1212); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.45), inset 0 0 0 3px rgba(0, 0, 0, 0.2); transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 1.35s; }
.is-closed .bow { transform: translateX(-50%) scale(1); }
.is-closed .seal { transform: translate(-50%, -50%) scale(1); }
.sparkle { position: absolute; inset: 0; pointer-events: none; z-index: 9; }
.sparkle i { position: absolute; width: 7px; height: 7px; background: #f3dda6; box-shadow: 0 0 8px rgba(243, 221, 166, 0.9); transform: rotate(45deg); opacity: 0; animation: twinkle 1.6s ease-out forwards; }
@keyframes twinkle { 0% { opacity: 0; transform: rotate(45deg) scale(0.2); } 40% { opacity: 1; transform: rotate(45deg) scale(1); } 100% { opacity: 0; transform: rotate(45deg) scale(0.4) translateY(-20px); } }

/* the card: large, upright, at the box's corner, with a contact shadow */
.card { position: absolute; z-index: 8; right: 4%; bottom: 7%; width: min(300px, 44%); aspect-ratio: 148 / 105; padding: 6% 7%; border-radius: 8px; background: #fbf7ee; color: var(--color-ink); opacity: 0; transform: translateY(40px) rotate(-3deg); transition: transform 0.8s cubic-bezier(0.3, 1.3, 0.5, 1), opacity 0.5s; box-shadow: 0 26px 36px -20px rgba(30, 18, 6, 0.6), 0 2px 4px rgba(30, 18, 6, 0.2); }
.is-carded .card { opacity: 1; transform: translateY(0) rotate(-3deg); }
.card::after { content: ''; position: absolute; inset: 9px; border: 1px solid rgba(176, 141, 87, 0.65); border-radius: 5px; pointer-events: none; }
.card-mark { position: absolute; top: 10%; left: 8%; }
.to { font-weight: 700; font-size: clamp(1.15rem, 2.2vw, 1.75rem); line-height: 1.25; max-width: 78%; overflow-wrap: anywhere; }
.msg { margin-top: 6px; font-size: clamp(0.95rem, 1.6vw, 1.25rem); line-height: 1.55; max-width: 26ch; overflow-wrap: anywhere; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.sig { position: absolute; bottom: 9%; left: 8%; font-family: var(--font-latin); font-size: 0.5625rem; letter-spacing: 0.3em; color: var(--color-gold); }
.card.is-empty .to, .card.is-empty .msg { color: var(--color-muted-light); }

@media (max-width: 1023px) {
  .stage { min-height: 0; padding: 14px; }
  .set { min-height: 270px; margin-top: 4px; }
  .box { width: 56%; left: 34%; bottom: 34%; }
  .shadow { left: 34%; bottom: 31%; }
  .card { width: 54%; right: 2%; bottom: 3%; padding: 5% 6%; }
  .to { font-size: 1rem; }
  .msg { font-size: 0.8125rem; -webkit-line-clamp: 2; }
  .sig { display: none; }
}
@media (prefers-reduced-motion: reduce) { .bottle, .lid, .card, .rib, .bow, .seal { transition: none; } .sparkle { display: none; } }
</style>
