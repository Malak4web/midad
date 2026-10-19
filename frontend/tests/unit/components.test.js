// TST-10: the storefront's building blocks, mounted in isolation.
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

const addToCart = vi.fn()
vi.mock('@/composables/useAddToCart', () => ({ useAddToCart: () => addToCart, firstError: (e) => e.message }))
vi.mock('@/services/api', () => ({
  api: {
    products: vi.fn(async () => ({ data: Array.from({ length: 8 }, (_, i) => ({ id: i + 1, slug: `p${i + 1}`, name: `عطر ${i + 1}`, latin: `P${i + 1}`, category: i < 4 ? 'men' : 'women', inspiredBy: 'أصل', signature: 'دافئ', notes: [], variants: [{ ml: 10, price: 300, stock: 5 }] })) })),
    finderQuestions: vi.fn(async () => ({ data: [] })),
    finderResult: vi.fn(async () => ({ data: [] })),
    cart: { get: vi.fn(async () => ({ data: { items: [], count: 0, summary: {} } })) },
  },
}))
vi.mock('@/services/home', async (orig) => ({ ...(await orig()), getHome: vi.fn(async () => ({ discovery: { price: 350, samples: 6, sampleMl: 2 }, sections: [] })) }))
vi.mock('vue-router', () => ({ useRouter: () => ({ push: vi.fn() }), useRoute: () => ({ query: {}, params: {} }) }))

const global = { stubs: { RouterLink: RouterLinkStub } }
const palette = { bg: '#2B1B12', surface: '#3A261A', fg: '#F7E9D2', muted: '#D9BF9A', accent: '#E8AE5C' }
const product = (o = {}) => ({
  slug: 'victory-elixir', name: 'فيكتوري إكسير', latin: 'Victory Elixir', inspiredBy: 'إنفيكتوس إكسير', signature: 'العنبر والسويت',
  notes: ['الهيل', 'العنبر'], variants: [{ ml: 10, price: 350, stock: 3 }, { ml: 30, price: 650, stock: 0 }, { ml: 50, price: 950, stock: 4 }, { ml: 100, price: 1450, stock: 2 }],
  ...o,
})

beforeAll(() => {
  globalThis.ResizeObserver = class { observe() {} disconnect() {} }
  globalThis.matchMedia = (q) => ({ matches: false, media: q, addEventListener() {}, removeEventListener() {} })
  setActivePinia(createPinia())
})

describe('SizePicker', async () => {
  const SizePicker = (await import('@/components/ui/SizePicker.vue')).default
  it('selects with v-model and shows each price', async () => {
    const w = mount(SizePicker, { props: { variants: product().variants, modelValue: 50, 'onUpdate:modelValue': (v) => w.setProps({ modelValue: v }) } })
    expect(w.findAll('input[type=radio]')).toHaveLength(4)
    expect(w.text()).toContain('٩٥٠ ج.م')
    await w.find('input[value="100"]').setValue()
    expect(w.props('modelValue')).toBe(100)
    expect(w.find('label.is-on').text()).toContain('١٠٠ مل')
  })
  it('marks sold-out sizes and blocks them', () => {
    const w = mount(SizePicker, { props: { variants: product().variants, modelValue: 50 } })
    const out = w.find('input[value="30"]')
    expect(out.attributes('disabled')).toBeDefined()
    expect(out.element.closest('label').textContent).toContain('نفد')
  })
})

describe('ScentPyramid', async () => {
  const ScentPyramid = (await import('@/components/home/ScentPyramid.vue')).default
  it('lists the three tiers in order', () => {
    const w = mount(ScentPyramid, { props: { pyramid: { top: ['الهيل', 'الخزامى'], heart: ['العنبر'], base: ['الفانيليا'] } } })
    expect(w.findAll('dt').map((d) => d.text())).toEqual(['القمةTOP', 'القلبHEART', 'القاعدةBASE'])
    expect(w.find('dd').text()).toContain('الهيل، الخزامى')
  })
  it('shows only the tiers it has, and nothing for a missing pyramid', () => {
    expect(mount(ScentPyramid, { props: { pyramid: { top: [], heart: ['الورد'], base: [] } } }).findAll('dt')).toHaveLength(1)
    expect(mount(ScentPyramid, { props: { pyramid: null } }).find('dl').exists()).toBe(false)
  })
})

describe('ColorCard', async () => {
  const ProductCard = (await import('@/components/catalog/ColorCard.vue')).default
  it('links to the product, shows the lowest price and the original', () => {
    const w = mount(ProductCard, { props: { product: product(), scene: 'amber', family: 'عنبري' }, global })
    expect(w.findComponent(RouterLinkStub).props('to')).toEqual({ name: 'product', params: { slug: 'victory-elixir' } })
    expect(w.text()).toContain('مستوحى من إنفيكتوس إكسير')
    expect(w.text()).toContain('من ٣٥٠ ج.م')
  })
  it('adds the 50 ml with a named button', async () => {
    const w = mount(ProductCard, { props: { product: product(), scene: 'amber', family: 'عنبري' }, global })
    const btn = w.find('button')
    expect(btn.attributes('aria-label')).toBe('أضف فيكتوري إكسير ٥٠ مل إلى السلة')
    await btn.trigger('click')
    expect(addToCart).toHaveBeenCalledWith(expect.objectContaining({ slug: 'victory-elixir' }), 50)
  })
  it('wears its scent family: the chip and the colour', () => {
    const w = mount(ProductCard, { props: { product: product(), scene: 'amber', family: 'عنبري' }, global })
    expect(w.text()).toContain('عنبري')
    expect(w.find('article').attributes('style')).toMatch(/background/)
  })
  it('uses the uploaded photo with its srcset when there is one', () => {
    const image = { src: '/storage/a-800.webp', srcset: '/storage/a-400.webp 400w, /storage/a-800.webp 800w', alt: 'زجاجة' }
    const img = mount(ProductCard, { props: { product: product({ image }) }, global }).find('img')
    expect(img.attributes('srcset')).toBe(image.srcset)
    expect(img.attributes('loading')).toBe('lazy')
  })
})

