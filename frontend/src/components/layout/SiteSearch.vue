<script setup>
import { ref, useId, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import ProductThumb from '@/components/brand/ProductThumb.vue'
import { api } from '@/services/api'

const emit = defineEmits(['done'])
// soft: a cream pill for use inside a white tile; big: the hero's taller field
const props = defineProps({ soft: { type: Boolean, default: false }, big: { type: Boolean, default: false }, placeholder: { type: String, default: 'ابحث باسم العطر أو الأصلي' } })
const router = useRouter()
const id = useId()
const q = ref('')
const open = ref(false)
const active = ref(-1)
const results = ref([])
const searched = ref('')
let timer
let ctrl
// debounced live suggestions from /api/search; a newer keystroke cancels the older request
watch(q, (term) => {
  clearTimeout(timer)
  ctrl?.abort()
  if (!term.trim()) {
    results.value = []
    searched.value = ''
    return
  }
  timer = setTimeout(async () => {
    ctrl = new AbortController()
    try {
      results.value = (await api.search(term.trim(), 6, ctrl.signal)).data.map((p) => ({ p }))
      searched.value = term
    } catch (e) {
      if (e.name !== 'AbortError') results.value = []
    }
  }, 180)
})

function go(p) {
  router.push({ name: 'product', params: { slug: p.slug } })
  reset()
}
function submit() {
  if (active.value >= 0 && results.value[active.value]) return go(results.value[active.value].p)
  if (!q.value.trim()) return
  router.push({ name: 'search', query: { q: q.value.trim() } })
  reset()
}
function reset() {
  open.value = false
  active.value = -1
  q.value = ''
  emit('done')
}
function closeSoon() {
  setTimeout(() => (open.value = false), 150)
}
function key(e) {
  if (!results.value.length) return
  if (e.key === 'ArrowDown') { e.preventDefault(); active.value = (active.value + 1) % results.value.length }
  if (e.key === 'ArrowUp') { e.preventDefault(); active.value = (active.value - 1 + results.value.length) % results.value.length }
  if (e.key === 'Escape') { open.value = false; active.value = -1 }
}
</script>

<template>
  <form role="search" class="relative" @submit.prevent="submit">
    <label :for="`${id}-q`" class="sr-only">ابحث باسم العطر أو باسم العطر الأصلي</label>
    <div class="flex items-center gap-2 rounded-full border px-4 transition-colors focus-within:border-ink" :class="props.soft ? 'border-transparent bg-ivory' : 'border-sand bg-paper'">
      <AppIcon name="search" :size="18" class="shrink-0 text-muted" />
      <input
        :id="`${id}-q`"
        v-model="q"
        type="search"
        autocomplete="off"
        :placeholder="props.placeholder"
        class="w-full min-w-0 bg-transparent text-base outline-none placeholder:text-muted"
        :class="props.big ? 'h-14' : 'h-12 lg:h-11 lg:text-[0.9375rem]'"
        role="combobox"
        :aria-expanded="open && results.length > 0"
        :aria-controls="`${id}-list`"
        :aria-activedescendant="active >= 0 ? `${id}-o${active}` : undefined"
        @focus="open = true"
        @input="open = true; active = -1"
        @keydown="key"
        @blur="closeSoon"
      />
    </div>
    <ul
      v-show="open && q && results.length"
      :id="`${id}-list`"
      role="listbox"
      class="absolute inset-x-0 top-full z-40 m-0 mt-2 list-none rounded-[22px] bg-paper p-2 shadow-[0_18px_40px_-16px_rgba(28,20,12,.35)]"
    >
      <li v-for="(r, i) in results" :id="`${id}-o${i}`" :key="r.p.slug" role="option" :aria-selected="i === active">
        <button type="button" class="flex w-full items-center gap-3 rounded-2xl px-2 py-2 text-start hover:bg-ivory" :class="i === active && 'bg-ivory'" @mousedown.prevent="go(r.p)">
          <ProductThumb :size="40" />
          <span class="min-w-0 flex-1">
            <span class="block font-display text-lg font-bold leading-tight">{{ r.p.name }}</span>
            <span class="block truncate text-[0.8125rem] text-muted">مستوحى من {{ r.p.inspiredBy }} · {{ r.p.signature }}</span>
          </span>
        </button>
      </li>
    </ul>
    <p v-show="open && searched && searched === q && !results.length" class="absolute inset-x-0 top-full z-40 m-0 mt-2 rounded-[22px] bg-paper px-4 py-3 text-sm text-muted shadow-[0_18px_40px_-16px_rgba(28,20,12,.35)]">
      لا يوجد عطر بهذا الاسم. جرّب اسم العطر الأصلي، أو اسأل خبير العطور.
    </p>
  </form>
</template>
