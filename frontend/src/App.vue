<script setup>
import { computed, defineAsyncComponent, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import SiteHeader from '@/components/layout/SiteHeader.vue'
import SiteFooter from '@/components/layout/SiteFooter.vue'
import CartDrawer from '@/components/layout/CartDrawer.vue'
import ToastHost from '@/components/layout/ToastHost.vue'
import AnnouncementBar from '@/components/layout/AnnouncementBar.vue'
import WhatsAppButton from '@/components/layout/WhatsAppButton.vue'
import MobileTabBar from '@/components/layout/MobileTabBar.vue'
import MidadLogo from '@/components/brand/MidadLogo.vue'
import MTopBar from '@/mobile/MTopBar.vue'
import { usePhone } from '@/composables/usePhone'
import { ANNOUNCEMENTS } from '@/services/home'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'

const route = useRoute()
const auth = useAuthStore()
const cart = useCartStore()
const phone = usePhone()
// the phone is its own app: these screens are designed for it, not shrunk from the desktop
const MOBILE = {
  home: defineAsyncComponent(() => import('@/mobile/MHome.vue')),
  category: defineAsyncComponent(() => import('@/mobile/MCategory.vue')),
  product: defineAsyncComponent(() => import('@/mobile/MProduct.vue')),
  cart: defineAsyncComponent(() => import('@/mobile/MCart.vue')),
  search: defineAsyncComponent(() => import('@/mobile/MSearch.vue')),
}
const own = computed(() => phone.value && !!MOBILE[route.name])
// focused flows hide the phone tab bar: checkout and payment own the bottom of the screen
const tabs = computed(() => !['checkout', 'pay-sandbox'].includes(route.name))
watch(() => route.meta.title, (t) => { document.title = t ? `${t} | مداد` : 'مداد | عطرٌ يُكتَب، فيبقى' })
onMounted(() => {
  if (!auth.checked) auth.fetch()
  cart.load().catch(() => {}) // header badge; the store stays empty if the API is down
})
</script>

<template>
  <RouterView v-if="route.meta.shell === 'admin'" />
  <template v-else>
    <div :class="tabs && 'has-tabs'">
      <template v-if="!phone">
        <AnnouncementBar :items="ANNOUNCEMENTS" />
        <SiteHeader />
      </template>
      <!-- phone screens without their own design get the app's bar: back and the mark -->
      <MTopBar v-else-if="!own" class="shellbar">
        <template #title><RouterLink to="/" aria-label="مداد، الصفحة الرئيسية" class="inline-flex min-h-10 min-w-10 items-center justify-center text-ink"><MidadLogo :height="34" label="مداد" /></RouterLink></template>
      </MTopBar>
      <!-- pages arrive with a short rise, like moving between screens of an app -->
      <RouterView v-slot="{ Component, route: r }">
        <Transition name="page" :css="true">
          <component :is="phone && MOBILE[r.name] ? MOBILE[r.name] : Component" :key="r.path" />
        </Transition>
      </RouterView>
      <SiteFooter v-if="!phone && !route.meta.bareFooter && route.name !== 'home'" />
    </div>
    <CartDrawer />
    <ToastHost :above-tabs="tabs" />
    <WhatsAppButton class="max-lg:hidden" />
    <MobileTabBar v-if="tabs" />
  </template>
</template>

<style>
.page-enter-active { animation: page-in 0.32s cubic-bezier(0.22, 0.8, 0.24, 1); }
.page-leave-active { display: none; }
@keyframes page-in { from { opacity: 0; transform: translateY(10px); } }
/* room for the phone tab bar under the last content */
@media (max-width: 1023px) {
  .has-tabs { padding-bottom: calc(66px + env(safe-area-inset-bottom, 0px)); }
}
</style>
