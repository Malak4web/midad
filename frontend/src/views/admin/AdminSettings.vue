<script setup>
import { onMounted, ref } from 'vue'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import FormField from '@/components/ui/FormField.vue'
import TagInput from '@/components/admin/TagInput.vue'
import { admin } from '@/services/admin'
import { api } from '@/services/api'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'

// FE-48: store settings, and the «اسأل خبير العطور» rules in words, not numbers.
const ui = useUiStore()
const s = ref(null)
const errors = ref({})
const finder = ref([])
const notes = ref([])
onMounted(async () => {
  s.value = (await admin.settings()).data
  finder.value = normalize((await admin.finder()).data)
  notes.value = (await api.notes()).data.map((n) => n.name)
})
// an empty PHP map arrives as [] — make it an object so v-model can add keys
const normalize = (qs) => qs.map((q) => ({ ...q, answers: q.answers.map((a) => ({ ...a, categories: Array.isArray(a.categories) ? {} : { ...a.categories } })) }))
async function save() {
  errors.value = {}
  try {
    s.value = (await admin.saveSettings({ ...s.value, whatsapp: s.value.whatsapp || null })).data
    ui.toast('حُفظت الإعدادات')
  } catch (e) {
    errors.value = e.errors || {}
  }
}
async function saveAnswer(a) {
  try {
    finder.value = normalize((await admin.saveFinderAnswer(a.id, { label: a.label, categories: a.categories, notes: a.notes })).data)
    ui.toast('حُفظت الإجابة')
  } catch (e) {
    ui.toast(firstError(e))
  }
}
</script>

<template>
  <AdminHeader title="الإعدادات" />
  <div v-if="!s" class="rounded-[28px] h-40 animate-pulse bg-paper" aria-busy="true" />
  <div v-else class="grid items-start gap-12 xl:grid-cols-2">
    <form class="flex flex-col gap-5" novalidate @submit.prevent="save">
      <h2 class="m-0 font-display text-2xl">المتجر</h2>
      <fieldset class="m-0 flex flex-col gap-2 border-0 p-0">
        <legend class="mb-1 p-0 text-[0.875rem]">شريط الإعلانات (حتى ٥ رسائل)</legend>
        <input v-for="(a, i) in 5" :key="i" v-model="s.announcements[i]" maxlength="80" class="h-10 rounded-xl border border-sand bg-paper px-3" :aria-label="`رسالة ${i + 1}`" />
      </fieldset>
      <div class="grid gap-4 sm:grid-cols-2">
        <FormField v-model="s.freeShippingThreshold" label="الشحن المجاني من ج.م" type="number" :error="errors.freeShippingThreshold" />
        <FormField v-model="s.discoveryPrice" label="سعر مجموعة الاكتشاف ج.م" type="number" :error="errors.discoveryPrice" />
        <FormField v-model="s.discoveryCreditDays" label="صلاحية رصيد الاكتشاف (أيام)" type="number" :error="errors.discoveryCreditDays" />
        <FormField v-model="s.giftWrapFee" label="رسوم تغليف الهدية ج.م" type="number" hint="صفر = مجاني" :error="errors.giftWrapFee" />
        <FormField v-model="s.reservationMinutes" label="مهلة الدفع الإلكتروني (دقائق)" type="number" hint="بعدها يُلغى الطلب غير المدفوع ويرجع المخزون." :error="errors.reservationMinutes" />
        <FormField v-model="s.whatsapp" label="رقم واتساب" dir="ltr" hint="بصيغة 2010XXXXXXXX" :error="errors.whatsapp" />
      </div>
      <button class="rounded-full self-start bg-ink px-8 py-3 text-ivory">حفظ الإعدادات</button>
    </form>

    <section class="flex flex-col gap-5" aria-labelledby="fd-h">
      <h2 id="fd-h" class="m-0 font-display text-2xl">اسأل خبير العطور</h2>
      <p class="m-0 text-muted">كل إجابة تقرّب العميل من قسم أو من نوتات بعينها. الترشيح = مجموع ما تختاره هنا.</p>
      <div v-for="q in finder" :key="q.id" class="rounded-[28px] bg-paper p-4">
        <p class="m-0 mb-3 font-display text-xl">{{ q.question }}</p>
        <div v-for="a in q.answers" :key="a.id" class="flex flex-col gap-2 border-t border-sand py-3">
          <div class="flex flex-wrap items-center gap-3">
            <input v-model="a.label" maxlength="80" class="h-9 w-40 rounded-xl border border-sand bg-paper px-2" :aria-label="`نص الإجابة ${a.label}`" />
            <label v-for="[slug, name] in [['men', 'رجالي'], ['women', 'نسائي'], ['oriental', 'شرقي']]" :key="slug" class="flex items-center gap-1 text-[0.8125rem]">
              {{ name }}
              <select v-model.number="a.categories[slug]" class="h-8 rounded-xl border border-sand bg-paper px-1" :aria-label="`وزن ${name}`"><option v-for="w in [0, 1, 2, 3]" :key="w" :value="w">{{ w ? '+' + w : '—' }}</option></select>
            </label>
          </div>
          <TagInput v-model="a.notes" label="النوتات التي تفضّلها هذه الإجابة" :suggestions="notes" :max="20" />
          <button class="rounded-full self-start border border-ink px-4 py-1.5 text-[0.875rem]" @click="saveAnswer(a)">حفظ الإجابة</button>
        </div>
      </div>
    </section>
  </div>
</template>
