<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import AdminHeader from '@/components/admin/AdminHeader.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import { admin, PAY, PAY_STATUS, STATUS } from '@/services/admin'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'
import { formatMl, formatNumber, formatPrice } from '@/utils/format'

// FE-43: one order, everything needed to move it forward.
const route = useRoute()
const auth = useAuthStore()
const ui = useUiStore()
const o = ref(null)
const note = ref('')
const internal = ref('')
const busy = ref(false)
const confirmTo = ref(null)
const refundNote = ref('')
const when = new Intl.DateTimeFormat('ar-EG', { dateStyle: 'medium', timeStyle: 'short' })
const ACTION = { confirmed: 'تأكيد الطلب', preparing: 'بدء التجهيز', shipped: 'تم الشحن', delivered: 'تم التوصيل', cancelled: 'إلغاء الطلب', returned: 'تسجيل مرتجع', placed: 'اعتماد' }

async function load() {
  o.value = (await admin.order(route.params.number)).data
  internal.value = o.value.internalNote || ''
}
onMounted(load)
async function move(to) {
  busy.value = true
  try {
    o.value = (await admin.transition(o.value.number, to, note.value || null)).data
    note.value = ''
    confirmTo.value = null
    ui.toast(`الطلب الآن: ${STATUS[to]}`)
  } catch (e) {
    ui.toast(firstError(e))
  } finally {
    busy.value = false
  }
}
async function saveNote() {
  o.value = (await admin.orderNote(o.value.number, internal.value)).data
  ui.toast('حُفظت الملاحظة')
}
async function refund() {
  try {
    o.value = (await admin.refund(o.value.number, refundNote.value)).data
    refundNote.value = ''
    ui.toast('سُجّل الاسترداد')
  } catch (e) {
    ui.toast(firstError(e))
  }
}
</script>

