<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeading from '@/components/layout/PageHeading.vue'
import FormField from '@/components/ui/FormField.vue'
import { api } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { formatNumber, formatPrice } from '@/utils/format'

// FE-36
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const tab = ref(route.hash.replace('#', '') || 'orders')
const orders = ref(null)
const addresses = ref([])
const governorates = ref([])
const editing = ref(null)
const addr = reactive({})
const errors = ref({})
const profile = reactive({ name: '', phone: '', current_password: '', password: '', password_confirmation: '' })
const profileMsg = ref('')
const dateFmt = new Intl.DateTimeFormat('ar-EG', { dateStyle: 'medium' })

onMounted(async () => {
  Object.assign(profile, { name: auth.user?.name, phone: auth.user?.phone || '' })
  ;[orders.value, addresses.value, governorates.value] = await Promise.all([
    api.account.orders().then((r) => r.data),
    api.account.addresses().then((r) => r.data),
    api.governorates().then((r) => r.data),
  ])
})
watch(tab, (t) => router.replace({ hash: `#${t}` }))

function editAddress(a = null) {
  errors.value = {}
  editing.value = a ? a.id : 'new'
  Object.assign(addr, { governorate_id: '', recipient_name: auth.user?.name || '', phone: auth.user?.phone || '', city: '', street: '', building: '', floor: '', apartment: '', landmark: '', is_default: false }, a || {})
}
async function saveAddress() {
  errors.value = {}
  try {
    await api.account.saveAddress({ ...addr, governorate_id: +addr.governorate_id }, editing.value === 'new' ? null : editing.value)
    addresses.value = (await api.account.addresses()).data
    editing.value = null
  } catch (e) {
    errors.value = e.errors || {}
  }
}
async function removeAddress(a) {
  await api.account.deleteAddress(a.id)
  addresses.value = addresses.value.filter((x) => x.id !== a.id)
}
async function saveProfile() {
  errors.value = {}
  profileMsg.value = ''
  const body = { name: profile.name, phone: profile.phone }
  if (profile.password) Object.assign(body, { current_password: profile.current_password, password: profile.password, password_confirmation: profile.password_confirmation })
  try {
    auth.user = (await api.auth.profile(body)).data
    Object.assign(profile, { current_password: '', password: '', password_confirmation: '' })
    profileMsg.value = 'تم حفظ بياناتك.'
  } catch (e) {
    errors.value = e.errors || {}
  }
}
async function logout() {
  await auth.logout()
  router.push('/')
}
</script>

