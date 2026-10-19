<script setup>
import AppIcon from '@/components/ui/AppIcon.vue'

// FE-30. PLACEHOLDER: set VITE_WHATSAPP_NUMBER (e.g. 2010XXXXXXXX) once Midad has a business number.
const number = import.meta.env.VITE_WHATSAPP_NUMBER || ''
const text = encodeURIComponent('أهلاً مداد، محتاج أستشيركم في عطر.')
</script>

<template>
  <a
    :href="number ? `https://wa.me/${number}?text=${text}` : '/help/contact'"
    :target="number ? '_blank' : undefined"
    rel="noopener"
    class="fixed bottom-[calc(16px+env(safe-area-inset-bottom,0px))] end-[calc(16px+env(safe-area-inset-left,0px))] z-40 grid h-12 w-12 place-items-center rounded-full bg-ink text-gold-light shadow-[0_12px_30px_-12px_rgba(14,13,12,.6)] transition-colors hover:bg-smoke"
    aria-label="استشارة على واتساب"
  >
    <AppIcon name="whatsapp" :size="22" />
  </a>
</template>
