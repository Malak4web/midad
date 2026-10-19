<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, useId } from 'vue'
import AttarStage from '@/components/attar/AttarStage.vue'
import ProductThumb from '@/components/brand/ProductThumb.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { QUESTIONS as LOCAL_QUESTIONS, recommend } from '@/utils/finder'
import { products as localProducts, lowestPrice } from '@/data/catalog'
import { FALLBACK_REPLY, NUDGE, REPLIES, TIPS, greeting, recall, remember } from '@/data/attar'
import { useAddToCart } from '@/composables/useAddToCart'
import { api } from '@/services/api'
import { formatPrice } from '@/utils/format'

// «اسأل خبير العطور»: the Attar talks to the visitor directly. Every line pops out of
// him in a speech bubble and is spoken word by word; the visitor answers with the
// chips beside him and their words pop up in a bubble of their own. He greets
// (remembering a returning visitor), asks his three questions, replies to each,
// smells a blotter while deciding, then hands over his pick.
// Questions and scoring come from the API (BE-29), with local rules as a fallback.
const props = defineProps({ standalone: { type: Boolean, default: false } })
const id = useId()
const add = useAddToCart()
const calm = typeof matchMedia !== 'undefined' && matchMedia('(prefers-reduced-motion: reduce)').matches
const phone = ref(typeof matchMedia !== 'undefined' && matchMedia('(max-width: 1023px)').matches)

const stage = ref(null)
const section = ref(null)
const questions = ref(LOCAL_QUESTIONS)
const step = ref('idle') // idle · name · q · answered · thinking · result · back
const qi = ref(0)
const answers = reactive({})
const memory = ref(recall())
const nameDraft = ref('')
const busy = ref(false)
const results = ref(null) // { top, alts }
const transcript = ref([]) // every line, for screen readers and the "what did he say" history

// what he is saying now
const speech = reactive({ key: 0, text: '', shown: '', thinking: false, typing: false })
// what the visitor just said
const you = reactive({ key: 0, text: '' })

let begun = false
let nudgeTimer = 0
let youTimer = 0
let skip = null
let tip = Math.floor(Math.random() * TIPS.length)

const name = computed(() => memory.value.name || '')
const q = computed(() => questions.value[qi.value])
// resolves true when the visitor tapped to hurry him
const pause = (ms) =>
  new Promise((resolve) => {
    if (calm) return resolve(false)
    const t = setTimeout(() => done(false), ms)
    function done(hurried) {
      clearTimeout(t)
      skip = null
      resolve(hurried)
    }
    skip = () => done(true) // a tap on the bubble moves him on
  })

/**
 * He speaks: the bubble pops, he draws breath (dots), then the words arrive one
 * by one. `hold` keeps the line up without a reading pause (a question waits for
 * the visitor). Resolves when he is done with the line.
 */
async function say(text, motion, { hold = false } = {}) {
  speech.key++
  speech.text = text
  speech.shown = ''
  speech.thinking = true
  transcript.value.push(text)
  if (motion) stage.value?.play(motion)
  await pause(380)
  speech.thinking = false
  speech.typing = true
  if (calm) speech.shown = text
  else {
    const words = text.split(' ')
    for (let i = 1; i <= words.length; i++) {
      speech.shown = words.slice(0, i).join(' ')
      // longer words take a little longer, as speech does
      if (await pause(70 + words[i - 1].length * 22)) break // hurried: show it all
    }
    speech.shown = text
  }
  speech.typing = false
  if (!hold) await pause(Math.min(2600, 650 + text.length * 32))
}
function hurry() {
  skip?.()
}
function hear(text) {
  you.key++
  you.text = text
  transcript.value.push(`أنت: ${text}`)
  clearTimeout(youTimer)
  youTimer = setTimeout(() => (you.text = ''), calm ? 4000 : 2600)
}

