<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import MTopBar from './MTopBar.vue'
import MProductCard from './MProductCard.vue'
import { api } from '@/services/api'
import { ORIGINAL_SEARCHES } from '@/services/home'
import { formatNumber } from '@/utils/format'

// Search on the phone: the field in focus, the originals people ask for as
// chips, and the results as the same two-column cards.
const route = useRoute()
const router = useRouter()
const q = ref(route.query.q || '')
const results = ref([])
const loading = ref(false)
const field = ref(null)
let ctrl
let timer

async function run(term) {
  ctrl?.abort()
  if (!term?.trim()) {
    results.value = []
    return
  }
  ctrl = new AbortController()
  loading.value = true
  try {
    results.value = (await api.search(term.trim(), 24, ctrl.signal)).data
  } catch (e) {
    if (e.name !== 'AbortError') results.value = []
  } finally {
    loading.value = false
  }
}
watch(() => route.query.q, (v) => { if (route.name !== 'search') return; q.value = v || ''; run(v) }, { immediate: true })
// typing updates the address after a short pause, so back keeps the search
watch(q, (v) => {
  clearTimeout(timer)
  timer = setTimeout(() => router.replace({ query: { q: v.trim() || undefined } }), 250)
})
onMounted(() => { if (!q.value) field.value?.focus() })
</script>

<template>
  <main id="main" class="m">
    <MTopBar title="ابحث عن عطرك" />
    <form role="search" class="search" @submit.prevent>
      <AppIcon name="search" :size="18" class="text-muted" />
      <label for="m-q" class="sr-only">باسم عطر مداد، أو العطر الأصلي، أو نوتة</label>
      <input id="m-q" ref="field" v-model="q" type="search" autocomplete="off" placeholder="بلو دي شانيل، عود، فانيليا…" />
      <button v-if="q" type="button" class="clear" aria-label="مسح البحث" @click="q = ''"><AppIcon name="close" :size="14" /></button>
    </form>

    <div v-if="!q" class="pop">
      <p class="m-0 text-[0.78rem] text-muted">الأكثر بحثاً</p>
      <div class="chips">
        <button v-for="o in ORIGINAL_SEARCHES" :key="o" class="chip" @click="q = o">{{ o }}</button>
      </div>
    </div>

    <p v-else class="count" aria-live="polite">{{ loading ? 'جارٍ البحث…' : `${formatNumber(results.length)} نتيجة` }}</p>
    <ul v-if="results.length" class="grid2 m-0 list-none p-0">
      <li v-for="p in results" :key="p.slug"><MProductCard :product="p" /></li>
    </ul>
    <p v-else-if="q && !loading" class="empty">لا يوجد عطر بهذا الاسم. جرّب اسم العطر الأصلي، أو <RouterLink :to="{ name: 'finder' }" class="underline">اسأل خبير العطور</RouterLink>.</p>
  </main>
</template>

<style scoped>
.m { padding-bottom: 20px; font-size: 0.875rem; }
.search { display: flex; align-items: center; gap: 8px; height: 46px; margin: 0 16px 10px; padding: 0 12px; border-radius: 14px; background: var(--color-paper); }
.search:focus-within { box-shadow: inset 0 0 0 2px var(--color-ink); }
.search input { flex: 1; min-width: 0; border: 0; background: none; outline: none; font-size: 0.9375rem; }
.clear { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 999px; background: var(--color-linen); }
.pop { display: flex; flex-direction: column; gap: 8px; padding: 0 16px; }
.chips { display: flex; gap: 6px; flex-wrap: wrap; }
.chip { height: 34px; padding: 0 14px; border-radius: 999px; background: var(--color-paper); font-size: 0.8125rem; }
.count { margin: 0 16px 8px; font-size: 0.78rem; color: var(--color-muted); }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 0 16px; }
.empty { padding: 30px 16px; text-align: center; color: var(--color-muted); }
</style>
