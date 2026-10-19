<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import BottleArt from '@/components/brand/BottleArt.vue'
import { admin } from '@/services/admin'
import { useAuthStore } from '@/stores/auth'
import { formatNumber, formatPrice } from '@/utils/format'

// FE-40 list
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const rows = ref([])
const meta = ref(null)
const cats = [['', 'كل الأقسام'], [1, 'رجالي'], [2, 'نسائي'], [3, 'شرقي ونيش']]
const f = ref({ q: route.query.q || '', category_id: '', status: '' })
let t
async function load() {
  const res = await admin.products({ ...f.value, per_page: 60 })
  rows.value = res.data
  meta.value = res.meta
}
onMounted(load)
watch(f, () => { clearTimeout(t); t = setTimeout(load, 250) }, { deep: true })
</script>

<template>
  <AdminHeader title="العطور" :lead="meta ? `${formatNumber(meta.total)} عطراً` : ''">
    <RouterLink v-if="auth.user?.role === 'admin'" :to="{ name: 'admin-product-new' }" class="rounded-full bg-ink px-5 py-2.5 text-ivory no-underline">إضافة عطر</RouterLink>
  </AdminHeader>
  <div class="mb-6 flex flex-wrap gap-3">
    <label for="pq" class="sr-only">بحث</label>
    <input id="pq" v-model="f.q" placeholder="الاسم، الاسم اللاتيني، أو الأصلي" class="h-10 w-72 rounded-xl border border-sand bg-paper px-3" />
    <select v-model="f.category_id" class="h-10 rounded-xl border border-sand bg-paper px-2" aria-label="القسم"><option v-for="[v, l] in cats" :key="v" :value="v">{{ l }}</option></select>
    <select v-model="f.status" class="h-10 rounded-xl border border-sand bg-paper px-2" aria-label="الحالة"><option value="">كل الحالات</option><option value="active">ظاهر</option><option value="inactive">مخفي</option><option value="low">مخزون منخفض</option></select>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[860px] border-collapse text-[0.9375rem]">
      <thead><tr class="border-b border-ink text-[0.8125rem] text-muted"><th class="w-14" /><th class="py-2 text-start font-normal">العطر</th><th class="text-start font-normal">القسم</th><th class="text-start font-normal">الأحجام والمخزون المتاح</th><th class="text-start font-normal">المبيع</th><th class="text-start font-normal">الحالة</th></tr></thead>
      <tbody>
        <tr v-for="p in rows" :key="p.id" class="cursor-pointer border-b border-sand hover:bg-paper" @click="router.push({ name: 'admin-product', params: { id: p.id } })">
          <td class="py-2"><img v-if="p.image" :src="p.image" alt="" class="h-12 w-10 object-cover" /><BottleArt v-else :ml="50" :width="26" :label="false" class="mx-auto" /></td>
          <td><RouterLink :to="{ name: 'admin-product', params: { id: p.id } }" class="font-display text-lg font-bold text-ink no-underline" @click.stop>{{ p.name }}</RouterLink><span class="block text-[0.8125rem] text-muted">مستوحى من {{ p.inspiredBy }}</span></td>
          <td>{{ p.category }}</td>
          <td>
            <span v-for="v in p.variants.filter((x) => x.active)" :key="v.id" class="me-2 inline-block text-[0.8125rem]" :class="v.low && 'font-medium text-danger'">
              {{ formatNumber(v.ml) }} مل: {{ formatNumber(v.stock - v.reserved) }}<span class="text-muted"> · {{ formatPrice(v.price) }}</span>
            </span>
          </td>
          <td class="tabular">{{ formatNumber(p.soldCount) }}</td>
          <td><span class="rounded-full border px-2.5 py-0.5 text-[0.8125rem]" :class="p.active ? 'border-ink/30' : 'border-dashed border-muted text-muted'">{{ p.active ? 'ظاهر' : 'مخفي' }}</span></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
