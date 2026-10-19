// Forgiving text search for short lists (the discovery picker): Arabic letters
// that people type interchangeably are folded together, harakat and tatweel are
// dropped, Latin is lower-cased. Every word of the query must appear somewhere,
// either as typed or by its consonants alone, so «فانيلا» finds «الفانيليا».

/** fold a string for comparison */
export function fold(s) {
  return String(s || '')
    .toLowerCase()
    .replace(/[ً-ٰٟـ]/g, '') // harakat, dagger alef, tatweel
    .replace(/[أإآٱ]/g, 'ا')
    .replace(/ى/g, 'ي')
    .replace(/ة/g, 'ه')
    .replace(/ؤ/g, 'و')
    .replace(/ئ/g, 'ي')
    .replace(/\s+/g, ' ')
    .trim()
}

// the word without its long vowels: people spell those freely in transliterations
const skeleton = (s) => s.replace(/[اوي]/g, '')

/** does `text` (one string or many) contain every word of `query`? */
export function matches(text, query) {
  const q = fold(query)
  if (!q) return true
  const hay = fold(Array.isArray(text) ? text.join(' ') : text)
  const bones = skeleton(hay)
  return q.split(' ').every((w) => hay.includes(w) || (skeleton(w).length >= 3 && bones.includes(skeleton(w))))
}
