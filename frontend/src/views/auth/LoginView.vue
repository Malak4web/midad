<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AuthShell from '@/components/layout/AuthShell.vue'
import FormField from '@/components/ui/FormField.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const email = ref('')
const password = ref('')
const errors = ref({})
const busy = ref(false)

async function submit() {
  busy.value = true
  errors.value = {}
  try {
    await auth.login(email.value, password.value)
    router.replace(typeof route.query.next === 'string' && route.query.next.startsWith('/') ? route.query.next : { name: 'account' })
  } catch (e) {
    errors.value = e.errors && Object.keys(e.errors).length ? e.errors : { email: [e.message] }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthShell title="تسجيل الدخول" lead="سلتك وطلباتك محفوظة في حسابك.">
    <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
      <FormField v-model="email" label="البريد الإلكتروني" type="email" autocomplete="email" dir="ltr" required :error="errors.email" />
      <FormField v-model="password" label="كلمة المرور" type="password" autocomplete="current-password" required :error="errors.password" />
      <RouterLink :to="{ name: 'forgot' }" class="-mt-2 self-start text-[0.875rem] text-muted">نسيت كلمة المرور؟</RouterLink>
      <button class="rounded-full bg-ink py-3.5 text-ivory disabled:opacity-50" :disabled="busy">{{ busy ? 'لحظة…' : 'دخول' }}</button>
      <p class="m-0 text-center text-[0.9375rem] text-muted">جديد على مداد؟ <RouterLink :to="{ name: 'register', query: route.query }" class="text-ink underline">أنشئ حساباً</RouterLink></p>
    </form>
  </AuthShell>
</template>
