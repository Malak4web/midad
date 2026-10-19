<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import ProductThumb from '@/components/brand/ProductThumb.vue'
import OrderSummary from '@/components/cart/OrderSummary.vue'
import MTopBar from './MTopBar.vue'
import { useCartStore } from '@/stores/cart'
import { useUiStore } from '@/stores/ui'
import { firstError } from '@/composables/useAddToCart'
import { formatMl, formatNumber, formatPrice } from '@/utils/format'

// The cart on the phone: compact lines with steppers, the gift as a switch,
// the code folded away, and the total with checkout pinned at the bottom.
const cart = useCartStore()
const ui = useUiStore()
const gift = reactive({ wrap: false, recipient: '', message: '' })
const code = ref('')
const codeOpen = ref(false)
const couponError = ref('')
onMounted(() => cart.load().catch(() => {}))
watch(() => cart.cart.gift, (g) => Object.assign(gift, { wrap: !!g?.wrap, recipient: g?.recipient || '', message: g?.message || '' }), { immediate: true })

async function qty(item, n) {
  try {
    await cart.setQty(item.id, n)
  } catch (e) {
    ui.toast(firstError(e))
  }
}
async function saveGift() {
  try {
    await cart.setGift(gift.wrap, gift.recipient, gift.message)
    ui.toast(gift.wrap ? 'حُفظ الكارت' : 'أُلغي التغليف')
  } catch (e) {
    ui.toast(firstError(e))
  }
}
async function apply() {
  couponError.value = ''
  try {
    await cart.applyCoupon(code.value.trim())
    code.value = ''
  } catch (e) {
    couponError.value = firstError(e)
  }
}
</script>

<template>
  <main id="main" class="m">
    <MTopBar title="السلة" :sub="cart.count ? `${formatNumber(cart.count)} ${cart.count === 1 ? 'قطعة' : 'قطع'}` : ''" />

    <div v-if="cart.loaded && !cart.items.length" class="empty">
      <AppIcon name="bag" :size="40" class="text-gold" />
      <p class="m-0 font-display text-[1.25rem] font-bold">السلة فارغة</p>
      <p class="m-0 text-[0.8125rem] text-muted">ابدأ بمجموعة الاكتشاف، أو تصفّح الأقسام.</p>
      <RouterLink :to="{ name: 'discovery' }" class="btn">ابدأ بستّ عينات</RouterLink>
      <RouterLink :to="{ name: 'finder' }" class="text-[0.8125rem] underline">أو اسأل خبير العطور</RouterLink>
    </div>

    <template v-else>
      <ul class="lines m-0 list-none p-0">
        <li v-for="i in cart.items" :key="i.id">
          <span class="th">
            <span v-if="i.kind === 'discovery_box'" class="box" aria-hidden="true"><AppIcon name="gift" :size="22" /></span>
            <ProductThumb v-else :size="52" />
          </span>
          <span class="min-w-0">
            <RouterLink v-if="i.slug" :to="{ name: 'product', params: { slug: i.slug } }" class="nm no-underline">{{ i.name }}</RouterLink>
            <b v-else class="nm">{{ i.name }}</b>
            <small>{{ i.kind === 'discovery_box' ? `ست عينات` : formatMl(i.ml) }}</small>
            <small v-if="!i.available" class="text-danger">المتاح: {{ formatNumber(i.stock ?? 0) }}</small>
            <span class="step" :class="cart.busy && 'opacity-60'">
              <button :disabled="cart.busy" :aria-label="`إنقاص كمية ${i.name}`" @click="qty(i, i.quantity - 1)"><AppIcon name="minus" :size="14" /></button>
              <span class="tabular" aria-live="polite">{{ formatNumber(i.quantity) }}</span>
              <button :disabled="cart.busy || i.quantity >= (i.kind === 'discovery_box' ? 3 : 10)" :aria-label="`زيادة كمية ${i.name}`" @click="qty(i, i.quantity + 1)"><AppIcon name="plus" :size="14" /></button>
            </span>
          </span>
          <span class="end">
            <b class="tabular">{{ formatPrice(i.lineTotal) }}</b>
            <button class="rm" :disabled="cart.busy" :aria-label="`إزالة ${i.name}`" @click="qty(i, 0)"><AppIcon name="close" :size="16" /></button>
          </span>
        </li>
      </ul>

      <section class="card">
        <label class="sw">
          <AppIcon name="gift" :size="20" />
          <span class="flex-1">تغليف هدية وكارت رسالة</span>
          <input v-model="gift.wrap" type="checkbox" class="sr-only" @change="!gift.wrap && saveGift()" />
          <i class="toggle" :class="gift.wrap && 'on'" aria-hidden="true" />
        </label>
        <div v-if="gift.wrap" class="flex flex-col gap-2">
          <label for="m-gift-to" class="sr-only">إلى</label>
          <input id="m-gift-to" v-model="gift.recipient" maxlength="60" placeholder="إلى" class="field" />
          <label for="m-gift-msg" class="sr-only">الرسالة</label>
          <textarea id="m-gift-msg" v-model="gift.message" maxlength="120" rows="2" placeholder="رسالتك" class="field resize-none" />
          <button class="mini" :disabled="cart.busy" @click="saveGift">حفظ الكارت</button>
        </div>
      </section>

      <section class="card">
        <div v-if="cart.cart.coupon" class="flex items-center justify-between gap-2 text-[0.8125rem]">
          <span><span dir="ltr">{{ cart.cart.coupon.code }}</span> · {{ cart.cart.coupon.label }}</span>
          <button class="underline" @click="cart.removeCoupon()">إزالة</button>
        </div>
        <template v-else>
          <button class="sw" :aria-expanded="codeOpen" @click="codeOpen = !codeOpen"><AppIcon name="plus" :size="18" /><span class="flex-1 text-start">كود خصم أو رصيد الاكتشاف</span></button>
          <form v-if="codeOpen" class="flex gap-2" @submit.prevent="apply">
            <label for="m-code" class="sr-only">الكود</label>
            <input id="m-code" v-model="code" dir="ltr" class="field flex-1 uppercase" :aria-invalid="!!couponError" />
            <button class="mini" :disabled="!code.trim() || cart.busy">تطبيق</button>
          </form>
          <p v-if="couponError" class="m-0 text-[0.75rem] text-danger" role="alert">{{ couponError }}</p>
        </template>
      </section>

      <section class="card text-[0.8125rem]"><OrderSummary :summary="cart.summary" :coupon="cart.cart.coupon" /></section>

      <div class="paybar">
        <span class="price"><small>الإجمالي</small><b class="tabular">{{ formatPrice(cart.summary.total) }}</b></span>
        <RouterLink :to="{ name: 'checkout' }" class="go">إتمام الطلب</RouterLink>
      </div>
    </template>
  </main>