async function begin() {
  if (begun) return
  begun = true
  busy.value = true
  stage.value?.arm(['listen', 'smile', 'explain'])
  const hello = greeting()
  const last = memory.value.lastPick
  if (last) {
    await say(`${hello}${name.value ? ` يا ${name.value}` : ''}! أهلاً بعودتك.`, 'wave')
    await say(`آخر مرة اخترنا لك «${last.name}». أنبحث اليوم عن جديد؟`, null, { hold: true })
    step.value = 'back'
  } else {
    await say(`${hello}، أنا خبير العطور في مداد.`, 'wave')
    await say('ثلاثة أسئلة قصيرة، وأعرف أيّ عطرٍ يشبهك.')
    if (name.value) await ask(0)
    else {
      await say('قبل أن نبدأ، بماذا أناديك؟', null, { hold: true })
      step.value = 'name'
    }
  }
  busy.value = false
}

async function giveName(skipName = false) {
  const n = skipName ? '' : nameDraft.value.trim().slice(0, 24)
  busy.value = true
  step.value = 'answered'
  hear(n || 'لنبدأ مباشرةً')
  if (n) memory.value = remember({ name: n })
  await say(n ? `تشرّفت يا ${n}.` : 'كما تحب، لنبدأ.', 'smile')
  await ask(0)
  busy.value = false
}

async function ask(i) {
  qi.value = i
  await say(questions.value[i].q, i === 0 ? 'explain' : null, { hold: true })
  step.value = 'q'
  stage.value?.loop('listen')
  clearTimeout(nudgeTimer)
  nudgeTimer = setTimeout(async () => {
    if (step.value !== 'q' || qi.value !== i) return
    const question = questions.value[i].q
    await say(NUDGE, 'smile')
    if (step.value === 'q' && qi.value === i) await say(question, null, { hold: true })
  }, calm ? 30000 : 16000)
}

async function choose(opt) {
  if (busy.value || step.value !== 'q') return
  busy.value = true
  clearTimeout(nudgeTimer)
  const question = q.value
  answers[question.key] = opt.v
  step.value = 'answered'
  hear(opt.label)
  stage.value?.loop('idle')
  await say(REPLIES[question.key]?.[opt.v] || FALLBACK_REPLY, 'smile')
  if (qi.value < questions.value.length - 1) await ask(qi.value + 1)
  else await decide()
  busy.value = false
}

async function decide() {
  step.value = 'thinking'
  stage.value?.arm()
  const thinking = say('دعني أشمّ ثلاثة أشرطة…', 'smell', { hold: true })
  let found
  try {
    found = (await api.finderResult({ ...answers })).data.map((r) => ({ p: r.product, why: r.why.map((w) => w.replace(/^ال/, '')) }))
  } catch {
    found = recommend(localProducts, answers)
  }
  await thinking
  await pause(calm ? 0 : 2600) // he is still smelling
  if (!found.length) {
    await say('لم أجد ما يشبه هذا تماماً. جرّب مجموعة الاكتشاف، فستّ عيّنات تقول الكثير.', null, { hold: true })
    step.value = 'result'
    return
  }
  const [top, ...alts] = found
  memory.value = remember({ lastPick: { slug: top.p.slug, name: top.p.name } })
  await say(`${name.value ? `يا ${name.value}، ` : ''}هذا عطرك: «${top.p.name}».`, 'present', { hold: true })
  results.value = { top, alts }
  step.value = 'result'
  if (top.why.length) await say(`اخترته لأن فيه ${top.why.join(' و')}، كما وصفت.`, 'explain', { hold: true })
}

async function addPick(p) {
  await add(p, 50)
  await say('اختيارٌ موفّق! صار في سلتك.', 'thumbs', { hold: true })
}

async function again() {
  busy.value = true
  hear('لنبدأ من جديد')
  results.value = null
  for (const k in answers) delete answers[k]
  await say('بكل سرور. من الأول:', 'smile')
  await ask(0)
  busy.value = false
}

async function returning() {
  busy.value = true
  hear('نعم، لنبحث عن جديد')
  await ask(0)
  busy.value = false
}

