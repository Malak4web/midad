<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AuthShell from '@/components/layout/AuthShell.vue'
import FormField from '@/components/ui/FormField.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const f = reactive({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
const errors = ref({})
const busy = ref(false)

async function submit() {
  busy.value = true
  errors.value = {}
  try {
    await auth.register({ ...f })
    router.replace(typeof route.query.next === 'string' && route.query.next.startsWith('/') ? route.query.next : { name: 'account' })
  } catch (e) {
    errors.value = e.errors || { email: [e.message] }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthShell title="حساب جديد" lead="لمتابعة طلباتك، وحفظ عناوينك، ورصيد مجموعة الاكتشاف.">
    <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
      <FormField v-model="f.name" label="الاسم" autocomplete="name" required :error="errors.name" />
      <FormField v-model="f.email" label="البريد الإلكتروني" type="email" autocomplete="email" dir="ltr" required :error="errors.email" />
      <FormField v-model="f.phone" label="رقم الموبايل" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" required :error="errors.phone" />
      <FormField v-model="f.password" label="كلمة المرور" type="password" autocomplete="new-password" required hint="٨ أحرف على الأقل، فيها حروف وأرقام." :error="errors.password" />
      <FormField v-model="f.password_confirmation" label="تأكيد كلمة المرور" type="password" autocomplete="new-password" required />
      <button class="rounded-full bg-ink py-3.5 text-ivory disabled:opacity-50" :disabled="busy">{{ busy ? 'لحظة…' : 'إنشاء الحساب' }}</button>
      <p class="m-0 text-center text-[0.9375rem] text-muted">لديك حساب؟ <RouterLink :to="{ name: 'login', query: route.query }" class="text-ink underline">سجّل الدخول</RouterLink></p>
    </form>
  </AuthShell>
</template>
