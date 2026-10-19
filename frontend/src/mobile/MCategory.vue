<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import MTopBar from './MTopBar.vue'
import MProductCard from './MProductCard.vue'
import { api } from '@/services/api'
import { lowestPrice } from '@/data/catalog'
import { SCENES } from '@/data/photos'
import { familiesOf } from '@/utils/scenes'
import { matches } from '@/utils/search'
import { formatNumber } from '@/utils/format'

// A category on the phone: search inside it, families as chips, a sort, and a
// dense two-column grid. All filtering happens on the loaded list, instantly.
const route = useRoute()
const all = ref([])
const name = ref('')
const loading = ref(true)
const error = ref('')
const q = ref('')
const fam = ref('all')
const sort = ref('default')
let ctrl

async function load() {
  ctrl?.abort()
  ctrl = new AbortController()
  loading.value = true
  error.value = ''
  try {
    const res = await api.category(route.params.slug, { per_page: 48 }, ctrl.signal)
    all.value = res.data.map((p) => ({ ...p, family: familiesOf(p)[0] }))
    name.value = res.meta?.category?.name || ''
  } catch (e) {
    if (e.name !== 'AbortError') error.value = e.status === 404 ? 'هذا القسم غير موجود.' : e.message
  } finally {
    loading.value = false
  }
}
watch(() => route.params.slug, (s) => { if (s && route.name === 'category') { q.value = ''; fam.value = 'all'; load() } }, { immediate: true })

const FAMS = computed(() => ['gourmand', 'amber', 'floral', 'fresh', 'oriental', 'woody'].filter((f) => all.value.some((p) => p.family === f)))
const price = (p) => p.fromPrice ?? lowestPrice(p)
const shown = computed(() => {
  const list = all.value.filter((p) => (fam.value === 'all' || p.family === fam.value) && matches([p.name, p.latin, p.inspiredBy, p.signature, ...(p.notes || [])], q.value))
  if (sort.value === 'price_asc') return [...list].sort((a, b) => price(a) - price(b))
  if (sort.value === 'price_desc') return [...list].sort((a, b) => price(b) - price(a))
  return list
})
const CATS = [['men', 'رجالي'], ['women', 'نسائي'], ['oriental', 'شرقي ونيش']]
</script>

<template>
  <main id="main" class="m">
    <MTopBar :title="name || route.meta.title" :sub="all.length ? `${formatNumber(all.length)} عطراً` : ''" />
    <div class="tools">
      <form role="search" class="search" @submit.prevent>
        <AppIcon name="search" :size="18" class="text-muted" />
        <label for="m-cat-q" class="sr-only">ابحث في القسم</label>
        <input id="m-cat-q" v-model="q" type="search" autocomplete="off" :placeholder="`ابحث في ${name || 'القسم'}…`" />
      </form>
      <nav class="chips" aria-label="الأقسام">
        <RouterLink v-for="[s, l] in CATS" :key="s" :to="{ name: 'category', params: { slug: s } }" class="chip" active-class="is-on">{{ l }}</RouterLink>
      </nav>
      <div class="chips" role="group" aria-label="العائلة">
        <button class="chip" :class="fam === 'all' && 'is-on'" :aria-pressed="fam === 'all'" @click="fam = 'all'">الكل</button>
        <button v-for="f in FAMS" :key="f" class="chip" :class="fam === f && 'is-on'" :aria-pressed="fam === f" @click="fam = fam === f ? 'all' : f">{{ SCENES[f].label }}</button>
      </div>
      <div class="sortbar">
        <span class="tabular">{{ formatNumber(shown.length) }} عطر</span>
        <label class="sort"><span class="sr-only">ترتيب</span>
          <select v-model="sort" aria-label="ترتيب">
            <option value="default">ترتيب مداد</option>
            <option value="price_asc">السعر: الأقل</option>
            <option value="price_desc">السعر: الأعلى</option>
          </select>
        </label>
      </div>
    </div>

    <p v-if="error" class="px-4 text-danger">{{ error }}</p>
    <ul class="grid2 m-0 list-none p-0" :aria-busy="loading">
      <li v-for="p in shown" :key="p.slug"><MProductCard :product="p" /></li>
      <template v-if="loading && !all.length"><li v-for="n in 6" :key="n" class="h-56 animate-pulse rounded-2xl bg-paper" /></template>
    </ul>
    <p v-if="!loading && !shown.length && !error" class="empty">لا يوجد عطر بهذا الوصف. جرّب كلمة أخرى، أو اعرض الكل.</p>
  </main>
</template>

<style scoped>
.m { padding-bottom: 20px; font-size: 0.875rem; }
.tools { display: flex; flex-direction: column; gap: 8px; padding: 0 16px 10px; }
.search { display: flex; align-items: center; gap: 8px; height: 42px; padding: 0 12px; border-radius: 12px; background: var(--color-paper); }
.search input { flex: 1; min-width: 0; border: 0; background: none; outline: none; font-size: 0.875rem; }
.search:focus-within { box-shadow: inset 0 0 0 2px var(--color-ink); }
.chips { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; margin-inline: -16px; padding-inline: 16px; }
.chips::-webkit-scrollbar { display: none; }
.chip { flex: none; display: inline-flex; align-items: center; height: 34px; padding: 0 14px; border-radius: 999px; background: var(--color-paper); color: var(--color-ink); font-size: 0.8125rem; text-decoration: none; white-space: nowrap; }
.chip.is-on { background: var(--color-ink); color: var(--color-gold-light); }
.sortbar { display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--color-muted); }
.sort select { height: 34px; padding: 0 10px; border: 0; border-radius: 10px; background: var(--color-paper); font-size: 0.78rem; color: var(--color-ink); }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 0 16px; }
.empty { padding: 30px 16px; text-align: center; color: var(--color-muted); }
</style>
