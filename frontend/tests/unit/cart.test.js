import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const payload = (count, total) => ({ data: { items: count ? [{ id: 1, kind: 'product', name: 'فيكتوري إكسير', ml: 50, quantity: count, unitPrice: 950, lineTotal: total, available: true }] : [], count, coupon: null, gift: { wrap: false }, summary: { subtotal: total, discount: 0, credit: 0, shipping: null, gift: 0, total } } })

vi.mock('@/services/api', () => ({
  api: {
    cart: {
      get: vi.fn(async () => payload(0, 0)),
      add: vi.fn(async (slug, ml, qty) => payload(qty, 950 * qty)),
      update: vi.fn(async (id, qty) => payload(qty, 950 * qty)),
      remove: vi.fn(async () => payload(0, 0)),
    },
  },
}))

const { api } = await import('@/services/api')
const { useCartStore } = await import('@/stores/cart')

describe('cart store (server cart)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('mirrors the server payload after adding', async () => {
    const cart = useCartStore()
    await cart.add('victory-elixir', 50, 2)
    expect(api.cart.add).toHaveBeenCalledWith('victory-elixir', 50, 2)
    expect(cart.count).toBe(2)
    expect(cart.subtotal).toBe(1900)
    expect(cart.loaded).toBe(true)
  })

  it('removes the line when quantity drops to zero', async () => {
    const cart = useCartStore()
    await cart.add('victory-elixir', 50, 1)
    await cart.setQty(1, 0)
    expect(api.cart.remove).toHaveBeenCalledWith(1)
    expect(cart.items).toEqual([])
  })

  it('keeps the previous cart and clears busy when the server refuses', async () => {
    const cart = useCartStore()
    await cart.add('victory-elixir', 50, 1)
    api.cart.update.mockRejectedValueOnce(Object.assign(new Error('x'), { errors: { quantity: ['الكمية المتاحة 1 فقط.'] } }))
    await expect(cart.setQty(1, 5)).rejects.toThrow()
    expect(cart.count).toBe(1)
    expect(cart.busy).toBe(false)
  })

  it('remembers the governorate used for shipping', async () => {
    const cart = useCartStore()
    await cart.load(3)
    expect(api.cart.get).toHaveBeenCalledWith(3)
    expect(cart.governorateId).toBe(3)
  })

  // a promise the test resolves by hand, to play responses out of order
  const deferred = () => {
    let resolve
    const promise = new Promise((r) => (resolve = r))
    return { promise, resolve }
  }

  it('a background refresh never disables the cart buttons', async () => {
    const cart = useCartStore()
    const slow = deferred()
    api.cart.get.mockReturnValueOnce(slow.promise)
    const loading = cart.load()
    expect(cart.busy).toBe(false)
    slow.resolve(payload(1, 950))
    await loading
    expect(cart.count).toBe(1)
  })

  it('a slow read that started before a write cannot undo it', async () => {
    const cart = useCartStore()
    const slowRead = deferred()
    api.cart.get.mockReturnValueOnce(slowRead.promise)
    const read = cart.load()
    await cart.add('victory-elixir', 50, 2) // write lands first
    slowRead.resolve(payload(0, 0)) // the old, empty cart arrives late
    await read
    expect(cart.count).toBe(2)
  })

  it('a read that started during a write is ignored, and the write still applies', async () => {
    const cart = useCartStore()
    const slowWrite = deferred()
    api.cart.add.mockReturnValueOnce(slowWrite.promise)
    const write = cart.add('victory-elixir', 50, 3)
    const read = cart.load() // answered with the pre-write cart
    await read
    expect(cart.count).toBe(0)
    slowWrite.resolve(payload(3, 2850))
    await write
    expect(cart.count).toBe(3)
    expect(cart.busy).toBe(false)
  })

  it('of two overlapping writes the later one wins', async () => {
    const cart = useCartStore()
    const first = deferred()
    api.cart.add.mockReturnValueOnce(first.promise)
    const a = cart.add('victory-elixir', 50, 1)
    const b = cart.add('victory-elixir', 50, 4)
    await b
    first.resolve(payload(1, 950))
    await a
    expect(cart.count).toBe(4)
    expect(cart.busy).toBe(false)
  })
})
