// Arabic normalization for search: the same rules the backend applies (BE-15).
const DIACRITICS = /[ؐ-ًؚ-ٰٟۖ-ۭ]/g
const TATWEEL = /ـ/g

export function normalizeArabic(input = '') {
  return String(input)
    .replace(DIACRITICS, '')
    .replace(TATWEEL, '')
    .replace(/[إأآٱ]/g, 'ا')
    .replace(/ى/g, 'ي')
    .replace(/ة/g, 'ه')
    .replace(/ؤ/g, 'و')
    .replace(/ئ/g, 'ي')
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .trim()
}

export function searchProducts(products, query, limit = 6) {
  const q = normalizeArabic(query)
  if (!q) return []
  const scored = []
  for (const p of products) {
    const fields = [p.name, p.latin, p.inspiredBy, p.signature, ...p.notes].map(normalizeArabic)
    let score = 0
    if (fields[0].startsWith(q) || fields[1].startsWith(q)) score += 6
    if (fields[2].includes(q)) score += 5
    if (fields[0].includes(q) || fields[1].includes(q)) score += 4
    if (fields.slice(3).some((f) => f.includes(q))) score += 1
    if (score) scored.push({ p, score, viaOriginal: fields[2].includes(q) })
  }
  return scored.sort((a, b) => b.score - a.score).slice(0, limit)
}
