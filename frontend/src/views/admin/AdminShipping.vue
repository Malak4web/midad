<script setup>
import { onMounted, ref } from 'vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import { admin } from '@/services/admin'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'

// FE-44: one table for all 27 governorates, saved together.
const ui = useUiStore()
const rows = ref([])
const saving = ref(false)
onMounted(async () => (rows.value = (await admin.governorates()).data))
async function save() {
  saving.value = true
  try {
    rows.value = (await admin.saveGovernorates(rows.value.map((r) => ({ id: r.id, fee: +r.fee, daysMin: +r.daysMin, daysMax: +r.daysMax, active: r.active })))).data
    ui.toast('حُفظت أسعار الشحن')
  } catch (e) {
    ui.toast(firstError(e))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AdminHeader title="الشحن" lead="رسوم ومدة التوصيل لكل محافظة. تظهر للعميل فور اختيار المحافظة.">
    <button class="rounded-full bg-ink px-6 py-2.5 text-ivory disabled:opacity-50" :disabled="saving" @click="save">{{ saving ? 'جارٍ الحفظ…' : 'حفظ الكل' }}</button>
  </AdminHeader>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[600px] border-collapse text-[0.9375rem]">
      <thead><tr class="border-b border-ink text-[0.8125rem] text-muted"><th class="py-2 text-start font-normal">المحافظة</th><th class="text-start font-normal">الرسوم ج.م</th><th class="text-start font-normal">أيام التوصيل</th><th class="text-start font-normal">متاحة</th></tr></thead>
      <tbody>
        <tr v-for="r in rows" :key="r.id" class="border-b border-sand" :class="!r.active && 'opacity-55'">
          <td class="py-2">{{ r.name }}</td>
          <td><input v-model="r.fee" type="number" min="0" class="h-9 w-24 rounded-xl border border-sand bg-paper px-2 tabular" :aria-label="`رسوم ${r.name}`" /></td>
          <td class="whitespace-nowrap"><input v-model="r.daysMin" type="number" min="0" class="h-9 w-16 rounded-xl border border-sand bg-paper px-2 tabular" :aria-label="`أقل مدة ${r.name}`" /> – <input v-model="r.daysMax" type="number" min="0" class="h-9 w-16 rounded-xl border border-sand bg-paper px-2 tabular" :aria-label="`أقصى مدة ${r.name}`" /></td>
          <td><input v-model="r.active" type="checkbox" class="h-4 w-4 accent-[#0E0D0C]" :aria-label="`${r.name} متاحة`" /></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
