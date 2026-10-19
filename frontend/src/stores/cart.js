import { defineStore } from 'pinia'
import { api } from '@/services/api'

// Server cart (INT-06). The API prices everything; this store only mirrors
// the latest cart payload and serializes writes so lines never race.
const EMPTY = { items: [], count: 0, coupon: null, gift: { wrap: false, recipient: null, message: null }, summary: { subtotal: 0, discount: 0, credit: 0, shipping: null, gift: 0, total: 0, freeShippingRemaining: null } }

export const useCartStore = defineStore('cart', {
  // busy = a write is in flight (it disables the cart's buttons); a read never sets it,
  // or a click landing during a background refresh would hit a disabled button and vanish.
  // writes/reads count requests so an old answer never replaces a newer cart: the
  // latest write always wins; a read counts only if no write ran before or during it.
  state: () => ({ cart: EMPTY, loaded: false, busy: false, governorateId: null, writes: 0, reads: 0 }),
  getters: {
    items: (s) => s.cart.items,
    count: (s) => s.cart.count,
    subtotal: (s) => s.cart.summary.subtotal,
    summary: (s) => s.cart.summary,
  },
  actions: {
    async run(call, { write = true } = {}) {
      const w = write ? ++this.writes : this.writes
      const r = write ? null : ++this.reads
      // a read that starts during a write may be answered with the pre-write cart
      const startedDuringWrite = !write && this.busy
      if (write) this.busy = true
      try {
        const { data } = await call()
        const current = write ? w === this.writes : !startedDuringWrite && w === this.writes && r === this.reads
        if (current) {
          this.cart = data
          this.loaded = true
        }
        return data
      } finally {
        if (write && w === this.writes) this.busy = false
      }
    },
    load(governorateId = this.governorateId) {
      this.governorateId = governorateId
      return this.run(() => api.cart.get(governorateId), { write: false })
    },
    add(slug, ml, qty = 1) {
      return this.run(() => api.cart.add(slug, ml, qty))
    },
    addBox(productIds) {
      return this.run(() => api.cart.addBox(productIds))
    },
    setQty(itemId, qty) {
      return this.run(() => (qty <= 0 ? api.cart.remove(itemId) : api.cart.update(itemId, qty)))
    },
    remove(itemId) {
      return this.run(() => api.cart.remove(itemId))
    },
    applyCoupon(code) {
      return this.run(() => api.cart.coupon(code))
    },
    removeCoupon() {
      return this.run(() => api.cart.removeCoupon())
    },
    setGift(wrap, recipient, message) {
      return this.run(() => api.cart.gift(wrap, recipient, message))
    },
    reset() {
      this.cart = EMPTY
    },
  },
})
