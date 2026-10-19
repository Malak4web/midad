<script setup>
import { computed, onMounted, reactive, ref, useId, watch } from 'vue'
import { useRouter } from 'vue-router'
import PageHeading from '@/components/layout/PageHeading.vue'
import FormField from '@/components/ui/FormField.vue'
import OrderSummary from '@/components/cart/OrderSummary.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { api } from '@/services/api'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { formatMl, formatNumber, formatPrice } from '@/utils/format'

// FE-32 + FE-33: one page, server-priced, live shipping by governorate.
const cart = useCartStore()
const auth = useAuthStore()
const router = useRouter()
const id = useId()
const governorates = ref([])
const errors = ref({})
const formError = ref('')
const placing = ref(false)
// one address line: the customer writes it the way they would tell the courier
const f = reactive({ customer_name: '', phone: '', governorate_id: '', street: '', payment_method: 'cod' })
const gov = computed(() => governorates.value.find((g) => g.id === +f.governorate_id))
const METHODS = [
  ['cod', 'الدفع عند الاستلام', 'نقداً للمندوب عند استلام الطلب.', 'cash'],
  ['card', 'بطاقة بنكية', 'فيزا أو ماستركارد أو ميزة، دفع آمن عبر Paymob.', 'bag'],
  ['wallet', 'محفظة إلكترونية', 'فودافون كاش وباقي المحافظ.', 'drop'],
]

onMounted(async () => {
  governorates.value = (await api.governorates()).data
  if (!auth.checked) await auth.fetch()
  if (auth.user) {
    Object.assign(f, { customer_name: auth.user.name, phone: auth.user.phone || '' })
    const addr = (await api.account.addresses().catch(() => ({ data: [] }))).data[0]
    if (addr) Object.assign(f, { governorate_id: addr.governorate_id, street: [addr.city, addr.street, addr.building && `عمارة ${addr.building}`, addr.floor && `دور ${addr.floor}`, addr.apartment && `شقة ${addr.apartment}`, addr.landmark].filter(Boolean).join('، ') })
  }
  await cart.load(f.governorate_id || null)
})
watch(() => f.governorate_id, (g) => g && cart.load(+g))

async function place() {
  errors.value = {}
  formError.value = ''
  placing.value = true
  try {
    const { data } = await api.checkout({ ...f, governorate_id: +f.governorate_id })
    cart.reset()
    if (data.redirectUrl) {
      const url = new URL(data.redirectUrl, location.origin)
      if (url.origin === location.origin) router.push(url.pathname + url.search)
      else location.assign(data.redirectUrl)
    } else router.push({ name: 'order', params: { number: data.number }, query: { phone: data.phone, placed: 1 } })
  } catch (e) {
    errors.value = e.errors || {}
    formError.value = e.errors?.cart?.[0] || e.errors?.code?.[0] || (Object.keys(errors.value).length ? 'راجع الحقول المعلّمة بالأحمر.' : e.message)
    if (e.errors?.cart) cart.load(+f.governorate_id || null)
  } finally {
    placing.value = false
  }
}
</script>

