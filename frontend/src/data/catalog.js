// Mock catalog used until the Laravel API is wired (INT-04/INT-05).
// Source: «دليل العطور والاستشارات للأونلاين» — names, notes and "inspired by" are real.
// PLACEHOLDER: prices per size are illustrative only and must be replaced (SET-05).

export const SIZES = [10, 30, 50, 100]

const PRICE_TABLE = {
  men: { 10: 350, 30: 650, 50: 950, 100: 1450 },
  women: { 10: 350, 30: 650, 50: 950, 100: 1450 },
  oriental: { 10: 400, 30: 750, 50: 1050, 100: 1550 },
}

export const categories = [
  { slug: 'men', name: 'رجالي', latin: 'FOR HIM' },
  { slug: 'women', name: 'نسائي', latin: 'FOR HER' },
  { slug: 'oriental', name: 'شرقي ونيش', latin: 'ORIENTAL & NICHE' },
]

const raw = [
  // ── men
  ['men', 'victory-elixir', 'Victory Elixir', 'فيكتوري إكسير', 'إنفيكتوس إكسير', 'العنبر والسويت', ['الفانيليا', 'العنبر', 'الخزامى', 'الهيل', 'خشب الجواياك']],
  ['men', 'together-intense', 'Together Intense', 'توجيذر إنتنس', 'سترونجر ويذ يو إنتنس', 'البندق المكرمل', ['التوفي', 'الفانيليا', 'القرفة', 'العنبر', 'الفلفل الوردي', 'الميرمية']],
  ['men', 'wanted-night', 'Wanted Night', 'وانتد نايت', 'أزارو وانتد', 'الهيل والليمون', ['الهيل', 'الليمون', 'الزنجبيل', 'التونكا', 'نجيل الهند']],
  ['men', 'bleu-royale', 'Bleu Royale', 'بلو رويال', 'بلو دي شانيل', 'الحمضيات والأرز', ['الأخشاب العطرية', 'الجريب فروت', 'البخور', 'الصندل', 'الزنجبيل']],
  ['men', 'ocean-wave', 'Ocean Wave', 'أوشن ويف', 'أكوا دي جيو', 'الانتعاش البحري', ['النوتات البحرية', 'الجريب فروت', 'الياسمين', 'الأرز', 'المسك']],
  ['men', 'ultra-passion', 'Ultra Passion', 'ألترا باشن', 'ألترا ميل', 'الكمثرى والفانيليا', ['الكمثرى', 'الفانيليا', 'القرفة', 'اللافندر', 'النعناع', 'العنبر']],
  ['men', 'absolute-scandal', 'Absolute Scandal', 'أبسولوت سكاندل', 'سكاندل رجالي', 'الكراميل الدافئ', ['الكراميل', 'التونكا', 'الميرمية', 'اللافندر', 'المندرين']],
  ['men', 'aventus-legend', 'Aventus Legend', 'أفنتوس ليجند', 'كريد أفنتوس', 'الأناناس المدخّن', ['الأناناس', 'البتولا المدخّن', 'التفاح الأخضر', 'المسك', 'الكشمش']],
  ['men', 'elixir-male', 'Elixir Male', 'إكسير مال', 'لومال إكسير', 'العسل واللافندر', ['العسل', 'الفانيليا', 'اللافندر', 'الجاوي', 'التونكا']],
  ['men', 'roma-velvet', 'Roma Velvet', 'روما فيلفت', 'فالنتينو بورن إن روما', 'الفانيليا والعنبر', ['الفانيليا', 'اللافندر', 'نجيل الهند', 'الأخشاب العنبرية']],
  // ── women
  ['women', 'her-blossom', 'Her Blossom', 'هير بلوسوم', 'بربري هير', 'الفراولة والتوت', ['الفراولة', 'التوت الأسود', 'الكرز', 'المسك', 'العنبر', 'الياسمين']],
  ['women', 'sweet-scandal', 'Sweet Scandal', 'سويت سكاندل', 'سكاندل نسائي', 'العسل الفاخر', ['العسل', 'زهر البرتقال', 'الغاردينيا', 'الكراميل', 'الباتشولي']],
  ['women', 'white-cream', 'White Cream', 'وايت كريم', 'بيانكو لاتيه', 'الحليب والفانيليا', ['الحليب', 'الكراميل', 'الفانيليا', 'الكومارين', 'المسك الأبيض']],
  ['women', 'marshmallow-cloud', 'Marshmallow Cloud', 'مارشميلو كلاود', 'مارشميلو', 'الحلوى والمارشميلو', ['المارشميلو', 'السكر المكرمل', 'الفانيليا', 'مسك الروم']],
  ['women', 'heavenly-love', 'Heavenly Love', 'هيفنلي لوف', 'لاف إز هيفنلي', 'الأزهار الناعمة', ['أزهار الكرز', 'التوت الأبيض', 'المسك الوردي', 'الفريزيا']],
  ['women', 'lady-rose', 'Lady Rose', 'ليدي روز', 'ميس ديور', 'الورد الجوري', ['الورد الجوري', 'زنبق الوادي', 'الفاوانيا', 'السوسن', 'الباتشولي']],
  ['women', 'paris-glow', 'Paris Glow', 'باريس جلو', 'باريس هيلتون', 'التفاح والفواكه', ['التفاح الأخضر', 'الخوخ', 'الفريزيا', 'الصندل', 'الميموزا']],
  ['women', 'cavalli-gold', 'Cavalli Gold', 'كافالي جولد', 'روبيرتو كافالي', 'زهر البرتقال الشرقي', ['زهر البرتقال', 'الفانيليا', 'الجاوي', 'التونكا']],
  ['women', 'freedom-aura', 'Freedom Aura', 'فريدم أورا', 'ليبر', 'اللافندر الأنثوي', ['اللافندر', 'زهر البرتقال', 'الفانيليا', 'المندرين', 'العنبر']],
  ['women', 'sugar-bloom', 'Sugar Bloom', 'شوجر بلوم', 'تاسي شوجر', 'السكر والفواكه', ['السكر الناعم', 'التوت', 'الفانيليا', 'غزل البنات', 'المسك']],
  // ── oriental & niche
  ['oriental', 'madawi-royale', 'Madawi Royale', 'مضاوي رويال', 'مضاوي', 'الباتشولي والورد', ['أزهار الخوخ', 'أزهار التفاح', 'الباتشولي', 'الأناناس', 'المسك']],
  ['oriental', 'the-rebel-oud', 'The Rebel Oud', 'ذا ريبل عود', 'الثائر', 'العود والأخشاب', ['العود', 'البخور', 'الجلود', 'العنبر', 'الهيل']],
  ['oriental', 'qahwa-fusion', 'Qahwa Fusion', 'قهوة فيوجن', 'خمرة قهوة', 'القرفة والقهوة', ['القهوة', 'القرفة', 'الجوز', 'التونكا', 'الفانيليا']],
  ['oriental', 'black-oud-velvet', 'Black Oud Velvet', 'بلاك عود فيلفت', 'بلاك أفغانو', 'البخور والتبغ', ['الراتنجات', 'البخور', 'التبغ', 'العود', 'الأخشاب']],
  ['oriental', 'royal-valley', 'Royal Valley', 'رويال فالي', 'إمبريال فالي', 'الجلود والعنبر', ['العنبر', 'الجلود', 'الدافانا', 'البرغموت', 'الباتشولي']],
  ['oriental', 'pure-amber-fruit', 'Pure Amber Fruit', 'بيور أمبر فروت', 'أربا بيورا', 'الفواكه والمسك', ['البرتقال الصقلي', 'الليمون', 'المانجو', 'المسك الأبيض']],
  ['oriental', 'ehsas-pure', 'Ehsas Pure', 'إحساس بيور', 'إحساس', 'الزهور والمسك', ['الورد الاستوائي', 'المسك الأبيض', 'العنبر', 'الحمضيات']],
  ['oriental', 'stellar-amber', 'Stellar Amber', 'ستيلار أمبر', 'ستيلر تايمز', 'العنبر الفاخر', ['العنبر الشفاف', 'زهر البرتقال', 'النباتات العطرية', 'المسك']],
  ['oriental', 'symphony-breeze', 'Symphony Breeze', 'سيمفوني بريز', 'سيمفوني', 'الجريب فروت الفوّار', ['الجريب فروت', 'الزنجبيل', 'البرغموت', 'الأخشاب']],
  ['oriental', 'deep-ocean', 'Deep Ocean', 'ديب أوشن', 'ميجامير', 'الانتعاش البحري الثقيل', ['الملح البحري', 'الطحالب', 'العنبر', 'الأخشاب المائية']],
]

