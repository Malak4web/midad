import { defineStore } from 'pinia'

let seq = 0

export const useUiStore = defineStore('ui', {
  // sheet: the phone's open bottom sheet ('shop' | 'search'), the cart has its own flag
  state: () => ({ cartOpen: false, menuOpen: false, sheet: null, toasts: [] }),
  actions: {
    toast(message, { action = null, timeout = 3600 } = {}) {
      const id = ++seq
      this.toasts.push({ id, message, action })
      setTimeout(() => this.dismiss(id), timeout)
    },
    dismiss(id) {
      this.toasts = this.toasts.filter((t) => t.id !== id)
    },
  },
})