<template>
  <main id="main">
    <PageHeading title="إتمام الطلب" lead="بياناتك وعنوان التوصيل وطريقة الدفع، في صفحة واحدة." />

    <div class="container-midad pb-24 pt-4">
      <div v-if="cart.loaded && !cart.items.length" class="flex flex-col items-start gap-4 py-10">
        <p class="m-0 font-display text-3xl">السلة فارغة</p>
        <RouterLink to="/" class="rounded-full bg-ink px-6 py-3 text-ivory no-underline">تصفّح العطور</RouterLink>
      </div>

      <form v-else class="grid items-start gap-12 lg:grid-cols-[1fr_400px]" novalidate @submit.prevent="place">
        <div class="flex flex-col gap-10">
          <p v-if="!auth.signedIn" class="m-0 text-[0.9375rem] text-muted">
            لديك حساب؟ <RouterLink :to="{ name: 'login', query: { next: '/checkout' } }" class="text-ink underline">سجّل الدخول</RouterLink> لتُحفظ طلباتك. أو أكمل كزائر.
          </p>

          <fieldset class="m-0 grid gap-5 border-0 p-0 sm:grid-cols-2">
            <legend class="mb-4 p-0 font-display text-3xl">بياناتك</legend>
            <FormField v-model="f.customer_name" label="الاسم بالكامل" autocomplete="name" required :error="errors.customer_name" />
            <FormField v-model="f.phone" label="رقم الموبايل" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" required hint="نتواصل معك عليه لتأكيد الطلب." :error="errors.phone" />
          </fieldset>

          <fieldset class="m-0 grid gap-5 border-0 p-0 sm:grid-cols-2">
            <legend class="mb-4 p-0 font-display text-3xl">عنوان التوصيل</legend>
            <div class="flex flex-col gap-1.5">
              <label :for="`${id}-g`" class="text-[0.875rem]">المحافظة<span class="text-gold-ink" aria-hidden="true"> *</span></label>
              <select :id="`${id}-g`" v-model="f.governorate_id" required class="h-11 rounded-xl border border-sand bg-paper px-3" :aria-invalid="!!errors.governorate_id">
                <option value="" disabled>اختر المحافظة</option>
                <option v-for="g in governorates" :key="g.id" :value="g.id">{{ g.name }}</option>
              </select>
              <p v-if="errors.governorate_id" class="m-0 text-[0.8125rem] text-danger">{{ errors.governorate_id[0] }}</p>
              <p v-else-if="gov" class="m-0 text-[0.8125rem] text-muted">التوصيل خلال {{ formatNumber(gov.days[0]) }}–{{ formatNumber(gov.days[1]) }} أيام عمل.</p>
            </div>
            <FormField v-model="f.street" class="sm:col-span-2" label="العنوان" autocomplete="street-address" textarea :maxlength="300" required hint="الحي والشارع ورقم العمارة والدور والشقة." :error="errors.street" />
          </fieldset>

          <fieldset class="m-0 flex flex-col gap-3 border-0 p-0">
            <legend class="mb-4 p-0 font-display text-3xl">طريقة الدفع</legend>
            <label v-for="[v, title, sub, icon] in METHODS" :key="v" class="pay flex cursor-pointer items-start gap-4 border p-4" :class="f.payment_method === v ? 'border-ink bg-paper' : 'border-sand'">
              <input v-model="f.payment_method" type="radio" name="pm" :value="v" class="mt-1.5 h-4 w-4 accent-[#0E0D0C]" />
              <AppIcon :name="icon" class="mt-0.5 shrink-0 text-gold" />
              <span class="flex flex-col"><span class="font-medium">{{ title }}</span><span class="text-[0.875rem] text-muted">{{ sub }}</span></span>
            </label>
          </fieldset>
        </div>

        <aside class="rounded-[28px] flex flex-col gap-6 bg-paper p-6 lg:sticky lg:top-24" aria-label="ملخص الطلب">
          <h2 class="m-0 font-display text-3xl">طلبك</h2>
          <ul class="m-0 flex list-none flex-col gap-2 p-0 text-[0.9375rem]">
            <li v-for="i in cart.items" :key="i.id" class="flex justify-between gap-3">
              <span class="min-w-0">{{ i.name }}<span class="text-muted"> · {{ i.kind === 'discovery_box' ? 'ست عينات' : formatMl(i.ml) }} × {{ formatNumber(i.quantity) }}</span></span>
              <span class="shrink-0 tabular">{{ formatPrice(i.lineTotal) }}</span>
            </li>
          </ul>
          <p v-if="cart.cart.gift.wrap" class="m-0 border-t border-sand pt-3 text-[0.875rem]">هدية إلى {{ cart.cart.gift.recipient || '…' }}: «{{ cart.cart.gift.message || 'بدون رسالة' }}»</p>
          <OrderSummary :summary="cart.summary" :coupon="cart.cart.coupon" shipping-hint="اختر المحافظة" />
          <p v-if="formError" class="m-0 border border-danger/40 bg-danger/5 p-3 text-[0.9375rem] text-danger" role="alert">{{ formError }}</p>
          <button class="rounded-full bg-ink py-4 text-ivory disabled:opacity-50" :disabled="placing || cart.busy">
            {{ placing ? 'جارٍ تسجيل الطلب…' : f.payment_method === 'cod' ? 'تأكيد الطلب' : 'المتابعة للدفع' }}
          </button>
          <p class="m-0 text-center text-[0.8125rem] text-muted">بالضغط توافق على <RouterLink to="/legal/terms" class="text-muted">الشروط</RouterLink> و<RouterLink to="/help/shipping" class="text-muted">سياسة الاسترجاع</RouterLink>.</p>
        </aside>
        <!-- phone: the decision stays under the thumb while filling the form -->
        <div class="paybar fixed inset-x-0 bottom-0 z-30 flex items-center gap-3 border-t border-sand bg-ivory/95 px-4 pt-3 backdrop-blur-md lg:hidden">
          <span class="flex flex-col">
            <span class="text-[0.75rem] text-muted">الإجمالي</span>
            <span class="font-display text-[1.375rem] leading-tight tabular">{{ formatPrice(cart.summary.total) }}</span>
          </span>
          <button class="h-12 flex-1 bg-ink text-ivory active:bg-smoke disabled:opacity-50" :disabled="placing || cart.busy" aria-hidden="true" tabindex="-1">
            {{ placing ? 'جارٍ التسجيل…' : f.payment_method === 'cod' ? 'تأكيد الطلب' : 'المتابعة للدفع' }}
          </button>
        </div>
      </form>
    </div>
  </main>
</template>

<style scoped>
.paybar { padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px)); }
@media (max-width: 1023px) { form { padding-bottom: 88px; } }
</style>
