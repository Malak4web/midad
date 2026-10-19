import { describe, expect, it } from 'vitest'
import { normalizeArabic, searchProducts } from '@/utils/arabic'
import { contrastRatio, readableOn } from '@/utils/contrast'
import { recommend } from '@/utils/finder'
import { products } from '@/data/catalog'
import { spotlights } from '@/data/spotlights'

describe('normalizeArabic', () => {
  it('unifies alef forms, ta marbuta, ya and strips diacritics', () => {
    expect(normalizeArabic('إنفيكتوس')).toBe('انفيكتوس')
    expect(normalizeArabic('عِطْرٌ')).toBe('عطر')
    expect(normalizeArabic('قهوة')).toBe('قهوه')
    expect(normalizeArabic('مصطفى')).toBe('مصطفي')
    expect(normalizeArabic('  مـــداد  ')).toBe('مداد')
  })
})

describe('searchProducts', () => {
  it('finds a perfume by the original it is inspired by', () => {
    const r = searchProducts(products, 'انفيكتوس')
    expect(r[0].p.slug).toBe('victory-elixir')
    expect(r[0].viaOriginal).toBe(true)
  })
  it('finds by Latin name, case-insensitively', () => {
    expect(searchProducts(products, 'black oud')[0].p.slug).toBe('black-oud-velvet')
  })
  it('returns nothing for an empty query', () => {
    expect(searchProducts(products, '   ')).toEqual([])
  })
})

describe('contrast', () => {
  it('computes WCAG ratios', () => {
    expect(contrastRatio('#000000', '#FFFFFF')).toBeCloseTo(21, 0)
    expect(contrastRatio('#0E0D0C', '#F2EADB')).toBeGreaterThan(15)
  })
  it('replaces an unreadable foreground', () => {
    expect(readableOn('#F2EADB', '#E8E0D0')).toBe('#0E0D0C')
    expect(readableOn('#15110F', '#EFE4D1')).toBe('#EFE4D1')
  })
  it('every sample spotlight palette has readable text', () => {
    for (const s of Object.values(spotlights)) {
      expect(contrastRatio(s.palette.bg, s.palette.fg)).toBeGreaterThanOrEqual(4.5)
    }
  })
})

describe('finder', () => {
  it('recommends oud perfumes for an evening oud lover', () => {
    const r = recommend(products, { who: 'him', when: 'night', mood: 'oud' })
    expect(r).toHaveLength(3)
    expect(r.some((x) => x.p.notes.includes('العود'))).toBe(true)
  })
  it('leans to the women collection for a gift to her', () => {
    const r = recommend(products, { who: 'her', when: 'daily', mood: 'floral' })
    expect(r[0].p.category).toBe('women')
  })
})

