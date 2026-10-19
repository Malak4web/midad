import { describe, expect, it } from 'vitest'
import { familiesOf, scenesFor } from '@/utils/scenes'

const p = (signature, notes = []) => ({ signature, notes })

describe('product scenes', () => {
  it('reads the family from the signature note first', () => {
    expect(familiesOf(p('البندق المكرمل', ['التوفي', 'الفانيليا']))).toEqual(['gourmand', 'amber'])
    expect(familiesOf(p('الانتعاش البحري'))[0]).toBe('fresh')
    expect(familiesOf(p('العود والأخشاب'))).toEqual(['oriental', 'woody'])
    // orange blossom is a flower, not citrus
    expect(familiesOf(p('العسل الفاخر', ['العسل', 'زهر البرتقال']))).toEqual(['gourmand', 'floral'])
  })

  it('falls back to woody when nothing is recognised', () => {
    expect(familiesOf(p('شيء غامض'))).toEqual(['woody'])
  })

  it('never gives two neighbours the same scene', () => {
    // the men's row as served today
    const men = [
      p('البندق المكرمل', ['التوفي', 'الفانيليا', 'القرفة']),
      p('الهيل والليمون', ['الهيل', 'الليمون', 'الزنجبيل']),
      p('الحمضيات والأرز', ['الأخشاب العطرية', 'الجريب فروت', 'البخور']),
      p('الانتعاش البحري', ['النوتات البحرية', 'الجريب فروت', 'الياسمين']),
      p('الكمثرى والفانيليا', ['الكمثرى', 'الفانيليا', 'القرفة']),
      p('الكراميل الدافئ', ['الكراميل', 'التونكا', 'الميرمية']),
    ]
    const s = scenesFor(men)
    expect(s).toEqual(['gourmand', 'oriental', 'woody', 'fresh', 'amber', 'gourmand'])
    s.forEach((k, i) => i && expect(k).not.toBe(s[i - 1]))
  })

  it('uses the gift scene when a card has nowhere else to go', () => {
    expect(scenesFor([p('العسل'), p('الكراميل')])).toEqual(['gourmand', 'gift'])
  })
})

describe('forgiving search', async () => {
  const { fold, matches } = await import('@/utils/search')
  it('folds the letters people type interchangeably', () => {
    expect(fold('أُوشَن ويف')).toBe('اوشن ويف')
    expect(fold('مضاوي رويالة')).toBe(fold('مضاوى رويالہ'.replace('ہ', 'ة')))
    expect(fold('Bleu ROYALE')).toBe('bleu royale')
  })
  it('needs every word, in any order and any field', () => {
    const p = ['بلو رويال', 'بلو دي شانيل', 'الحمضيات والأرز', 'Bleu Royale']
    expect(matches(p, 'شانيل')).toBe(true)
    expect(matches(p, 'ارز بلو')).toBe(true)
    expect(matches(p, 'royale')).toBe(true)
    expect(matches(p, 'عود')).toBe(false)
    expect(matches(p, '  ')).toBe(true)
  })
  it('forgives how the long vowels are spelled', () => {
    expect(matches(['الفانيليا', 'العنبر'], 'فانيلا')).toBe(true)
    expect(matches(['أفنتوس ليجند', 'كريد أفنتوس'], 'افينتوس')).toBe(true)
    expect(matches(['العود'], 'ورد')).toBe(false)
  })
})
