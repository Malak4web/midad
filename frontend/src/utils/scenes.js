// Which scene a product card shows, until each perfume has its own photograph.
// A product's families come from its signature first (the note it is known
// for), then its key notes, in order. Along a row no two neighbours share a
// scene: the earlier card steps to its next family when it has one, else the
// later card does, else the gift scene stands in. Families are matched in
// order, so «زهر البرتقال» (orange blossom) reads as floral, not citrus.
const FAMILIES = [
  ['floral', ['ورد', 'ياسمين', 'زهور', 'أزهار', 'زهر', 'فاوانيا', 'غاردينيا', 'فريزيا', 'زنبق', 'سوسن', 'ميموزا', 'توت', 'كرز', 'خوخ']],
  ['fresh', ['بحر', 'انتعاش', 'ليمون', 'حمضيات', 'جريب', 'برغموت', 'نعناع', 'برتقال', 'مندرين', 'تفاح']],
  ['gourmand', ['كراميل', 'مكرمل', 'توفي', 'عسل', 'حلوى', 'مارشميلو', 'بندق', 'قهوة', 'حليب', 'شوكولا', 'كمثرى', 'سكر']],
  ['oriental', ['عود', 'بخور', 'جلود', 'جلد', 'زعفران', 'هيل', 'باتشولي']],
  ['amber', ['عنبر', 'فانيليا', 'تونكا', 'لافندر', 'قرفة', 'مسك']],
  ['woody', ['أرز', 'أخشاب', 'خشب', 'صندل', 'نجيل', 'ميرمية', 'فلفل', 'زنجبيل', 'فيتيفر']],
]

/** the families a product belongs to, strongest first */
export function familiesOf(product) {
  const words = [...String(product.signature || '').split(/\s+و?|،/), ...(product.notes || [])].filter(Boolean)
  const out = []
  for (const w of words) {
    const hit = FAMILIES.find(([, keys]) => keys.some((k) => w.includes(k)))
    if (hit && !out.includes(hit[0])) out.push(hit[0])
  }
  return out.length ? out : ['woody']
}

/** one scene key per product, neighbours always different */
export function scenesFor(products) {
  const fams = products.map(familiesOf)
  const pick = fams.map((f) => f[0])
  for (let i = 1; i < pick.length; i++) {
    if (pick[i] !== pick[i - 1]) continue
    const prevAlt = fams[i - 1].find((f) => f !== pick[i] && f !== pick[i - 2])
    const ownAlt = fams[i].find((f) => f !== pick[i - 1])
    if (prevAlt) pick[i - 1] = prevAlt
    else pick[i] = ownAlt || 'gift'
  }
  return pick
}
