// Client-side version of the scent finder scoring (server: BE-29).
// Each answer adds weight to note keywords and categories.

export const QUESTIONS = [
  {
    key: 'who',
    q: 'لمن تختار؟',
    options: [
      { v: 'me', label: 'لنفسي' },
      { v: 'her', label: 'هدية لها', weight: { cat: { women: 3, oriental: 1 } } },
      { v: 'him', label: 'هدية له', weight: { cat: { men: 3, oriental: 1 } } },
    ],
  },
  {
    key: 'when',
    q: 'متى ستضعه غالباً؟',
    options: [
      { v: 'day', label: 'النهار والعمل', weight: { notes: ['الجريب فروت', 'الليمون', 'البحرية', 'البرغموت', 'الزنجبيل', 'التفاح', 'المندرين'] } },
      { v: 'night', label: 'المساء والسهرات', weight: { notes: ['العود', 'البخور', 'التبغ', 'العنبر', 'الفانيليا', 'الجلود'] } },
      { v: 'events', label: 'المناسبات', weight: { notes: ['العنبر', 'العود', 'الورد', 'زهر البرتقال', 'العسل'] } },
      { v: 'daily', label: 'كل يوم', weight: { notes: ['المسك', 'اللافندر', 'الفانيليا', 'الياسمين'] } },
    ],
  },
  {
    key: 'mood',
    q: 'أي أثرٍ تحب؟',
    options: [
      { v: 'sweet', label: 'دافئ وحلو', weight: { notes: ['الفانيليا', 'الكراميل', 'التوفي', 'العسل', 'السكر', 'التونكا', 'المارشميلو', 'الحليب'] } },
      { v: 'floral', label: 'زهري وناعم', weight: { notes: ['الورد', 'الياسمين', 'زهر', 'الفريزيا', 'الفاوانيا', 'أزهار', 'التوت'] } },
      { v: 'oud', label: 'عود وبخور', weight: { notes: ['العود', 'البخور', 'التبغ', 'الجلود', 'الراتنجات', 'العنبر'] } },
      { v: 'fresh', label: 'منعش وبحري', weight: { notes: ['البحرية', 'الملح', 'الجريب فروت', 'الليمون', 'الزنجبيل', 'البرغموت', 'الحمضيات'] } },
    ],
  },
]

export function recommend(products, answers, n = 3) {
  const picked = QUESTIONS.map((q) => q.options.find((o) => o.v === answers[q.key])).filter(Boolean)
  return products
    .map((p) => {
      const hay = [p.signature, ...p.notes].join(' ')
      let score = 0
      const why = []
      for (const o of picked) {
        score += o.weight?.cat?.[p.category] || 0
        const hits = (o.weight?.notes || []).filter((k) => hay.includes(k))
        score += hits.length * 2
        if (hits.length) why.push(hits[0].replace(/^ال/, ''))
      }
      return { p, score, why: [...new Set(why)].slice(0, 2) }
    })
    .sort((a, b) => b.score - a.score || a.p.id - b.p.id)
    .slice(0, n)
}