</template>

<style scoped>
.m { padding-bottom: 130px; font-size: 0.875rem; display: flex; flex-direction: column; gap: 8px; }
.empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 48px 24px; text-align: center; }
.btn { display: inline-flex; align-items: center; height: 46px; padding: 0 22px; margin-top: 6px; border-radius: 14px; background: var(--color-ink); color: var(--color-gold-light); text-decoration: none; font-weight: 600; }
.lines { display: flex; flex-direction: column; gap: 8px; padding: 0 16px; }
.lines li { display: grid; grid-template-columns: 56px minmax(0, 1fr) auto; gap: 10px; align-items: center; padding: 8px; border-radius: 16px; background: var(--color-paper); }
.th { display: grid; place-items: center; height: 64px; border-radius: 12px; background: var(--color-linen); }
.box { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 10px; background: var(--color-ink); color: var(--color-gold-light); }
.nm { display: block; font-family: var(--font-display); font-weight: 700; font-size: 1rem; line-height: 1.3; color: var(--color-ink); }
small { display: block; font-size: 0.72rem; color: var(--color-muted); }
.step { display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; padding: 2px; border-radius: 999px; background: var(--color-ivory); }
.step button { display: grid; place-items: center; width: 30px; height: 30px; border-radius: 999px; }
.step span { min-width: 18px; text-align: center; font-size: 0.8125rem; }
.end { display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between; align-self: stretch; font-size: 0.8125rem; }
.rm { display: grid; place-items: center; width: 32px; height: 32px; color: var(--color-muted); }
.card { display: flex; flex-direction: column; gap: 8px; margin: 0 16px; padding: 12px; border-radius: 16px; background: var(--color-paper); }
.sw { display: flex; align-items: center; gap: 10px; min-height: 40px; width: 100%; font-size: 0.8125rem; cursor: pointer; }
.toggle { position: relative; flex: none; width: 42px; height: 26px; border-radius: 999px; background: var(--color-sand); transition: background 0.2s; }
.toggle::after { content: ''; position: absolute; top: 3px; right: 3px; width: 20px; height: 20px; border-radius: 50%; background: #fff; transition: transform 0.2s; }
.toggle.on { background: var(--color-ink); }
.toggle.on::after { transform: translateX(-16px); }
.sw:has(input:focus-visible) .toggle { outline: 2px solid var(--color-ink); outline-offset: 2px; }
.field { min-width: 0; padding: 10px 12px; border: 0; border-radius: 12px; background: var(--color-ivory); font-size: 0.875rem; outline: none; box-shadow: inset 0 0 0 1px var(--color-sand); }
.field:focus { box-shadow: inset 0 0 0 2px var(--color-ink); }
.mini { align-self: flex-start; height: 40px; padding: 0 16px; border-radius: 12px; background: var(--color-ink); color: var(--color-gold-light); font-size: 0.8125rem; }
.card :deep(dd.text-3xl) { font-size: 1.25rem; }
.card :deep(dl) { font-size: 0.8125rem; }
.paybar { position: fixed; inset-inline: 12px; bottom: calc(100px + env(safe-area-inset-bottom, 0px)); z-index: 35; display: grid; grid-template-columns: auto 1fr; gap: 8px; }
.price { display: flex; flex-direction: column; justify-content: center; min-width: 110px; height: 50px; padding: 0 14px; border-radius: 14px; background: var(--color-paper); line-height: 1.2; box-shadow: 0 8px 20px -10px rgba(14, 13, 12, 0.4); }
.price b { font-size: 1rem; }
.go { display: flex; align-items: center; justify-content: center; height: 50px; border-radius: 14px; background: var(--color-ink); color: var(--color-gold-light); font-size: 0.9375rem; font-weight: 600; text-decoration: none; box-shadow: 0 8px 20px -10px rgba(14, 13, 12, 0.6); }
</style>
