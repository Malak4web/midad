<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { MOTIONS, REST, pose as poseOf } from '@/data/attar'

// The Attar in the arched doorway of his shop, behind his counter.
// One <video> per motion, stacked; the visible one cross-fades, so chaining
// motions never shows a cut (they all begin and end in the same rest pose).
//   play(name)  → plays a one-shot motion, resolves when he is back at rest
//   loop(name)  → sets the motion he returns to (idle, listen)
//   arm()       → starts loading every motion (call once a conversation begins)
// Reduced motion: no video; a still pose stands in for each motion.
// the default slot is drawn over the shop: his speech bubble lives there
defineProps({ compact: { type: Boolean, default: false } })
const emit = defineEmits(['poke'])

const calm = typeof matchMedia !== 'undefined' && matchMedia('(prefers-reduced-motion: reduce)').matches
const STILL = { idle: 'portrait', listen: 'portrait', smile: 'heart', explain: 'offer', present: 'offer', smell: 'smelling', wave: 'hello', thumbs: 'approve' }

const root = ref(null)
const vids = reactive({})
const armed = reactive({ idle: true, wave: true })
const current = ref('idle')
const base = ref('idle')
let pending = null
let visible = false
let ticking = false

function el(name) {
  return vids[name]
}
function arm(names = Object.keys(MOTIONS)) {
  for (const n of names) armed[n] = true
}

async function start(name) {
  const v = el(name)
  if (!v) return false
  try {
    if (!MOTIONS[name].loop) v.currentTime = 0
    await v.play()
    return true
  } catch {
    return false // autoplay refused: the poster still shows the rest pose
  }
}

function loop(name) {
  base.value = name
  armed[name] = true
  if (MOTIONS[current.value]?.loop) {
    current.value = name
    if (visible) requestAnimationFrame(() => start(name))
  }
}

function play(name) {
  pending?.()
  if (calm || !MOTIONS[name]) {
    current.value = name
    return new Promise((r) => setTimeout(r, 900)) // still poses hold briefly
  }
  armed[name] = true
  return new Promise((resolve) => {
    let done = false
    const finish = () => {
      if (done) return
      done = true
      pending = null
      el(name)?.removeEventListener('ended', finish)
      el(name)?.pause() // an interrupted motion must not keep playing unseen
      current.value = base.value
      start(base.value)
      resolve()
    }
    pending = finish
    // wait for the element to exist (armed this tick), then show it once it plays
    requestAnimationFrame(async () => {
      const v = el(name)
      if (!v) return finish()
      v.addEventListener('ended', finish)
      if (await start(name)) current.value = name
      else finish()
    })
  })
}

// pause everything off screen; resume the loop when he comes back into view
function check() {
  ticking = false
  const r = root.value?.getBoundingClientRect()
  const now = !!r && r.bottom > 0 && r.top < innerHeight
  if (now === visible) return
  visible = now
  if (visible) start(current.value)
  else Object.values(vids).forEach((v) => v?.pause())
}
function onScroll() {
  if (ticking) return
  ticking = true
  requestAnimationFrame(check)
}
onMounted(() => {
  addEventListener('scroll', onScroll, { passive: true })
  check()
})
onBeforeUnmount(() => {
  pending?.()
  removeEventListener('scroll', onScroll)
})

defineExpose({ play, loop, arm, visible: () => visible })
const still = (name) => poseOf(STILL[name] || 'portrait')
</script>

<template>
  <figure ref="root" class="shop relative m-0" :class="compact && 'is-compact'">
    <div class="niche relative overflow-hidden">
      <span class="arch-line" aria-hidden="true" />
      <button type="button" class="figure absolute inset-x-0 bottom-0 mx-auto block border-0 bg-transparent p-0" aria-label="خبير العطور: انقر ليحدّثك" @click="emit('poke')">
        <template v-if="!calm">
          <video
            v-for="(m, name) in MOTIONS"
            :key="name"
            :ref="(v) => (vids[name] = v)"
            class="motion"
            :class="current === name && 'is-on'"
            :src="armed[name] ? m.src : undefined"
            :poster="REST"
            :loop="m.loop"
            :preload="armed[name] ? 'auto' : 'none'"
            muted
            playsinline
            disablepictureinpicture
            aria-hidden="true"
          />
        </template>
        <img v-else :src="still(current)?.src" :srcset="still(current)?.srcset" sizes="420px" alt="" class="motion is-on still" />
      </button>
    </div>
    <div class="counter" aria-hidden="true"><span>خبير العطور</span></div>
    <slot />
  </figure>
</template>

<style scoped>
/* the shop: an arched doorway painted the colour of his studio light */
.shop { width: 100%; max-width: 420px; margin-inline: auto; }
.niche {
  aspect-ratio: 3 / 3.7;
  border-radius: 999px 999px 0 0 / 48% 48% 0 0;
  background:
    radial-gradient(ellipse 70% 50% at 50% 38%, rgba(255, 246, 228, 0.55), transparent 70%),
    linear-gradient(180deg, #d9c19f 0%, #e3cdb0 46%, #e8d5b9 100%);
  box-shadow: inset 0 18px 40px -20px rgba(59, 42, 30, 0.45), inset 0 -10px 30px -18px rgba(59, 42, 30, 0.3);
}
.arch-line {
  position: absolute;
  inset: 10px 10px -2px;
  border: 1px solid rgba(176, 141, 87, 0.75);
  border-bottom: 0;
  border-radius: inherit;
  pointer-events: none;
  z-index: 2;
}
.figure { height: 94%; aspect-ratio: 360 / 404; cursor: pointer; }
.motion {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0;
  transition: opacity 0.22s ease;
  /* his studio wall melts into the arch: no rectangle edge */
  -webkit-mask-image: radial-gradient(ellipse 62% 70% at 50% 52%, #000 66%, transparent 100%);
  mask-image: radial-gradient(ellipse 62% 70% at 50% 52%, #000 66%, transparent 100%);
}
.motion.is-on { opacity: 1; }
.still { object-position: 50% 12%; }
.figure:hover .motion.is-on { filter: brightness(1.03); }
.figure:active { transform: scale(0.995); }

/* the counter he stands behind */
.counter {
  position: relative;
  z-index: 3;
  margin-top: -14%;
  height: 16%;
  min-height: 48px;
  display: grid;
  place-items: center;
  background: var(--color-ink);
  border-radius: 0 0 22px 22px;
  box-shadow: 0 -6px 18px -8px rgba(14, 13, 12, 0.5), 0 24px 30px -20px rgba(14, 13, 12, 0.6);
}
.counter span { font-family: var(--font-display); font-weight: 700; color: var(--color-gold-light); font-size: 1.125rem; }

.is-compact { max-width: 250px; }
.is-compact .niche { aspect-ratio: 3 / 2.6; }
.is-compact .figure { height: 112%; bottom: -14%; }
.is-compact .counter { margin-top: -8%; height: 18%; min-height: 40px; }
.is-compact .counter span { font-size: 0.9375rem; }
</style>
