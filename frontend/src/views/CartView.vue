<script setup>
import AttarCameo from '@/components/attar/AttarCameo.vue'
import { onMounted, ref, useId, watch } from 'vue'
import PageHeading from '@/components/layout/PageHeading.vue'
import CartLines from '@/components/cart/CartLines.vue'
import OrderSummary from '@/components/cart/OrderSummary.vue'
import { useCartStore } from '@/stores/cart'
import { firstError } from '@/composables/useAddToCart'
import { formatNumber, formatPrice } from '@/utils/format'

// FE-31
const cart = useCartStore()
const id = useId()
const code = ref('')
const couponError = ref('')
const gift = ref({ wrap: false, recipient: '', message: '' })
const giftSaved = ref(false)

onMounted(() => cart.load().catch(() => {}))
watch(() => cart.cart.gift, (g) => { gift.value = { wrap: g.wrap, recipient: g.recipient || '', message: g.message || '' } }, { immediate: true })

async function apply() {
  couponError.value = ''
  try {
    await cart.applyCoupon(code.value)
    code.value = ''
  } catch (e) {
    couponError.value = firstError(e)
  }
}
async function saveGift() {
  await cart.setGift(gift.value.wrap, gift.value.recipient, gift.value.message)
  giftSaved.value = true
  setTimeout(() => (giftSaved.value = false), 2500)
}
</script>

<template>
  <main id="main">
    <PageHeading title="السلة" :lead="cart.count ? `${formatNumber(cart.count)} ${cart.count === 1 ? 'قطعة' : 'قطع'} في سلتك.` : ''" />

    <div class="container-midad pb-24 pt-4">
      <div v-if="cart.loaded && !cart.items.length" class="flex flex-col items-center gap-4 py-16 text-center">
        <AttarCameo pose="thinking" line="سلّتك فارغة… أخبرني بما تحب، وأختار لك." :to="{ name: 'finder' }" action="اسأل خبير العطور" class="text-start" />
        <p class="m-0 font-display text-3xl">السلة فارغة</p>
        <p class="m-0 max-w-sm text-muted">ابدأ بمجموعة الاكتشاف إن كنت ما زلت تجرّب، أو تصفّح الأقسام.</p>
        <div class="flex gap-3">
          <RouterLink :to="{ name: 'discovery' }" class="rounded-full bg-ink px-6 py-3 text-ivory no-underline">ابدأ بستّ عينات</RouterLink>
          <RouterLink to="/" class="rounded-full border border-ink px-6 py-3 text-ink no-underline">تصفّح العطور</RouterLink>
        </div>
      </div>

      <div v-else class="grid grid-cols-[minmax(0,1fr)] items-start gap-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-12">
        <div class="flex min-w-0 flex-col gap-6 lg:gap-10">
          <CartLines />

          <section class="flex flex-col gap-4 rounded-[28px] bg-paper p-5" aria-labelledby="gift-h">
            <label class="flex cursor-pointer items-center gap-3">
              <input v-model="gift.wrap" type="checkbox" class="h-5 w-5 accent-[#0E0D0C]" @change="!gift.wrap && saveGift()" />
              <span id="gift-h" class="font-display text-2xl">تغليف هدية وكارت رسالة</span>
            </label>
            <div v-if="gift.wrap" class="grid gap-4 sm:grid-cols-[1fr_2fr]">
              <div class="flex flex-col gap-1.5">
                <label :for="`${id}-to`" class="text-[0.875rem]">إلى</label>
                <input :id="`${id}-to`" v-model="gift.recipient" maxlength="60" class="h-11 w-full min-w-0 rounded-xl border border-sand bg-paper px-3 outline-none focus:border-ink" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label :for="`${id}-msg`" class="flex justify-between text-[0.875rem]">الرسالة <span class="tabular text-muted">{{ formatNumber(120 - gift.message.length) }}</span></label>
                <textarea :id="`${id}-msg`" v-model="gift.message" maxlength="120" rows="2" class="w-full min-w-0 resize-none rounded-xl border border-sand bg-paper px-3 py-2 outline-none focus:border-ink" />
              </div>
              <div class="flex items-center gap-3 sm:col-span-2">
                <button class="rounded-full border border-ink px-5 py-2.5" :disabled="cart.busy" @click="saveGift">حفظ الكارت</button>
                <span v-if="giftSaved" class="text-[0.875rem] text-muted" role="status">تم الحفظ.</span>
              </div>
            </div>
          </section>
        </div>

        <aside class="rounded-[28px] flex flex-col gap-6 bg-paper p-6 lg:sticky lg:top-24" aria-label="ملخص الطلب">
          <form class="flex flex-col gap-2" @submit.prevent="apply">
            <label :for="`${id}-c`" class="text-[0.875rem]">كود خصم أو رصيد الاكتشاف</label>
            <div v-if="cart.cart.coupon" class="flex items-center justify-between border border-gold/60 px-3 py-2">
              <span><span class="font-meta tracking-[0.1em]" dir="ltr">{{ cart.cart.coupon.code }}</span> · {{ cart.cart.coupon.label }}</span>
              <button type="button" class="text-[0.8125rem] underline" @click="cart.removeCoupon()">إزالة</button>
            </div>
            <p v-if="cart.cart.coupon?.problem" class="m-0 text-[0.8125rem] text-danger">{{ cart.cart.coupon.problem }}</p>
            <div v-if="!cart.cart.coupon" class="flex gap-2">
              <input :id="`${id}-c`" v-model="code" dir="ltr" class="h-11 min-w-0 flex-1 rounded-xl border border-sand bg-ivory px-3 uppercase outline-none focus:border-ink" :aria-invalid="!!couponError" />
              <button class="rounded-full border border-ink px-4 disabled:opacity-50" :disabled="!code.trim() || cart.busy">تطبيق</button>
            </div>
            <p v-if="couponError" class="m-0 text-[0.8125rem] text-danger" role="alert">{{ couponError }}</p>
          </form>

          <OrderSummary :summary="cart.summary" :coupon="cart.cart.coupon" />
          <p v-if="cart.summary.freeShippingRemaining > 0" class="m-0 text-[0.875rem] text-muted">أضف {{ formatPrice(cart.summary.freeShippingRemaining) }} وتحصل على شحن مجاني.</p>
          <RouterLink :to="{ name: 'checkout' }" class="rounded-full bg-ink py-4 text-center text-ivory no-underline">إتمام الطلب</RouterLink>
          <p class="m-0 text-center text-[0.8125rem] text-muted">الدفع عند الاستلام، بطاقة، أو محفظة إلكترونية.</p>
        </aside>
      </div>
    </div>
  </main>
</template>
