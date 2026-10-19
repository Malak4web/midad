<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MidadLogo from '@/components/brand/MidadLogo.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import ToastHost from '@/components/layout/ToastHost.vue'
import { useAuthStore } from '@/stores/auth'

// FE-38: the back office keeps the Midad frame (ink rail, ivory desk) and drops the shop chrome.
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const open = ref(false)
const NAV = [
  ['admin', 'الرئيسية', false],
  ['admin-orders', 'الطلبات', false],
  ['admin-products', 'العطور', false],
  ['admin-stock', 'المخزون', false],
  ['admin-spotlights', 'الأقسام والهويات', true],
  ['admin-coupons', 'الكوبونات', true],
  ['admin-customers', 'العملاء', true],
  ['admin-reports', 'التقارير', true],
  ['admin-shipping', 'الشحن', true],
  ['admin-settings', 'الإعدادات', true],
]
const nav = computed(() => NAV.filter(([, , adminOnly]) => !adminOnly || auth.user?.role === 'admin'))
watch(() => route.fullPath, () => (open.value = false))
async function logout() {
  await auth.logout()
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-ivory lg:grid lg:grid-cols-[232px_1fr]">
    <a href="#admin-main" class="rounded-full sr-only-focusable absolute start-4 top-2 z-50 bg-ink px-3 py-1 text-ivory">تخطَّ إلى المحتوى</a>
    <aside class="bg-ink text-sand lg:sticky lg:top-0 lg:h-screen" aria-label="لوحة التحكم">
      <div class="flex items-center justify-between px-5 py-4 lg:py-6">
        <RouterLink :to="{ name: 'admin' }" class="flex items-center gap-3 text-gold-light no-underline">
          <MidadLogo :height="64" kasra="#D6B77E" /><span class="text-[0.8125rem] text-sand">لوحة التحكم</span>
        </RouterLink>
        <button class="p-2 lg:hidden" :aria-expanded="open" aria-controls="admin-nav" aria-label="القائمة" @click="open = !open"><AppIcon :name="open ? 'close' : 'menu'" /></button>
      </div>
      <nav id="admin-nav" class="px-3 pb-6" :class="open ? 'block' : 'hidden lg:block'">
        <ul class="m-0 flex list-none flex-col gap-0.5 p-0">
          <li v-for="[name, label] in nav" :key="name">
            <RouterLink :to="{ name }" class="rail relative block px-3 py-2.5 text-[0.9375rem] text-sand no-underline hover:text-ivory" :exact-active-class="name === 'admin' ? 'is-on' : ''" :active-class="name === 'admin' ? '' : 'is-on'">{{ label }}</RouterLink>
          </li>
        </ul>
        <div class="mt-8 border-t border-smoke px-3 pt-5 text-[0.875rem]">
          <p class="m-0 text-ivory">{{ auth.user?.name }}</p>
          <p class="m-0 mb-3 text-muted-light">{{ auth.user?.role === 'admin' ? 'مدير' : 'موظف' }}</p>
          <RouterLink to="/" class="block py-1 text-sand">فتح المتجر</RouterLink>
          <button class="py-1 text-sand underline" @click="logout">تسجيل الخروج</button>
        </div>
      </nav>
    </aside>
    <main id="admin-main" class="min-w-0 px-[clamp(16px,3vw,40px)] pb-20 pt-8">
      <RouterView />
    </main>
    <ToastHost />
  </div>
</template>

<style scoped>
/* active item: the brand spray square (18°), never a thick side border */
.rail.is-on { color: var(--color-ivory); background: rgba(242, 234, 219, 0.06); }
.rail.is-on::before { content: ''; position: absolute; inset-inline-start: -2px; top: 50%; width: 6px; height: 6px; margin-top: -3px; background: var(--color-gold-light); transform: rotate(18deg); }
</style>
