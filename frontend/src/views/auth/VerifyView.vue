<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import AuthShell from '@/components/layout/AuthShell.vue'
import { api } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const auth = useAuthStore()
const state = ref('loading')
const message = ref('')
onMounted(async () => {
  try {
    // the e-mail carries the signed API path; only same-origin API paths are accepted
    const url = new URL(String(route.query.url || ''), location.origin)
    if (!url.pathname.startsWith('/api/auth/verify-email/')) throw new Error('رابط التأكيد غير صالح.')
    message.value = (await api.auth.verify(url.pathname.replace(/^\/api/, '') + url.search)).message
    state.value = 'done'
    auth.fetch()
  } catch (e) {
    message.value = e.status === 403 ? 'رابط التأكيد غير صالح أو انتهت صلاحيته.' : e.message
    state.value = 'error'
  }
})
</script>

<template>
  <AuthShell title="تأكيد البريد الإلكتروني">
    <div v-if="state === 'loading'" class="rounded-[28px] h-16 animate-pulse bg-paper" aria-busy="true" />
    <p v-else class="m-0 border p-4" :class="state === 'done' ? 'border-sand bg-paper' : 'border-danger/40 text-danger'" role="status">{{ message }}</p>
    <RouterLink to="/" class="rounded-full mt-6 inline-block bg-ink px-6 py-3 text-ivory no-underline">تصفّح العطور</RouterLink>
  </AuthShell>
</template>
