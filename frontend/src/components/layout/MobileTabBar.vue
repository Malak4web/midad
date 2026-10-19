<script setup>
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import BottomSheet from '@/components/ui/BottomSheet.vue'
import SiteSearch from './SiteSearch.vue'
import { useCartStore } from '@/stores/cart'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { formatNumber } from '@/utils/format'

// The phone's app shell: five tabs within thumb reach, and the sheets they open.
// Desktop keeps its header navigation; this bar exists below lg only.
const route = useRoute()
const cart = useCartStore()
const ui = useUiStore()
const auth = useAuthStore()
const number = import.meta.env.VITE_WHATSAPP_NUMBER || ''

const SHOP = [
  { to: { name: 'category', params: { slug: 'men' } }, label: 'رجالي', note: 'عنبر، خشب، وهيل' },
  { to: { name: 'category', params: { slug: 'women' } }, label: 'نسائي', note: 'زهور، فواكه، وحلاوة' },
  { to: { name: 'category', params: { slug: 'oriental' } }, label: 'شرقي ونيش', note: 'عود، بخور، وتوابل' },
]
const MORE = [
  { to: { name: 'discovery' }, label: 'مجموعة الاكتشاف', icon: 'drop' },
  { to: { name: 'finder' }, label: 'اسأل خبير العطور', icon: 'help' },
  { to: { name: 'gifts' }, label: 'الهدايا', icon: 'gift' },
  { to: { name: 'track' }, label: 'تتبّع طلبك', icon: 'package' },
]
const shopOpen = computed({ get: () => ui.sheet === 'shop', set: (v) => (ui.sheet = v ? 'shop' : null) })
const searchOpen = computed({ get: () => ui.sheet === 'search', set: (v) => (ui.sheet = v ? 'search' : null) })
watch(() => route.fullPath, () => (ui.sheet = null))

const ORDER = ['home', 'shop', 'search', 'cart', 'account']
const tab = computed(() => {
  if (ui.sheet) return ui.sheet
  if (ui.cartOpen) return 'cart'
  const n = route.name
  if (n === 'home') return 'home'
  if (['account', 'login', 'register', 'order'].includes(n)) return 'account'
  if (n === 'cart') return 'cart'
  if (n === 'search') return 'search'
  return 'shop'
})
</script>

<template>
  <nav class="tabbar lg:hidden" :style="{ '--i': ORDER.indexOf(tab) }" aria-label="التنقل الرئيسي">
    <!-- the active tab rises into a drop of gold, like ink lifting off the nib -->
    <span class="drop" aria-hidden="true"><i /></span>
    <ul class="m-0 grid list-none grid-cols-5 p-0">
      <li>
        <RouterLink to="/" class="tab" :class="tab === 'home' && 'is-on'" :aria-current="tab === 'home' ? 'page' : undefined"><AppIcon name="home" :size="22" /><span>الرئيسية</span></RouterLink>
      </li>
      <li>
        <button class="tab" :class="tab === 'shop' && 'is-on'" :aria-expanded="shopOpen" @click="shopOpen = true"><AppIcon name="shop" :size="22" /><span>تسوّق</span></button>
      </li>
      <li>
        <button class="tab" :class="tab === 'search' && 'is-on'" :aria-expanded="searchOpen" @click="searchOpen = true"><AppIcon name="search" :size="22" /><span>بحث</span></button>
      </li>
      <li>
        <button class="tab" :class="tab === 'cart' && 'is-on'" :aria-label="`السلة، ${formatNumber(cart.count)} قطعة`" @click="ui.cartOpen = true">
          <span class="ic">
            <AppIcon name="bag" :size="22" />
            <span v-if="cart.count" class="badge" aria-hidden="true">{{ formatNumber(cart.count) }}</span>
          </span>
          <span aria-hidden="true">السلة</span>
        </button>
      </li>
      <li>
        <RouterLink :to="{ name: auth.signedIn ? 'account' : 'login' }" class="tab" :class="tab === 'account' && 'is-on'"><AppIcon name="user" :size="22" /><span>حسابي</span></RouterLink>
      </li>
    </ul>
  </nav>

  <BottomSheet v-model="shopOpen" title="تسوّق">
    <ul class="m-0 flex list-none flex-col gap-2 p-0">
      <li v-for="c in SHOP" :key="c.label">
        <RouterLink :to="c.to" class="flex items-center justify-between gap-3 rounded-2xl bg-paper px-4 py-3 text-ink no-underline active:bg-sand/40">
          <span class="flex flex-col"><span class="font-display text-[1.125rem] font-bold leading-[1.3]">{{ c.label }}</span><span class="text-[0.75rem] text-muted">{{ c.note }}</span></span>
          <AppIcon name="chevron-left" :size="20" class="text-muted" />
        </RouterLink>
      </li>
    </ul>
    <ul class="m-0 mt-4 grid list-none grid-cols-2 gap-2 p-0">
      <li v-for="m in MORE" :key="m.label">
        <RouterLink :to="m.to" class="flex h-full min-h-[72px] flex-col justify-between gap-2 rounded-2xl bg-ink px-3.5 py-3 text-ivory no-underline active:bg-smoke">
          <AppIcon :name="m.icon" :size="22" class="text-gold-light" />
          <span class="text-[0.8125rem]">{{ m.label }}</span>
        </RouterLink>
      </li>
    </ul>
    <a :href="number ? `https://wa.me/${number}` : '/help/contact'" :target="number ? '_blank' : undefined" rel="noopener" class="mt-3 flex h-12 items-center justify-center gap-2 rounded-2xl bg-paper px-4 text-[0.875rem] text-ink no-underline active:bg-sand/40">
      <AppIcon name="whatsapp" :size="20" /> استشارة على واتساب
    </a>
  </BottomSheet>

  <BottomSheet v-model="searchOpen" title="ابحث عن عطرك" full>
    <p class="m-0 mb-3 text-[0.875rem] text-muted">باسم عطر مداد، أو باسم العطر الأصلي، أو بنوتة تحبها.</p>
    <SiteSearch v-if="searchOpen" @done="searchOpen = false" />
  </BottomSheet>
