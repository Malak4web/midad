// SAMPLE spotlight identities. In production these come from the admin
// (category_spotlights, BE-04 / FE-41). Featured perfumes are not decided yet;
// these three exist to prove the template carries very different worlds.
// The top/heart/base split is illustrative until the real pyramid arrives (SET-05).

export const spotlights = {
  men: {
    sample: true,
    product: 'victory-elixir',
    tagline: 'دفءُ العنبر، وحلاوةُ الفانيليا، وهيلٌ يوقظ البداية.',
    palette: { bg: '#4A2A14', surface: '#5C361B', fg: '#F7E9D2', muted: '#D9BF9A', accent: '#E8AE5C' },
    texture: 'resin',
    ingredients: ['vanilla', 'amber', 'lavender', 'cardamom'],
    pyramid: { top: ['الهيل', 'الخزامى'], heart: ['العنبر'], base: ['الفانيليا', 'خشب الجواياك'] },
  },
  women: {
    sample: true,
    product: 'her-blossom',
    tagline: 'فراولةٌ وتوتٌ أسود، يهدآن على مسكٍ ناعم.',
    palette: { bg: '#F3D8DA', surface: '#FAEAEA', fg: '#3E1621', muted: '#7A4553', accent: '#A8324E' },
    texture: 'petals',
    ingredients: ['strawberry', 'blackberry', 'jasmine', 'musk'],
    pyramid: { top: ['الفراولة', 'الكرز'], heart: ['التوت الأسود', 'الياسمين'], base: ['المسك', 'العنبر'] },
  },
  oriental: {
    sample: true,
    product: 'black-oud-velvet',
    tagline: 'بخورٌ وتبغٌ وعود، يكتبون أثراً للمساء.',
    palette: { bg: '#15110F', surface: '#221B17', fg: '#EFE4D1', muted: '#A9997F', accent: '#C99A5B' },
    texture: 'smoke',
    ingredients: ['incense', 'tobacco', 'oud', 'resin'],
    pyramid: { top: ['البخور', 'الراتنجات'], heart: ['التبغ', 'العود'], base: ['الأخشاب'] },
  },
}

// Midad's own palette: used when a category has no featured perfume yet.
export const fallbackPalette = { bg: '#3B342C', surface: '#4A4137', fg: '#F2EADB', muted: '#CFC4B2', accent: '#D6B77E' }
