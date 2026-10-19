<script setup>
import AttarCameo from '@/components/attar/AttarCameo.vue'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import PageHeading from '@/components/layout/PageHeading.vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import { api } from '@/services/api'
import { formatMl, formatNumber, formatPrice } from '@/utils/format'

// FE-33/34/37 + BE-42: the status always comes from the database.
const route = useRoute()
const order = ref(null)
const error = ref('')
const paying = ref(false)
const STEPS = [['placed', 'تم استلام الطلب'], ['confirmed', 'تم التأكيد'], ['preparing', 'قيد التجهيز'], ['shipped', 'تم الشحن'], ['delivered', 'تم التوصيل']]
const reached = computed(() => STEPS.findIndex(([s]) => s === order.value?.status))
const dateFmt = new Intl.DateTimeFormat('ar-EG', { dateStyle: 'medium', timeStyle: 'short' })

onMounted(async () => {
  try {
    order.value = (await api.order(route.params.number, route.query.phone)).data
  } catch (e) {
    error.value = e.status === 404 ? 'لم نجد طلباً بهذا الرقم وهذا الموبايل.' : e.message
  }
})
async function retry() {
  paying.value = true
  try {
    const { data } = await api.pay(order.value.number, route.query.phone)
    const url = new URL(data.redirectUrl, location.origin)
    location.assign(url.origin === location.origin ? url.pathname + url.search : data.redirectUrl)
  } catch (e) {
    error.value = e.message
    paying.value = false
  }
}
</script>

<template>
  <main id="main">
    <PageHeading :title="route.query.placed && order?.status !== 'pending_payment' ? 'شكراً، وصلنا طلبك' : 'طلبك'" :lead="order ? `رقم الطلب ${order.number}` : ''" />

    <div class="container-midad pb-24 pt-4">
      <AttarCameo v-if="route.query.placed && order && order.status !== 'pending_payment'" pose="heart" :line="`شكراً لثقتك. سنجهّز طلبك بعناية، وأتمنى أن يكتب أثراً جميلاً.`" class="mb-8" />
      <div v-if="error" class="flex flex-col items-start gap-4 py-6">
        <p class="m-0 font-display text-3xl">{{ error }}</p>
        <RouterLink :to="{ name: 'track' }" class="rounded-full bg-ink px-6 py-3 text-ivory no-underline">تتبّع طلب آخر</RouterLink>
      </div>
      <div v-else-if="!order" class="rounded-[28px] h-64 animate-pulse bg-paper" aria-busy="true" />

      <div v-else class="grid items-start gap-12 lg:grid-cols-[1fr_380px]">
        <div class="flex flex-col gap-8">
          <div v-if="order.status === 'pending_payment'" class="flex flex-col gap-3 border border-gold/60 bg-paper p-5" role="status">
            <p class="m-0 font-display text-2xl">{{ order.paymentStatus === 'failed' ? 'لم يكتمل الدفع' : 'بانتظار الدفع' }}</p>
            <p class="m-0 text-muted">طلبك محجوز لك حتى {{ order.reservedUntil ? dateFmt.format(new Date(order.reservedUntil)) : '' }}. أكمل الدفع قبلها حتى لا يُلغى.</p>
            <button v-if="order.canRetryPayment" class="rounded-full self-start bg-ink px-6 py-3 text-ivory disabled:opacity-50" :disabled="paying" @click="retry">{{ paying ? 'لحظة…' : 'ادفع الآن' }}</button>
          </div>
          <p v-else-if="order.status === 'cancelled'" class="m-0 border border-danger/40 p-4 text-danger" role="status">تم إلغاء هذا الطلب.</p>

          <ol v-else class="m-0 grid list-none grid-cols-5 gap-2 p-0" aria-label="حالة الطلب">
            <li v-for="([s, l], i) in STEPS" :key="s" class="flex flex-col gap-2">
              <span class="h-1" :class="i <= reached ? 'bg-ink' : 'bg-sand'" />
              <span class="text-[0.8125rem]" :class="i <= reached ? 'text-ink' : 'text-muted'" :aria-current="i === reached ? 'step' : undefined">{{ l }}</span>
            </li>
          </ol>

          <section>
            <h2 class="m-0 mb-3 font-display text-3xl">المحتويات</h2>
            <ul class="m-0 list-none p-0">
              <li v-for="(i, n) in order.items" :key="n" class="flex justify-between gap-4 border-b border-sand py-3">
                <span>
                  <span class="font-display text-xl font-bold">{{ i.name }}</span>
                  <span class="text-muted"> · {{ i.kind === 'discovery_box' ? 'ست عينات' : formatMl(i.ml) }} × {{ formatNumber(i.quantity) }}</span>
                  <span v-if="i.samples?.length" class="block text-[0.8125rem] text-muted">{{ i.samples.join('، ') }}</span>
                </span>
                <span class="tabular">{{ formatPrice(i.lineTotal) }}</span>
              </li>
            </ul>
          </section>

          <section v-if="order.gift" class="rounded-[28px] bg-paper p-5">
            <h2 class="m-0 mb-2 inline-flex items-center gap-2 font-display text-2xl"><AppIcon name="gift" class="text-gold" />هدية</h2>
            <p class="m-0">إلى {{ order.gift.recipient || '…' }}: «{{ order.gift.message || 'بدون رسالة' }}»</p>
          </section>
        </div>

        <aside class="rounded-[28px] flex flex-col gap-5 bg-paper p-6">
          <dl class="m-0 flex flex-col gap-2 text-[0.9375rem]">
            <div class="flex justify-between"><dt>المجموع الفرعي</dt><dd class="m-0 tabular">{{ formatPrice(order.totals.subtotal) }}</dd></div>
            <div v-if="order.totals.discount" class="flex justify-between text-gold-ink"><dt>الخصم</dt><dd class="m-0 tabular">− {{ formatPrice(order.totals.discount) }}</dd></div>
            <div v-if="order.totals.credit" class="flex justify-between text-gold-ink"><dt>رصيد الاكتشاف</dt><dd class="m-0 tabular">− {{ formatPrice(order.totals.credit) }}</dd></div>
            <div class="flex justify-between"><dt>الشحن</dt><dd class="m-0 tabular">{{ order.totals.shipping ? formatPrice(order.totals.shipping) : 'مجاني' }}</dd></div>
            <div class="flex items-baseline justify-between border-t border-sand pt-3"><dt class="font-medium">الإجمالي</dt><dd class="m-0 font-display text-3xl tabular">{{ formatPrice(order.totals.total) }}</dd></div>
          </dl>
          <div class="border-t border-sand pt-4 text-[0.9375rem]">
            <p class="m-0 font-medium">{{ order.customerName }}</p>
            <p class="m-0 text-muted">{{ [order.shippingAddress.governorate, order.shippingAddress.city, order.shippingAddress.street].filter(Boolean).join('، ') }}</p>
            <p class="m-0 text-muted" dir="ltr">{{ order.phone }}</p>
          </div>
          <p class="m-0 text-[0.875rem] text-muted">{{ { cod: 'الدفع عند الاستلام', card: 'بطاقة بنكية', wallet: 'محفظة إلكترونية' }[order.paymentMethod] }} · {{ order.paymentStatus === 'paid' ? 'مدفوع' : order.paymentMethod === 'cod' ? 'يُدفع عند الاستلام' : 'غير مدفوع' }}</p>
        </aside>
      </div>
    </div>
  </main>
</template>