// tapped: a wave and one of his small lessons
function poke() {
  if (!begun) return begin()
  if (speech.typing) return hurry()
  if (busy.value) return
  const line = TIPS[tip++ % TIPS.length]
  const back = step.value === 'q' ? q.value.q : null
  ;(async () => {
    await say(line, tip % 2 ? 'smile' : 'wave')
    if (back && step.value === 'q') await say(back, null, { hold: true })
  })()
}

// on the home page he starts once his shop is well in view; on /finder at once
let ticking = false
function check() {
  ticking = false
  const r = section.value?.getBoundingClientRect()
  if (r && r.top < innerHeight * 0.55 && r.bottom > innerHeight * 0.3) {
    removeEventListener('scroll', onScroll)
    begin()
  }
}
function onScroll() {
  if (ticking) return
  ticking = true
  requestAnimationFrame(check)
}
const onMq = (e) => (phone.value = e.matches)
let mq
onMounted(async () => {
  mq = matchMedia('(max-width: 1023px)')
  mq.addEventListener('change', onMq)
  try {
    questions.value = (await api.finderQuestions()).data
  } catch {
    /* keep the local questions */
  }
  if (props.standalone) begin()
  else {
    addEventListener('scroll', onScroll, { passive: true })
    check()
  }
})
onBeforeUnmount(() => {
  clearTimeout(nudgeTimer)
  clearTimeout(youTimer)
  removeEventListener('scroll', onScroll)
  mq?.removeEventListener('change', onMq)
})
</script>

