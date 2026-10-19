<script setup>
import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeading from '@/components/layout/PageHeading.vue'
import ProductGrid from '@/components/catalog/ProductGrid.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { api } from '@/services/api'
import { ORIGINAL_SEARCHES } from '@/services/home'
import { formatNumber } from '@/utils/format'

// FE-25: full results page; the header field gives instant suggestions.
const route = useRoute()
const router = useRouter()
const q = ref(route.query.q || '')
const results = ref([])
const loading = ref(false)
let ctrl

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
watch(() => route.query.q, (v) => { q.value = v || ''; run(v) }, { immediate: true })
const submit = () => router.replace({ query: { q: q.value.trim() || undefined } })
</script>

<template>
  <main id="main">
    <PageHeading title="ابحث عن عطرك" lead="باسم عطر مداد، أو باسم العطر الأصلي الذي تعرفه.">
      <form role="search" class="mt-3 flex max-w-xl items-center gap-2 rounded-full bg-paper py-1 pe-1 ps-4 focus-within:ring-2 focus-within:ring-ink" @submit.prevent="submit">
        <AppIcon name="search" class="text-sand" />
        <label for="q-big" class="sr-only">كلمة البحث</label>
        <input id="q-big" v-model="q" type="search" class="h-12 min-w-0 flex-1 bg-transparent text-lg text-ink outline-none placeholder:text-muted" placeholder="مثلاً: بلو دي شانيل، عود، فانيليا" />
        <button class="btn-pill min-h-11 px-5">بحث</button>
      </form>
    </PageHeading>

    <div class="container-midad flex flex-col gap-6 pb-20 pt-4">
      <p v-if="route.query.q && !loading" class="m-0 text-muted" aria-live="polite">
        {{ results.length ? `${formatNumber(results.length)} نتيجة لـ «${route.query.q}»` : `لا توجد نتائج لـ «${route.query.q}».` }}
      </p>
      <ProductGrid v-if="results.length || loading" :products="results" :loading="loading" />
      <div v-if="!loading && !results.length" class="flex flex-col gap-3">
        <p class="m-0 font-display text-2xl">جرّب واحداً من الأكثر بحثاً</p>
        <ul class="m-0 flex list-none flex-wrap gap-2 p-0">
          <li v-for="o in ORIGINAL_SEARCHES" :key="o"><RouterLink :to="{ name: 'search', query: { q: o } }" class="block rounded-full border border-ink/25 px-4 py-1.5 text-ink no-underline hover:border-ink">{{ o }}</RouterLink></li>
        </ul>
        <p class="m-0 mt-2 text-muted">أو <RouterLink :to="{ name: 'finder' }" class="text-ink underline">اسأل خبير العطور</RouterLink> يرشّح لك من ثلاثة أسئلة.</p>
      </div>
    </div>
  </main>
</template>
