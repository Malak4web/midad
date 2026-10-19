<script setup>
import { reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import AuthShell from '@/components/layout/AuthShell.vue'
import FormField from '@/components/ui/FormField.vue'
import { api } from '@/services/api'

const route = useRoute()
const f = reactive({ email: route.query.email || '', password: '', password_confirmation: '' })
const done = ref('')
const errors = ref({})
const busy = ref(false)
async function submit() {
  busy.value = true
  errors.value = {}
  try {
    done.value = (await api.auth.reset({ ...f, token: route.query.token })).message
  } catch (e) {
    errors.value = e.errors || { email: [e.message] }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthShell title="كلمة مرور جديدة">
    <div v-if="done" class="flex flex-col gap-4">
      <p class="m-0 rounded-[28px] bg-paper p-4" role="status">{{ done }}</p>
      <RouterLink :to="{ name: 'login' }" class="rounded-full self-start bg-ink px-6 py-3 text-ivory no-underline">تسجيل الدخول</RouterLink>
    </div>
    <form v-else class="flex flex-col gap-5" novalidate @submit.prevent="submit">
      <FormField v-model="f.email" label="البريد الإلكتروني" type="email" dir="ltr" required :error="errors.email" />
      <FormField v-model="f.password" label="كلمة المرور الجديدة" type="password" autocomplete="new-password" required hint="٨ أحرف على الأقل، فيها حروف وأرقام." :error="errors.password" />
      <FormField v-model="f.password_confirmation" label="تأكيد كلمة المرور" type="password" autocomplete="new-password" required />
      <button class="rounded-full bg-ink py-3.5 text-ivory disabled:opacity-50" :disabled="busy">{{ busy ? 'لحظة…' : 'حفظ كلمة المرور' }}</button>
    </form>
  </AuthShell>
</template>
