<script setup>
import { ref } from 'vue'
import AuthShell from '@/components/layout/AuthShell.vue'
import FormField from '@/components/ui/FormField.vue'
import { api } from '@/services/api'

const email = ref('')
const sent = ref('')
const errors = ref({})
const busy = ref(false)
async function submit() {
  busy.value = true
  errors.value = {}
  try {
    sent.value = (await api.auth.forgot(email.value)).message
  } catch (e) {
    errors.value = e.errors || { email: [e.message] }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthShell title="نسيت كلمة المرور" lead="اكتب بريدك، ونرسل لك رابطاً لاختيار كلمة مرور جديدة.">
    <p v-if="sent" class="m-0 rounded-[28px] bg-paper p-4" role="status">{{ sent }}</p>
    <form v-else class="flex flex-col gap-5" novalidate @submit.prevent="submit">
      <FormField v-model="email" label="البريد الإلكتروني" type="email" autocomplete="email" dir="ltr" required :error="errors.email" />
      <button class="rounded-full bg-ink py-3.5 text-ivory disabled:opacity-50" :disabled="busy">{{ busy ? 'لحظة…' : 'أرسل الرابط' }}</button>
    </form>
    <RouterLink :to="{ name: 'login' }" class="mt-6 inline-block text-[0.9375rem] text-muted">العودة لتسجيل الدخول</RouterLink>
  </AuthShell>
</template>
