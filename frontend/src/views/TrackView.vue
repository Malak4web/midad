<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeading from '@/components/layout/PageHeading.vue'
import FormField from '@/components/ui/FormField.vue'

// FE-37: guests find an order with its number and the phone used on it.
const router = useRouter()
const number = ref('')
const phone = ref('')
const error = ref('')
function go() {
  error.value = !number.value.trim() || !phone.value.trim() ? 'اكتب رقم الطلب ورقم الموبايل.' : ''
  if (!error.value) router.push({ name: 'order', params: { number: number.value.trim().toUpperCase() }, query: { phone: phone.value.trim() } })
}
</script>

<template>
  <main id="main">
    <PageHeading title="تتبّع طلبك" lead="رقم الطلب موجود في رسالة التأكيد، ويبدأ بـ MD." />
    <form class="container-midad flex max-w-lg flex-col gap-5 pb-24 pt-4" novalidate @submit.prevent="go">
      <FormField v-model="number" label="رقم الطلب" dir="ltr" required />
      <FormField v-model="phone" label="رقم الموبايل المستخدم في الطلب" type="tel" inputmode="tel" dir="ltr" required />
      <p v-if="error" class="m-0 text-danger" role="alert">{{ error }}</p>
      <button class="rounded-full self-start bg-ink px-8 py-3.5 text-ivory">عرض الطلب</button>
    </form>
  </main>
</template>
