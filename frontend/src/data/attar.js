// The Attar: Midad's perfumer, a character who talks with the visitor.
// Motions (scripts/attar.mjs) all start and end in the same rest pose, so any
// one can follow any other. Lines are his voice: warm, brief, light MSA.

const files = import.meta.glob('@/assets/attar/*.{mp4,webp}', { eager: true, import: 'default' })
const byName = Object.fromEntries(Object.entries(files).map(([p, url]) => [p.split('/').pop(), url]))

/** looping motions play until replaced; the rest play once and fall back to the loop */
export const MOTIONS = {
  idle: { src: byName['idle.mp4'], loop: true },
  listen: { src: byName['listen.mp4'], loop: true },
  smile: { src: byName['smile.mp4'] },
  explain: { src: byName['explain.mp4'] },
  present: { src: byName['present.mp4'] },
  smell: { src: byName['smell.mp4'] },
  wave: { src: byName['wave.mp4'] },
  thumbs: { src: byName['thumbs.mp4'] },
}
export const REST = byName['rest.webp']

/** a still pose as { src, srcset } (thinking, hello, smelling, samples, portrait, gift, approve, heart, offer) */
export function pose(name) {
  const small = byName[`pose-${name}-480.webp`]
  const large = byName[`pose-${name}-960.webp`]
  return small ? { src: large, srcset: `${small} 480w, ${large} 960w` } : null
}

export function greeting(hour = new Date().getHours()) {
  if (hour < 5) return 'سهرتك طيبة'
  if (hour < 12) return 'صباح الخير'
  if (hour < 17) return 'طاب يومك'
  return 'مساء الخير'
}

/** his reply to an answer, by question key and option value */
export const REPLIES = {
  who: {
    me: 'جميل، عطرٌ لك أنت. هذا أصدق أنواع العطر.',
    her: 'هديةٌ لها… سأختار ما يبقى في البال بعد أن تمضي.',
    him: 'هديةٌ له، إذن شيءٌ بثقةٍ هادئة.',
  },
  when: {
    day: 'للنهار نريده خفيفاً، يرافقك ولا يزاحم أحداً.',
    night: 'للمساء نسمح للعطر أن يعلو قليلاً.',
    events: 'للمناسبات… عطرٌ يُسأل عنه.',
    daily: 'كل يوم؟ إذن رفيقٌ لا يُملّ.',
  },
  mood: {
    sweet: 'الدفء والحلاوة، ذوقٌ طيب.',
    floral: 'الزهور… رقّةٌ لا تخطئ.',
    oud: 'العود والبخور، ذوقٌ عريق.',
    fresh: 'المنعش، كنسمة بحرٍ في الصباح.',
  },
}
export const FALLBACK_REPLY = 'فهمت.'

/** what he says when tapped, or when a visitor lingers: small perfume lessons */
export const TIPS = [
  'سرّ الثبات: رشّةٌ على النبض، ولا تفرك المعصمين.',
  'العطر يتغيّر على كل بشرة، ولهذا نجرّب قبل أن نقرّر.',
  'رشّةٌ على الملابس تبقى أطول من رشّةٍ على الجلد.',
  'لا تشمّ أكثر من ثلاثة عطور متتالية؛ الأنف يتعب كما تتعب العين.',
  'احفظ زجاجتك بعيداً عن الشمس والحمّام، فالحرارة تُتعب العطر.',
  'القمّة تتحدث أولاً، لكن القاعدة هي ما يبقى للمساء.',
]
export const NUDGE = 'خذ وقتك، لا إجابة خاطئة هنا.'

/** a small memory, kept on this device only */
const KEY = 'midad.attar'
export function remember(patch) {
  try {
    const v = { ...recall(), ...patch }
    localStorage.setItem(KEY, JSON.stringify(v))
    return v
  } catch {
    return patch
  }
}
export function recall() {
  try {
    return JSON.parse(localStorage.getItem(KEY) || '{}') || {}
  } catch {
    return {}
  }
}