describe('ProductSlider', async () => {
  const ProductSlider = (await import('@/components/home/ProductSlider.vue')).default
  const make = () => mount(ProductSlider, { props: { products: [product({ slug: 'a' }), product({ slug: 'b' }), product({ slug: 'c' })], palette, title: 'عطور أخرى' }, global, attachTo: document.body })
  it('is a labelled region whose slides say where they are', () => {
    const w = make()
    const region = w.find('[role=region]')
    expect(region.attributes('aria-roledescription')).toBe('سلايدر')
    expect(document.getElementById(region.attributes('aria-labelledby')).textContent).toBe('عطور أخرى')
    expect(w.findAll('li.slide').map((l) => l.attributes('aria-label'))).toEqual(['١ من ٣', '٢ من ٣', '٣ من ٣'])
    expect(w.find('ul').attributes('tabindex')).toBe('0') // arrow keys scroll the track natively
    w.unmount()
  })
  it('starts with "previous" disabled and moves forward to the visual left in RTL', async () => {
    const w = make()
    const track = w.find('ul').element
    track.scrollBy = vi.fn()
    const [prev, next] = w.findAll('button.nav-btn')
    expect(prev.attributes('disabled')).toBeDefined()
    await next.trigger('click')
    expect(track.scrollBy.mock.calls[0][0].left).toBeLessThanOrEqual(0)
    w.unmount()
  })
})

describe('SpotlightSection', async () => {
  const SpotlightSection = (await import('@/components/home/SpotlightSection.vue')).default
  const section = (spot = {}) => ({
    category: { slug: 'men', name: 'رجالي' },
    palette,
    others: [product({ slug: 'x', name: 'عطر آخر' })],
    spotlight: { tagline: 'دفءُ العنبر.', texture: 'amber', ingredients: ['vanilla'], pyramid: { top: ['الهيل'], heart: ['العنبر'], base: ['الفانيليا'] }, product: product(), ...spot },
  })
  it('paints the featured perfume in its identity, then the slider of the rest', () => {
    const w = mount(SpotlightSection, { props: { section: section() }, global })
    expect(w.find('h2').text()).toBe('رجالي')
    expect(w.find('h3').text()).toContain('فيكتوري إكسير')
    expect(w.text()).toContain('دفءُ العنبر.')
    expect(w.find('.name').attributes('style')).toContain('color: rgb(247, 233, 210)') // palette fg on the name tile
    expect(w.text()).toContain('عطر آخر')
  })
  it('still renders when the featured perfume has no pyramid', () => {
    const w = mount(SpotlightSection, { props: { section: section({ pyramid: null }) }, global })
    expect(w.find('h3').text()).toContain('فيكتوري إكسير')
    expect(w.find('dl').exists()).toBe(false)
  })
})

describe('FormField (TST-11)', async () => {
  const FormField = (await import('@/components/ui/FormField.vue')).default
  it('ties the label, the hint and the error to the input', async () => {
    const w = mount(FormField, { props: { label: 'رقم الموبايل', required: true, hint: 'هنتواصل معاك عليه.', modelValue: '' } })
    const input = w.find('input')
    expect(w.find('label').attributes('for')).toBe(input.attributes('id'))
    expect(input.attributes('required')).toBeDefined()
    expect(w.find('label [aria-hidden=true]').text()).toBe('*')
    expect(w.find(`#${input.attributes('aria-describedby')}`).text()).toBe('هنتواصل معاك عليه.')
    expect(input.attributes('aria-invalid')).toBe('false')

    await w.setProps({ error: ['رقم موبايل مصري غير صحيح.'] })
    expect(input.attributes('aria-invalid')).toBe('true')
    expect(w.find(`#${input.attributes('aria-describedby')}`).text()).toBe('رقم موبايل مصري غير صحيح.')
  })
})

describe('Discovery box counter', async () => {
  const DiscoveryView = (await import('@/views/DiscoveryView.vue')).default
  it('counts down to six and refuses a seventh', async () => {
    const w = mount(DiscoveryView, { global, attachTo: document.body })
    await flushPromises()
    const picks = w.findAll('button.pick')
    expect(picks.length).toBe(8)
    for (const b of picks.slice(0, 6)) await b.trigger('click')
    expect(w.findAll('button.pick[aria-pressed=true]')).toHaveLength(6)
    await picks[6].trigger('click')
    expect(w.findAll('button.pick[aria-pressed=true]')).toHaveLength(6) // the seventh is refused
    await picks[0].trigger('click')
    expect(w.findAll('button.pick[aria-pressed=true]')).toHaveLength(5)
    w.unmount()
  })
  it('narrows the tiles as you type, and says when nothing matches', async () => {
    const w = mount(DiscoveryView, { global, attachTo: document.body })
    await flushPromises()
    await w.find('input[type=search]').setValue('لا شيء يطابق هذا')
    expect(w.findAll('button.pick')).toHaveLength(0)
    expect(w.text()).toContain('لا يوجد عطر بهذا الوصف')
    w.unmount()
  })
})