<template>
  <main id="main">
    <PageHeading :title="`أهلاً، ${auth.user?.name || ''}`" lead="طلباتك وعناوينك وبياناتك في مكان واحد.">
      <div class="mt-3 flex flex-wrap gap-2" role="tablist">
        <button v-for="[k, l] in [['orders', 'طلباتي'], ['addresses', 'عناويني'], ['profile', 'بياناتي']]" :key="k" role="tab" :aria-selected="tab === k" class="rounded-full border px-4 py-1.5" :class="tab === k ? 'border-ivory bg-ivory text-ink' : 'border-sand/40 text-ivory'" @click="tab = k">{{ l }}</button>
        <button class="ms-auto text-[0.9375rem] text-sand underline" @click="logout">تسجيل الخروج</button>
      </div>
    </PageHeading>

    <div class="container-midad pb-24 pt-4">
      <section v-if="tab === 'orders'" aria-label="طلباتي">
        <div v-if="!orders" class="rounded-[28px] h-40 animate-pulse bg-paper" aria-busy="true" />
        <div v-else-if="!orders.length" class="flex flex-col items-start gap-3 py-6">
          <p class="m-0 font-display text-3xl">لا توجد طلبات بعد</p>
          <RouterLink to="/" class="rounded-full bg-ink px-6 py-3 text-ivory no-underline">تصفّح العطور</RouterLink>
        </div>
        <ul v-else class="m-0 list-none p-0">
          <li v-for="o in orders" :key="o.number" class="border-b border-sand">
            <RouterLink :to="{ name: 'order', params: { number: o.number } }" class="grid grid-cols-[1fr_auto] items-center gap-2 py-4 text-ink no-underline hover:bg-paper sm:grid-cols-[180px_1fr_140px_auto]">
              <span class="font-meta tracking-[0.06em]" dir="ltr">{{ o.number }}</span>
              <span class="text-muted max-sm:order-3">{{ o.placedAt ? dateFmt.format(new Date(o.placedAt)) : '' }} · {{ formatNumber(o.items.length) }} أصناف</span>
              <span class="tabular">{{ formatPrice(o.totals.total) }}</span>
              <span class="rounded-full border border-ink/25 px-3 py-0.5 text-[0.8125rem] max-sm:order-4 max-sm:justify-self-start">{{ o.statusLabel }}</span>
            </RouterLink>
          </li>
        </ul>
      </section>

      <section v-else-if="tab === 'addresses'" class="flex flex-col gap-6" aria-label="عناويني">
        <ul class="m-0 grid list-none gap-4 p-0 md:grid-cols-2">
          <li v-for="a in addresses" :key="a.id" class="flex flex-col gap-1 border p-5" :class="a.is_default ? 'border-ink' : 'border-sand'">
            <span class="font-medium">{{ a.recipient_name }} <span v-if="a.is_default" class="ms-2 text-[0.8125rem] text-gold-ink">الافتراضي</span></span>
            <span class="text-muted">{{ a.governorate?.name }}، {{ a.city }}، {{ a.street }}</span>
            <span class="text-muted" dir="ltr">{{ a.phone }}</span>
            <div class="mt-2 flex gap-4 text-[0.875rem]"><button class="underline" @click="editAddress(a)">تعديل</button><button class="text-danger underline" @click="removeAddress(a)">حذف</button></div>
          </li>
        </ul>
        <button v-if="!editing" class="rounded-full self-start border border-ink px-5 py-2.5" @click="editAddress()">إضافة عنوان</button>
        <form v-else class="grid max-w-2xl gap-4 rounded-[28px] bg-paper p-5 sm:grid-cols-2" novalidate @submit.prevent="saveAddress">
          <FormField v-model="addr.recipient_name" label="اسم المستلم" required :error="errors.recipient_name" />
          <FormField v-model="addr.phone" label="الموبايل" type="tel" dir="ltr" required :error="errors.phone" />
          <div class="flex flex-col gap-1.5">
            <label for="addr-g" class="text-[0.875rem]">المحافظة</label>
            <select id="addr-g" v-model="addr.governorate_id" class="h-11 rounded-xl border border-sand bg-paper px-3"><option value="" disabled>اختر</option><option v-for="g in governorates" :key="g.id" :value="g.id">{{ g.name }}</option></select>
            <p v-if="errors.governorate_id" class="m-0 text-[0.8125rem] text-danger">{{ errors.governorate_id[0] }}</p>
          </div>
          <FormField v-model="addr.city" label="المدينة أو الحي" required :error="errors.city" />
          <FormField v-model="addr.street" class="sm:col-span-2" label="الشارع ورقم العقار" required :error="errors.street" />
          <FormField v-model="addr.building" label="العمارة" />
          <FormField v-model="addr.floor" label="الدور" />
          <label class="flex items-center gap-2 sm:col-span-2"><input v-model="addr.is_default" type="checkbox" class="h-4 w-4 accent-[#0E0D0C]" /> العنوان الافتراضي</label>
          <div class="flex gap-3 sm:col-span-2"><button class="rounded-full bg-ink px-6 py-3 text-ivory">حفظ</button><button type="button" class="rounded-full border border-ink px-6 py-3" @click="editing = null">إلغاء</button></div>
        </form>
      </section>

      <form v-else class="flex max-w-lg flex-col gap-5" novalidate @submit.prevent="saveProfile">
        <FormField v-model="profile.name" label="الاسم" :error="errors.name" />
        <FormField v-model="profile.phone" label="الموبايل" type="tel" dir="ltr" :error="errors.phone" />
        <p class="m-0 text-[0.875rem] text-muted" dir="ltr">{{ auth.user?.email }}</p>
        <fieldset class="m-0 flex flex-col gap-4 border-0 border-t border-sand p-0 pt-5">
          <legend class="p-0 font-display text-2xl">تغيير كلمة المرور (اختياري)</legend>
          <FormField v-model="profile.current_password" label="كلمة المرور الحالية" type="password" autocomplete="current-password" :error="errors.current_password" />
          <FormField v-model="profile.password" label="كلمة المرور الجديدة" type="password" autocomplete="new-password" :error="errors.password" />
          <FormField v-model="profile.password_confirmation" label="تأكيدها" type="password" autocomplete="new-password" />
        </fieldset>
        <p v-if="profileMsg" class="m-0 text-muted" role="status">{{ profileMsg }}</p>
        <button class="rounded-full self-start bg-ink px-8 py-3.5 text-ivory">حفظ</button>
      </form>
    </div>
  </main>
</template>