</template>

<style scoped>
/* an ink bar; the active tab's icon lifts into a gold drop that glides between tabs */
.tabbar { position: fixed; inset: auto 0 0; z-index: 40; height: calc(66px + env(safe-area-inset-bottom, 0px)); padding-bottom: env(safe-area-inset-bottom, 0px); border-radius: 24px 24px 0 0; background: var(--color-ink); box-shadow: 0 -12px 30px -18px rgba(14, 13, 12, 0.6); }
.tabbar ul { height: 66px; }
.drop { position: absolute; top: -24px; inset-inline-start: calc(var(--i, 0) * 20%); width: 20%; display: grid; place-items: center; pointer-events: none; transition: inset-inline-start 0.45s cubic-bezier(0.3, 1.3, 0.5, 1); }
.drop i { width: 52px; height: 52px; border-radius: 50%; background: var(--color-gold-light); box-shadow: 0 0 0 6px var(--color-ivory), 0 10px 18px -8px rgba(14, 13, 12, 0.6); }
.tab { position: relative; display: flex; height: 66px; width: 100%; flex-direction: column; align-items: center; justify-content: flex-end; gap: 3px; padding-bottom: 10px; border: 0; background: none; color: rgba(214, 183, 126, 0.6); font-size: 0.6875rem; text-decoration: none; }
.tab > :first-child { transition: transform 0.45s cubic-bezier(0.3, 1.3, 0.5, 1), color 0.3s; }
.tab.is-on { color: var(--color-gold-light); font-weight: 600; }
.tab.is-on > :first-child { transform: translateY(-25px); color: var(--color-ink); }
.tab:focus-visible { outline: 2px solid var(--color-gold-light); outline-offset: -4px; border-radius: 14px; }
.ic { position: relative; display: inline-flex; }
.badge { position: absolute; top: -6px; inset-inline-end: -9px; display: grid; min-width: 17px; height: 17px; place-items: center; padding: 0 4px; border-radius: 999px; background: var(--color-gold-light); color: var(--color-ink); font-size: 0.6875rem; line-height: 1; box-shadow: 0 0 0 2px var(--color-ink); }
.tab.is-on .badge { background: var(--color-ink); color: var(--color-gold-light); box-shadow: 0 0 0 2px var(--color-gold-light); }
@media (prefers-reduced-motion: reduce) { .drop, .tab > :first-child { transition: none; } }
</style>