<template>
  <!-- phone: title, then the shop with his words, then your answers, all in one screen -->
  <div class="container-midad py-6 md:py-10">
    <div ref="section" class="finder-tile framed grid items-start gap-x-12 gap-y-5 lg:grid-cols-[0.9fr_1.1fr] lg:grid-rows-[auto_1fr] lg:gap-y-6">
      <div class="flex flex-col gap-3 lg:col-start-2 lg:row-start-1 lg:self-end">
        <component :is="standalone ? 'h1' : 'h2'" class="m-0 font-display text-[clamp(2rem,6vw,4.75rem)] font-bold leading-[1.2]">اسأل خبير العطور</component>
        <p class="m-0 max-w-[40ch] text-[0.9375rem] leading-[1.8] text-lead md:text-[1.125rem]">خبير العطور في مداد يعرف عطرك من ثلاثة أسئلة. حدّثه كما تحدّث خبيراً تثق به.</p>
      </div>
      <div class="stage-col z-10 lg:sticky lg:top-24 lg:col-start-1 lg:row-span-2 lg:row-start-1">
        <AttarStage ref="stage" :compact="phone" @poke="poke">
          <!-- his words, out of his mouth -->
          <Transition name="pop">
            <div v-if="speech.text" :key="speech.key" class="speech" @click="hurry">
              <span v-if="speech.thinking" class="dots" aria-hidden="true"><i /><i /><i /></span>
              <p v-else class="m-0">{{ speech.shown }}<span v-if="speech.typing" class="caret" aria-hidden="true" /></p>
            </div>
          </Transition>
          <!-- the visitor's words -->
          <Transition name="pop-you">
            <p v-if="you.text" :key="you.key" class="you m-0">{{ you.text }}</p>
          </Transition>
        </AttarStage>
      </div>

      <div class="flex min-w-0 flex-col gap-6 lg:col-start-2 lg:row-start-2">
        <!-- every line, read out by screen readers; the visual is the bubble -->
        <ol class="sr-only" aria-live="polite">
          <li v-for="(t, i) in transcript" :key="i">{{ t.startsWith('أنت:') ? t : `الخبير: ${t}` }}</li>
        </ol>

        <!-- the visitor's turn -->
        <div class="turn-area" :aria-labelledby="`${id}-turn`">
          <span :id="`${id}-turn`" class="sr-only">دورك في الحديث</span>
          <Transition name="turn" mode="out-in">
            <form v-if="step === 'name'" key="name" class="flex flex-wrap gap-2" @submit.prevent="giveName()">
              <label :for="`${id}-name`" class="sr-only">اسمك</label>
              <input :id="`${id}-name`" v-model="nameDraft" maxlength="24" autocomplete="given-name" placeholder="اسمك الأول" class="h-12 min-w-0 flex-1 border border-ink/25 bg-paper px-4 text-base" />
              <button class="h-12 bg-ink px-6 text-ivory disabled:opacity-40" :disabled="!nameDraft.trim() || busy">تفضّل</button>
              <button type="button" class="h-12 px-3 text-[0.9375rem] underline" :disabled="busy" @click="giveName(true)">تخطَّ</button>
            </form>

            <div v-else-if="step === 'q'" :key="`q-${qi}`" class="flex flex-wrap gap-2" role="group" :aria-label="q.q">
              <button v-for="(o, i) in q.options" :key="o.v" class="chip" :style="{ '--i': i }" :disabled="busy" @click="choose(o)" @mouseenter="stage?.loop('listen')" @focus="stage?.loop('listen')">{{ o.label }}</button>
            </div>

            <div v-else-if="step === 'back'" key="back" class="flex flex-wrap gap-2">
              <button class="chip" style="--i: 0" :disabled="busy" @click="returning">نعم، لنبحث عن جديد</button>
              <RouterLink :to="{ name: 'product', params: { slug: memory.lastPick.slug } }" class="chip no-underline" style="--i: 1">ذكّرني بـ«{{ memory.lastPick.name }}»</RouterLink>
            </div>

            <div v-else-if="step === 'result'" key="result" class="flex flex-wrap gap-2">
              <button class="chip" style="--i: 0" :disabled="busy" @click="again">أعد الأسئلة</button>
              <RouterLink :to="{ name: 'discovery' }" class="chip no-underline" style="--i: 1">جرّب عيّنات أولاً</RouterLink>
              <RouterLink v-if="answers.who === 'her' || answers.who === 'him'" :to="{ name: 'gifts' }" class="chip no-underline" style="--i: 2">غلّفه هدية</RouterLink>
            </div>

            <p v-else-if="step === 'idle'" key="idle" class="m-0">
              <button class="chip" style="--i: 0" @click="begin">ابدأ الحديث مع الخبير</button>
            </p>
            <p v-else key="wait" class="listening m-0" aria-hidden="true">الخبير يتحدّث… <span class="text-muted">(انقر على كلامه لتسريعه)</span></p>
          </Transition>
        </div>

        <!-- what he hands over -->
        <Transition name="hand">
          <div v-if="results" class="flex flex-col gap-3">
            <article class="pick">
              <ProductThumb :size="phone ? 96 : 128" :image="results.top.p.image" />
              <div class="flex min-w-0 flex-1 flex-col gap-1">
                <span class="text-[0.8125rem] text-gold-ink">ترشيح الخبير</span>
                <h3 class="m-0 font-display text-[1.75rem] font-bold leading-tight">{{ results.top.p.name }}</h3>
                <span class="font-latin text-[0.75rem] tracking-[0.12em] text-muted">{{ results.top.p.latin?.toUpperCase() }}</span>
                <span class="text-[0.875rem] text-muted">مستوحى من {{ results.top.p.inspiredBy }}</span>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                  <button class="inline-flex h-11 items-center gap-2 bg-ink px-5 text-ivory active:bg-smoke" @click="addPick(results.top.p)"><AppIcon name="bag" :size="18" /> أضف ٥٠ مل · {{ formatPrice(results.top.p.variants?.find((v) => v.ml === 50)?.price ?? results.top.p.fromPrice ?? lowestPrice(results.top.p)) }}</button>
                  <RouterLink :to="{ name: 'product', params: { slug: results.top.p.slug } }" class="px-2 py-2 text-[0.9375rem] text-ink underline">تفاصيل العطر</RouterLink>
                </div>
              </div>
            </article>
            <ul v-if="results.alts.length" class="m-0 grid list-none gap-2 p-0 sm:grid-cols-2" aria-label="بدائل يرشّحها الخبير">
              <li v-for="(a, i) in results.alts" :key="a.p.slug" class="alt" :style="{ '--i': i + 1 }">
                <ProductThumb :size="64" :image="a.p.image" />
                <div class="flex min-w-0 flex-1 flex-col">
                  <RouterLink :to="{ name: 'product', params: { slug: a.p.slug } }" class="truncate font-display text-[1.25rem] font-bold leading-tight text-ink no-underline">{{ a.p.name }}</RouterLink>
                  <span class="truncate text-[0.8125rem] text-muted">مستوحى من {{ a.p.inspiredBy }}</span>
                  <span class="text-[0.8125rem] tabular">من {{ formatPrice(a.p.fromPrice ?? lowestPrice(a.p)) }}</span>
                </div>
                <button class="grid h-10 w-10 shrink-0 place-items-center border border-ink/30" :aria-label="`أضف ${a.p.name} ٥٠ مل إلى السلة`" @click="addPick(a.p)"><AppIcon name="plus" :size="18" /></button>
              </li>
            </ul>
          </div>
        </Transition>

        <RouterLink :to="{ name: 'help', params: { page: 'contact' } }" class="inline-flex items-center gap-2 self-start text-[0.9375rem] text-ink underline">
          <AppIcon name="whatsapp" :size="18" /> تفضّل الحديث مع إنسان؟ راسلنا على واتساب
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── his speech bubble: beside his head on desktop, under the shop on a phone ── */
.speech {
  position: absolute;
  z-index: 6;
  top: 9%;
  inset-inline-start: 60%;
  width: max-content;
  max-width: 250px;
  padding: 14px 18px;
  background: #fffaf0;
  color: var(--color-ink);
  border: 1.5px solid var(--color-ink);
  border-radius: 22px;
  box-shadow: 0 18px 34px -18px rgba(14, 13, 12, 0.55);
  font-family: var(--font-display);
  font-size: 1.25rem;
  line-height: 1.55;
  cursor: pointer;
  transform-origin: 100% 30%; /* grows out of his side (RTL: the start edge) */
}
/* the tail, pointing at his mouth */
.speech::before,
.speech::after { content: ''; position: absolute; top: 26px; width: 0; height: 0; border-style: solid; }
.speech::before { inset-inline-start: -15px; border-width: 9px 15px 9px 0; border-color: transparent var(--color-ink) transparent transparent; }
.speech::after { inset-inline-start: -12px; top: 28px; border-width: 7px 12px 7px 0; border-color: transparent #fffaf0 transparent transparent; }
[dir='rtl'] .speech::before { border-width: 9px 0 9px 15px; border-color: transparent transparent transparent var(--color-ink); }
[dir='rtl'] .speech::after { border-width: 7px 0 7px 12px; border-color: transparent transparent transparent #fffaf0; }

.caret { display: inline-block; width: 2px; height: 1em; margin-inline-start: 3px; vertical-align: -0.15em; background: var(--color-gold); animation: blink 0.8s steps(2) infinite; }
@keyframes blink { 50% { opacity: 0; } }
.dots { display: inline-flex; gap: 6px; padding: 6px 2px; }
.dots i { width: 7px; height: 7px; background: var(--color-gold); transform: rotate(18deg); animation: dot 1s ease-in-out infinite; }
.dots i:nth-child(2) { animation-delay: 0.14s; }
.dots i:nth-child(3) { animation-delay: 0.28s; }
@keyframes dot { 0%, 60%, 100% { opacity: 0.35; transform: rotate(18deg) translateY(0); } 30% { opacity: 1; transform: rotate(18deg) translateY(-4px); } }

.pop-enter-active { animation: pop 0.42s cubic-bezier(0.34, 1.56, 0.64, 1); }
.pop-leave-active { transition: opacity 0.16s, transform 0.16s; }
.pop-leave-to { opacity: 0; transform: scale(0.94); }
@keyframes pop { from { opacity: 0; transform: scale(0.4) rotate(-4deg); } 70% { opacity: 1; } }

/* the visitor's words: a dark bubble rising from below, on the other side */
.you {
  position: absolute;
  z-index: 6;
  bottom: 30%;
  inset-inline-end: 88%;
  max-width: min(240px, 60vw);
  padding: 10px 16px;
  background: var(--color-ink);
  color: var(--color-ivory);
  border-radius: 20px 20px 4px 20px;
  font-size: 1rem;
  box-shadow: 0 14px 26px -16px rgba(14, 13, 12, 0.6);
}
.pop-you-enter-active { animation: rise 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.pop-you-leave-active { transition: opacity 0.4s, transform 0.4s; }
.pop-you-leave-to { opacity: 0; transform: translateY(-14px); }
@keyframes rise { from { opacity: 0; transform: translateY(24px) scale(0.8); } }

/* the visitor's choices arrive one after another */
.chip {
  min-height: 48px;
  padding: 10px 20px;
  border: 1px solid color-mix(in srgb, var(--color-ink) 30%, transparent);
  background: var(--color-paper);
  color: var(--color-ink);
  font-size: 1rem;
  border-radius: 999px;
  transition: background 0.2s, border-color 0.2s, transform 0.15s;
  animation: chip-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
  animation-delay: calc(var(--i, 0) * 70ms);
}
.chip:hover { border-color: var(--color-ink); background: #fbf6ec; }
.chip:active { transform: scale(0.96); }
.chip:disabled { opacity: 0.5; }
@keyframes chip-in { from { opacity: 0; transform: translateY(10px) scale(0.9); } }
.turn-area { min-height: 56px; }
.turn-enter-active { transition: opacity 0.2s; }
.turn-leave-active { transition: opacity 0.12s; }
.turn-enter-from, .turn-leave-to { opacity: 0; }
.listening { color: var(--color-ink); font-size: 0.9375rem; padding: 14px 0; }

/* what he hands over */
.finder-tile { background: var(--color-sand); border-radius: var(--radius-tile); padding: clamp(20px, 4vw, 48px); }
.pick { display: flex; gap: 16px; padding: 16px; background: var(--color-paper); border-radius: 24px; box-shadow: 0 18px 36px -24px rgba(28, 20, 12, 0.4); }
.alt { display: flex; align-items: center; gap: 12px; padding: 10px; background: var(--color-paper); border-radius: 20px; animation: chip-in 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both; animation-delay: calc(var(--i, 0) * 120ms + 200ms); }
.pick :deep(img), .alt :deep(img) { background: #efe6d6; }
.hand-enter-active { animation: hand 0.6s cubic-bezier(0.34, 1.4, 0.64, 1); }
@keyframes hand { from { opacity: 0; transform: translateY(26px) rotate(-1.5deg) scale(0.96); } }

/* phone: the shop stays pinned above; his bubble hangs under it, tail up to him */
@media (max-width: 1023px) {
  .speech {
    position: relative;
    top: auto;
    inset-inline-start: auto;
    width: auto;
    margin-top: 12px;
    font-size: 1.125rem;
    transform-origin: 50% 0;
  }
  .speech::before { top: -14px; inset-inline-start: calc(50% - 9px); border-width: 0 9px 14px 9px !important; border-color: transparent transparent var(--color-ink) transparent !important; }
  .speech::after { top: -11px; inset-inline-start: calc(50% - 7px); border-width: 0 7px 12px 7px !important; border-color: transparent transparent #fffaf0 transparent !important; }
  .you { bottom: auto; top: 18%; inset-inline-end: 4%; }
  .pick { flex-direction: column; }
}
@media (prefers-reduced-motion: reduce) {
  .chip, .alt, .pop-enter-active, .pop-you-enter-active, .hand-enter-active { animation: none; }
}
</style>
