<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MidadLogo from '@/components/brand/MidadLogo.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import SiteSearch from './SiteSearch.vue'
import { useCartStore } from '@/stores/cart'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { formatNumber } from '@/utils/format'

// Desktop: logo, sections, search, account and cart.
// Phone: an app bar. Back on inner pages, the logo in the middle; everything else
// lives in the bottom tab bar. It slides away while reading down and returns on
// the way back up.
const cart = useCartStore()
const ui = useUiStore()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const searchOpen = ref(false)
const nav = [
  { to: { name: 'category', params: { slug: 'men' } }, label: 'رجالي' },
  { to: { name: 'category', params: { slug: 'women' } }, label: 'نسائي' },
  { to: { name: 'category', params: { slug: 'oriental' } }, label: 'شرقي ونيش' },
  { to: { name: 'discovery' }, label: 'مجموعة الاكتشاف' },
  { to: { name: 'gifts' }, label: 'الهدايا' },
  { to: { name: 'finder' }, label: 'اسأل خبير العطور' },
]
watch(() => route.fullPath, () => {
  searchOpen.value = false
  hidden.value = false
})

const inner = computed(() => route.name !== 'home')
function back() {
  // a deep link has no in-app history: go home instead of leaving the site
  if (history.state?.back) router.back()
  else router.push('/')
}

const hidden = ref(false)
let lastY = 0
let ticking = false
function onScroll() {
  if (ticking) return
  ticking = true
  requestAnimationFrame(() => {
    const y = scrollY
    const phone = innerWidth < 1024
    if (!phone || y < 120) hidden.value = false
    else if (y > lastY + 6) hidden.value = true
    else if (y < lastY - 6) hidden.value = false
    lastY = y
    ticking = false
  })
}
onMounted(() => addEventListener('scroll', onScroll, { passive: true }))
onBeforeUnmount(() => removeEventListener('scroll', onScroll))
</script>

<template>
  <header class="site-header sticky top-0 z-30 bg-ivory/90 backdrop-blur-md" :class="hidden && 'is-hidden'">
    <a href="#main" class="sr-only-focusable absolute start-4 top-2 z-50 rounded-full bg-ink px-4 py-1 text-ivory">تخطَّ إلى المحتوى</a>
    <div class="container-midad relative flex h-[60px] items-center gap-5 max-lg:justify-center lg:h-[76px]">
      <button v-if="inner" class="absolute start-3 grid h-11 w-11 place-items-center rounded-full bg-paper lg:hidden" aria-label="رجوع" @click="back">
        <AppIcon name="arrow-right" :size="22" />
      </button>
      <RouterLink to="/" class="text-ink no-underline" aria-label="مداد MIDAD، الصفحة الرئيسية">
        <MidadLogo :height="60" class="logo" />
      </RouterLink>
      <nav class="hidden flex-1 lg:block" aria-label="الأقسام">
        <ul class="pills m-0 inline-flex list-none items-center gap-1 rounded-full bg-paper p-1.5 text-[0.875rem] font-semibold">
          <li v-for="n in nav" :key="n.label">
            <RouterLink :to="n.to" class="pill block rounded-full px-3.5 py-2 text-ink no-underline transition-colors hover:bg-linen xl:px-4">{{ n.label }}</RouterLink>
          </li>
        </ul>
      </nav>
      <div class="ms-auto hidden items-center gap-2 lg:flex">
        <div class="hidden w-60 xl:block"><SiteSearch /></div>
        <button class="grid h-11 w-11 place-items-center rounded-full bg-paper xl:hidden" aria-label="بحث" :aria-expanded="searchOpen" @click="searchOpen = !searchOpen"><AppIcon name="search" :size="22" /></button>
        <RouterLink :to="{ name: auth.signedIn ? 'account' : 'login' }" class="grid h-11 w-11 place-items-center rounded-full bg-paper text-ink" :aria-label="auth.signedIn ? 'حسابي' : 'تسجيل الدخول'"><AppIcon name="user" :size="22" /></RouterLink>
        <button class="inline-flex h-11 items-center gap-2 rounded-full bg-ink px-4 font-medium text-gold-light" :aria-label="`السلة، ${formatNumber(cart.count)} قطعة`" @click="ui.cartOpen = true">
          <AppIcon name="bag" :size="20" />
          <span class="text-[0.875rem] tabular" aria-hidden="true">{{ formatNumber(cart.count) }}</span>
        </button>
      </div>
    </div>
    <div v-if="searchOpen" class="container-midad hidden pb-3 lg:block xl:hidden"><SiteSearch @done="searchOpen = false" /></div>
  </header>
</template>

<style scoped>
.site-header { transition: transform 0.32s cubic-bezier(0.22, 0.8, 0.24, 1); }
.site-header.is-hidden { transform: translateY(-100%); }
@media (max-width: 1023px) { .logo :deep(svg) { height: 46px; width: auto; } }
.pills .router-link-active { background: var(--color-ink); color: var(--color-gold-light); }
</style>
