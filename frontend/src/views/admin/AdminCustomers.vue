<script setup>
import { onMounted, ref, watch } from 'vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import { admin } from '@/services/admin'
import { formatNumber, formatPrice } from '@/utils/format'

// FE-46
const q = ref('')
const rows = ref([])
const meta = ref(null)
const open = ref(null)
let t
const load = async () => {
  const r = await admin.customers({ q: q.value })
  rows.value = r.data
  meta.value = r.meta
}
onMounted(load)
watch(q, () => { clearTimeout(t); t = setTimeout(load, 250) })
async function show(id) {
  open.value = open.value?.id === id ? null : (await admin.customer(id)).data
}
</script>

<template>
  <AdminHeader title="العملاء" :lead="meta ? `${formatNumber(meta.total)} حساب مسجّل. الطلبات كزائر تظهر في صفحة الطلبات.` : ''" />
  <label for="cq" class="sr-only">بحث</label>
  <input id="cq" v-model="q" placeholder="الاسم، البريد، أو الموبايل" class="mb-6 h-10 w-72 rounded-xl border border-sand bg-paper px-3" />
  <div class="overflow-x-auto">
    <table class="w-full min-w-[720px] border-collapse text-[0.9375rem]">
      <thead><tr class="border-b border-ink text-[0.8125rem] text-muted"><th class="py-2 text-start font-normal">العميل</th><th class="text-start font-normal">التواصل</th><th class="text-start font-normal">الطلبات</th><th class="text-start font-normal">إجمالي الشراء</th><th class="text-start font-normal">انضم</th></tr></thead>
      <tbody>
        <template v-for="u in rows" :key="u.id">
          <!-- the whole row opens the customer; the name is the keyboard and screen-reader control -->
          <tr class="cursor-pointer border-b border-sand hover:bg-paper" @click="show(u.id)">
            <td class="py-3 font-medium"><button type="button" class="min-h-11 text-start font-medium" :aria-expanded="open?.id === u.id" @click.stop="show(u.id)">{{ u.name }}</button></td>
            <td class="text-[0.875rem]"><span dir="ltr">{{ u.phone }}</span><span class="block text-muted" dir="ltr">{{ u.email }}</span></td>
            <td class="tabular">{{ formatNumber(u.orders) }}</td>
            <td class="tabular">{{ formatPrice(u.spent) }}</td>
            <td class="text-[0.875rem] text-muted">{{ u.joinedAt }}</td>
          </tr>
          <tr v-if="open?.id === u.id" class="bg-paper">
            <td colspan="5" class="px-4 py-4">
              <p v-for="a in open.addresses" :key="a" class="m-0 text-[0.875rem] text-muted">{{ a }}</p>
              <ul class="m-0 mt-2 list-none p-0 text-[0.875rem]">
                <li v-for="o in open.orders" :key="o.number" class="flex gap-4 py-1"><RouterLink :to="{ name: 'admin-order', params: { number: o.number } }" class="font-meta text-ink" dir="ltr">{{ o.number }}</RouterLink><span>{{ o.status }}</span><span class="tabular">{{ formatPrice(o.total) }}</span><span class="text-muted">{{ o.at }}</span></li>
                <li v-if="!open.orders.length" class="text-muted">لم يطلب بعد.</li>
              </ul>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>
