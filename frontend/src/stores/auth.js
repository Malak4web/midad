import { defineStore } from 'pinia'
import { api } from '@/services/api'
import { useCartStore } from './cart'

export const useAuthStore = defineStore('auth', {
  state: () => ({ user: null, checked: false }),
  getters: {
    signedIn: (s) => !!s.user,
    isStaff: (s) => ['staff', 'admin'].includes(s.user?.role),
  },
  actions: {
    async fetch() {
      try {
        this.user = (await api.auth.me()).data
      } catch {
        this.user = null
      } finally {
        this.checked = true
      }
      return this.user
    },
    async login(email, password) {
      this.user = (await api.auth.login(email, password)).data
      await useCartStore().load() // the guest cart was merged server-side
    },
    async register(body) {
      this.user = (await api.auth.register(body)).data
      await useCartStore().load()
    },
    async logout() {
      await api.auth.logout().catch(() => {})
      this.user = null
      useCartStore().reset()
    },
  },
})