// PLACEHOLDER pyramid: notes sorted by classic perfumery volatility until Midad
// sends the real split. Top = citrus, fruit, fresh spice; base = woods, resins,
// amber, vanilla, musk; everything else sits in the heart.
const TOP = ['ليمون', 'جريب فروت', 'برغموت', 'مندرين', 'برتقال الصقلي', 'حمضيات', 'زنجبيل', 'هيل', 'فلفل', 'نعناع', 'أناناس', 'تفاح', 'كمثرى', 'كرز', 'فراولة', 'خوخ', 'كشمش', 'مانجو', 'بحرية', 'ملح', 'ميرمية', 'فواكه', 'توت']
const BASE = ['خشب', 'أخشاب', 'أرز', 'صندل', 'جواياك', 'نجيل', 'عنبر', 'فانيليا', 'تونكا', 'مسك', 'باتشولي', 'عود', 'بخور', 'جلود', 'جاوي', 'راتنجات', 'بتولا', 'طحالب', 'كومارين']
const has = (note, list) => list.some((k) => note.includes(k))

export function splitPyramid(notes) {
  const top = notes.filter((n) => has(n, TOP) && !has(n, BASE))
  const base = notes.filter((n) => has(n, BASE))
  const heart = notes.filter((n) => !top.includes(n) && !base.includes(n))
  // every layer gets at least one note, borrowing from its neighbour
  if (!top.length) {
    if (heart.length) top.push(heart.shift())
    else if (base.length > 1) top.push(base.shift())
  }
  if (!heart.length) {
    if (top.length > 1) heart.push(top.pop())
    else if (base.length > 1) heart.push(base.shift())
  }
  if (!base.length && heart.length > 1) base.push(heart.pop())
  return { top, heart, base }
}

export const products = raw.map(([category, slug, latin, name, inspiredBy, signature, notes], i) => ({
  id: i + 1,
  slug,
  category,
  name,
  latin,
  inspiredBy,
  signature,
  notes,
  pyramid: splitPyramid(notes),
  concentration: 'EAU DE PARFUM',
  variants: SIZES.map((ml) => ({ ml, price: PRICE_TABLE[category][ml], stock: ml === 10 && i % 7 === 3 ? 0 : 20 })),
}))

export const productBySlug = (slug) => products.find((p) => p.slug === slug)
export const productsIn = (category) => products.filter((p) => p.category === category)
export const lowestPrice = (p) => Math.min(...p.variants.map((v) => v.price))
