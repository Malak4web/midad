<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeading from '@/components/layout/PageHeading.vue'
import ProductGrid from '@/components/catalog/ProductGrid.vue'
import { api } from '@/services/api'
import { formatNumber } from '@/utils/format'

// FE-23 + INT-05: filters live in the URL so a filtered view can be shared or refreshed.
const route = useRoute()
const router = useRouter()
const products = ref([])
const meta = ref(null)
const notes = ref([])
const loading = ref(false)
const error = ref('')
let ctrl

const SORTS = [
  ['default', 'ترتيب مداد'],
  ['best', 'الأكثر مبيعاً'],
  ['new', 'الأحدث'],
  ['price_asc', 'السعر: من الأقل'],
  ['price_desc', 'السعر: من الأعلى'],
]
const filters = computed(() => ({
  note: route.query.note || '',
  size: route.query.size || '',
  sort: route.query.sort || 'default',
}))
const category = computed(() => meta.value?.category)
const title = computed(() => category.value?.name || route.meta.title)

function setFilter(key, value) {
  const q = { ...route.query, [key]: value || undefined }
  delete q.page
  if (key === 'sort' && value === 'default') delete q.sort
  router.replace({ query: q })
}

async function load(page = 1) {
  ctrl?.abort()
  ctrl = new AbortController()
  loading.value = true
  error.value = ''
  try {
    const res = await api.category(route.params.slug, { ...filters.value, sort: filters.value.sort === 'default' ? undefined : filters.value.sort, page, per_page: 12 }, ctrl.signal)
    products.value = page === 1 ? res.data : [...products.value, ...res.data]
    meta.value = res.meta
  } catch (e) {
    if (e.name === 'AbortError') return
    error.value = e.status === 404 ? 'هذا القسم غير موجود.' : e.message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  notes.value = (await api.notes().catch(() => ({ data: [] }))).data.slice(0, 14)
})
watch(() => [route.params.slug, route.query], () => route.name === 'category' && load(), { immediate: true, deep: true })
</script>

<template>
  <main id="main">
    <PageHeading :title="title" :lead="category ? `${formatNumber(meta.total)} عطراً. اختر بالنوتة التي تحبها، أو بالحجم المناسب لك.` : ''">
      <nav class="rail mt-2 flex gap-2 lg:flex-wrap" aria-label="الأقسام">
        <RouterLink v-for="c in [['men', 'رجالي'], ['women', 'نسائي'], ['oriental', 'شرقي ونيش']]" :key="c[0]" :to="{ name: 'category', params: { slug: c[0] } }" class="shrink-0 rounded-full px-4 py-2 text-[0.9375rem] text-ivory no-underline shadow-[inset_0_0_0_1px_rgba(216,202,176,.4)]" active-class="!bg-gold-light !text-ink !shadow-none">{{ c[1] }}</RouterLink>
      </nav>
    </PageHeading>

    <div class="container-midad flex flex-col gap-8 pb-20 pt-4">
      <div class="flex flex-col gap-4 rounded-[28px] bg-paper p-4 md:p-5">
        <div class="rail flex items-center gap-2 lg:flex-wrap" role="group" aria-label="النوتة">
          <span class="me-2 shrink-0 text-[0.875rem] text-muted">النوتة</span>
          <button class="chip" :class="!filters.note && 'is-on'" @click="setFilter('note', '')">الكل</button>
          <button v-for="n in notes" :key="n.slug" class="chip" :class="filters.note === n.slug && 'is-on'" :aria-pressed="filters.note === n.slug" @click="setFilter('note', filters.note === n.slug ? '' : n.slug)">{{ n.name }}</button>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="rail flex items-center gap-2 max-lg:w-full lg:flex-wrap" role="group" aria-label="الحجم">
            <span class="me-2 shrink-0 text-[0.875rem] text-muted">الحجم</span>
            <button v-for="s in ['', '10', '30', '50', '100']" :key="s" class="chip" :class="filters.size === s && 'is-on'" :aria-pressed="filters.size === s" @click="setFilter('size', s)">{{ s ? `${formatNumber(+s)} مل` : 'الكل' }}</button>
          </div>
          <label class="flex items-center gap-2 text-[0.875rem]">
            ترتيب حسب
            <select class="h-11 rounded-xl border border-sand bg-paper px-3 text-base lg:h-10 lg:text-[0.875rem]" :value="filters.sort" @change="setFilter('sort', $event.target.value)">
              <option v-for="[v, l] in SORTS" :key="v" :value="v">{{ l }}</option>
            </select>
          </label>
        </div>
      </div>

      <p v-if="error" class="m-0 text-danger" role="alert">{{ error }}</p>
      <div v-else-if="!loading && !products.length" class="flex flex-col items-start gap-3 py-10">
        <p class="m-0 font-display text-3xl">لا توجد عطور بهذه الاختيارات</p>
        <p class="m-0 text-muted">جرّب نوتة أخرى أو حجماً آخر، أو امسح الفلاتر.</p>
        <button class="rounded-full border border-ink px-5 py-2.5" @click="router.replace({ query: {} })">مسح الفلاتر</button>
      </div>
      <ProductGrid v-else :products="products" :loading="loading" />

      <div v-if="meta && meta.current_page < meta.last_page" class="flex justify-center">
        <button class="rounded-full border border-ink px-8 py-3 disabled:opacity-50" :disabled="loading" @click="load(meta.current_page + 1)">
          {{ loading ? 'جارٍ التحميل…' : `عرض المزيد (${formatNumber(meta.total - products.length)})` }}
        </button>
      </div>
    </div>
  </main>
</template>

<style scoped>
.chip {
  border-radius: 9999px;
  padding: 6px 14px;
  font-size: 0.875rem;
  min-height: 40px;
  background: var(--color-ivory);
  transition: background 0.2s;
}
.chip:hover { background: var(--color-linen); }
.chip.is-on { background: var(--color-ink); color: var(--color-gold-light); }
</style>
