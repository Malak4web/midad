<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/services/api'
import { formatPrice } from '@/utils/format'

// Stands in for the Paymob page while there is no merchant account (SET-06).
// It reports back through the same signed webhook a real payment would.
const route = useRoute()
const router = useRouter()
const pay = ref(null)
const error = ref('')
const busy = ref(false)

onMounted(async () => {
  try {
    pay.value = (await api.sandbox(route.query.ref)).data
  } catch {
    error.value = 'رابط الدفع غير صالح.'
  }
})
async function finish(result) {
  busy.value = true
  try {
    const { data } = await api.sandboxComplete(route.query.ref, result)
    router.replace({ name: 'order', params: { number: data.orderNumber }, query: { phone: data.phone, placed: 1 } })
  } catch (e) {
    error.value = e.message
    busy.value = false
  }
}
</script>

<template>
  <main id="main" class="container-midad flex min-h-[70vh] items-center justify-center py-16">
    <div class="w-full max-w-md rounded-[28px] bg-paper p-8">
      <p class="m-0 mb-6 inline-block border border-dashed border-muted px-2 py-0.5 text-[0.75rem] text-muted">بيئة دفع تجريبية · لا يتم خصم أي مبلغ</p>
      <p v-if="error" class="m-0 text-danger" role="alert">{{ error }}</p>
      <template v-else-if="pay">
        <h1 class="m-0 font-display text-3xl">ادفع طلبك</h1>
        <p class="m-0 mt-1 text-muted">طلب رقم <span dir="ltr" class="font-meta">{{ pay.orderNumber }}</span></p>
        <p class="m-0 my-6 font-display text-5xl tabular">{{ formatPrice(pay.amount) }}</p>
        <p v-if="pay.status !== 'pending'" class="m-0 mb-4 text-muted">هذه العملية {{ pay.status === 'paid' ? 'مدفوعة بالفعل' : 'انتهت' }}.</p>
        <div class="grid gap-3">
          <button class="rounded-full bg-ink py-3.5 text-ivory disabled:opacity-50" :disabled="busy || pay.status !== 'pending'" @click="finish('success')">محاكاة دفع ناجح</button>
          <button class="border border-ink py-3.5 disabled:opacity-50" :disabled="busy || pay.status !== 'pending'" @click="finish('fail')">محاكاة رفض البطاقة</button>
        </div>
      </template>
      <div v-else class="rounded-[28px] h-40 animate-pulse bg-sand/30" aria-busy="true" />
    </div>
  </main>
</template>