<template>
  <div v-if="!o" class="rounded-[28px] h-64 animate-pulse bg-paper" aria-busy="true" />
  <template v-else>
    <AdminHeader :title="`طلب ${o.number}`" :lead="`${o.customerName} · ${when.format(new Date(o.placedAt))}`">
      <StatusPill :status="o.status" />
      <a :href="admin.waybillUrl(o.number)" target="_blank" rel="noopener" class="rounded-full border border-ink px-4 py-2 text-ink no-underline">طباعة البوليصة</a>
    </AdminHeader>

    <div class="grid items-start gap-10 xl:grid-cols-[1.4fr_1fr]">
      <div class="flex flex-col gap-8">
        <section v-if="o.allowed.length" class="border border-ink p-5" aria-labelledby="act-h">
          <h2 id="act-h" class="m-0 mb-3 font-display text-2xl">الخطوة التالية</h2>
          <div class="flex flex-wrap gap-2">
            <button v-for="s in o.allowed" :key="s" class="px-5 py-2.5 disabled:opacity-50" :class="s === 'cancelled' || s === 'returned' ? 'border border-danger/60 text-danger' : 'bg-ink text-ivory'" :disabled="busy" @click="s === 'cancelled' || s === 'returned' ? (confirmTo = s) : move(s)">{{ ACTION[s] }}</button>
          </div>
          <div v-if="confirmTo" class="mt-4 border border-danger/40 bg-danger/5 p-4" role="alertdialog" aria-labelledby="cf-h">
            <p id="cf-h" class="m-0 mb-2">{{ confirmTo === 'cancelled' ? 'إلغاء الطلب يُرجع الكمية للمخزون ويُبلغ العميل.' : 'تسجيل المرتجع يُرجع الكمية للمخزون.' }} متأكد؟</p>
            <div class="flex gap-2"><button class="bg-danger px-4 py-2 text-ivory" :disabled="busy" @click="move(confirmTo)">نعم، {{ ACTION[confirmTo] }}</button><button class="rounded-full border border-ink px-4 py-2" @click="confirmTo = null">تراجع</button></div>
          </div>
          <label class="mt-4 flex flex-col gap-1.5 text-[0.875rem]">ملاحظة على هذه الخطوة (اختياري)
            <input v-model="note" maxlength="255" class="h-10 rounded-xl border border-sand bg-paper px-3" placeholder="مثلاً: اتصلنا بالعميل وأكّد العنوان" />
          </label>
        </section>

        <section>
          <h2 class="m-0 mb-3 font-display text-2xl">المحتويات</h2>
          <table class="w-full border-collapse text-[0.9375rem]">
            <tbody>
              <tr v-for="(i, n) in o.items" :key="n" class="border-b border-sand">
                <td class="py-3"><span class="font-medium">{{ i.name }}</span> <span class="text-muted">· {{ i.kind === 'discovery_box' ? 'ست عينات' : formatMl(i.ml) }}</span><span v-if="i.samples?.length" class="block text-[0.8125rem] text-muted">{{ i.samples.join('، ') }}</span></td>
                <td class="text-center tabular">× {{ formatNumber(i.quantity) }}</td>
                <td class="text-end tabular">{{ formatPrice(i.lineTotal) }}</td>
              </tr>
            </tbody>
          </table>
          <dl class="m-0 mt-3 ms-auto flex max-w-xs flex-col gap-1 text-[0.9375rem]">
            <div class="flex justify-between"><dt>المجموع</dt><dd class="m-0 tabular">{{ formatPrice(o.totals.subtotal) }}</dd></div>
            <div v-if="o.totals.discount" class="flex justify-between"><dt>خصم {{ o.couponCode }}</dt><dd class="m-0 tabular">− {{ formatPrice(o.totals.discount) }}</dd></div>
            <div v-if="o.totals.credit" class="flex justify-between"><dt>رصيد الاكتشاف</dt><dd class="m-0 tabular">− {{ formatPrice(o.totals.credit) }}</dd></div>
            <div class="flex justify-between"><dt>الشحن</dt><dd class="m-0 tabular">{{ formatPrice(o.totals.shipping) }}</dd></div>
            <div class="flex justify-between border-t border-ink pt-1 font-medium"><dt>الإجمالي</dt><dd class="m-0 tabular">{{ formatPrice(o.totals.total) }}</dd></div>
          </dl>
        </section>

        <section v-if="o.gift" class="rounded-[28px] bg-paper p-4">
          <h2 class="m-0 mb-1 font-display text-xl">هدية</h2>
          <p class="m-0">إلى {{ o.gift.recipient || '—' }}: «{{ o.gift.message || 'بدون رسالة' }}»</p>
          <p class="m-0 text-[0.8125rem] text-muted">اكتب الكارت بخط أميري، ولا تضع الفاتورة داخل العلبة.</p>
        </section>

        <section>
          <h2 class="m-0 mb-3 font-display text-2xl">السجل</h2>
          <ol class="m-0 list-none border-s border-sand p-0 ps-5">
            <li v-for="(h, i) in o.history" :key="i" class="relative pb-4">
              <span class="absolute -start-[25px] top-1.5 h-2 w-2 rotate-[18deg] bg-gold" aria-hidden="true" />
              <span class="font-medium">{{ h.label }}</span> <span class="text-[0.8125rem] text-muted">· {{ when.format(new Date(h.at)) }}{{ h.by ? ` · ${h.by}` : '' }}</span>
              <span v-if="h.note" class="block text-[0.875rem]">{{ h.note }}</span>
            </li>
          </ol>
        </section>
      </div>

      <aside class="flex flex-col gap-6">
        <section class="rounded-[28px] bg-paper p-5 text-[0.9375rem]">
          <h2 class="m-0 mb-2 font-display text-xl">العميل</h2>
          <p class="m-0 font-medium">{{ o.customerName }}</p>
          <p class="m-0" dir="ltr">{{ o.phone }}</p>
          <p v-if="o.email" class="m-0 text-muted" dir="ltr">{{ o.email }}</p>
          <p class="m-0 mt-2">{{ [o.shippingAddress.governorate, o.shippingAddress.city].filter(Boolean).join('، ') }}</p>
          <p class="m-0">{{ o.shippingAddress.street }} {{ o.shippingAddress.building ? `· عمارة ${o.shippingAddress.building}` : '' }} {{ o.shippingAddress.floor ? `· دور ${o.shippingAddress.floor}` : '' }}</p>
          <p v-if="o.shippingAddress.landmark" class="m-0 text-muted">{{ o.shippingAddress.landmark }}</p>
          <p v-if="o.customerNote" class="m-0 mt-2 border-t border-sand pt-2">ملاحظة العميل: {{ o.customerNote }}</p>
          <p v-if="o.account" class="m-0 mt-2 text-[0.8125rem] text-muted">حساب مسجّل</p>
        </section>
        <section class="rounded-[28px] bg-paper p-5 text-[0.9375rem]">
          <h2 class="m-0 mb-2 font-display text-xl">الدفع</h2>
          <p class="m-0">{{ PAY[o.paymentMethod] }} · {{ PAY_STATUS[o.paymentStatus] }}</p>
          <ul class="m-0 mt-2 list-none p-0 text-[0.8125rem] text-muted">
            <li v-for="p in o.payments" :key="p.reference" dir="ltr" class="text-end">{{ p.gateway }} · {{ p.reference }} · {{ p.status }}</li>
          </ul>
          <form v-if="auth.user?.role === 'admin' && o.paymentStatus === 'paid'" class="mt-3 flex flex-col gap-2 border-t border-sand pt-3" @submit.prevent="refund">
            <label class="text-[0.8125rem]">تسجيل استرداد (بعد تنفيذه من لوحة Paymob)
              <input v-model="refundNote" required maxlength="255" class="mt-1 h-10 w-full rounded-xl border border-sand bg-ivory px-3" placeholder="سبب الاسترداد" />
            </label>
            <button class="rounded-full self-start border border-danger/60 px-4 py-2 text-danger">تسجيل الاسترداد</button>
          </form>
        </section>
        <section class="flex flex-col gap-2">
          <label for="inote" class="font-display text-xl">ملاحظة داخلية</label>
          <textarea id="inote" v-model="internal" rows="4" maxlength="2000" class="rounded-xl border border-sand bg-paper p-3" />
          <button class="rounded-full self-start border border-ink px-4 py-2" @click="saveNote">حفظ</button>
        </section>
      </aside>
    </div>
  </template>
</template>
