<script setup>
import MidadLogo from '@/components/brand/MidadLogo.vue'
import AppIcon from '@/components/ui/AppIcon.vue'

const year = new Date().getFullYear()
const cols = [
  { title: 'تسوّق', links: [['رجالي', { name: 'category', params: { slug: 'men' } }], ['نسائي', { name: 'category', params: { slug: 'women' } }], ['شرقي ونيش', { name: 'category', params: { slug: 'oriental' } }], ['مجموعة الاكتشاف', { name: 'discovery' }], ['الهدايا', { name: 'gifts' }]] },
  { title: 'مساعدة', links: [['تتبّع طلبك', { name: 'track' }], ['الشحن والاسترجاع', '/help/shipping'], ['الأسئلة الشائعة', '/help/faq'], ['تواصل معنا', '/help/contact']] },
  { title: 'مداد', links: [['قصة مداد', '/about'], ['الخصوصية', '/legal/privacy'], ['الشروط والأحكام', '/legal/terms']] },
]
</script>

<template>
  <footer class="container-midad pb-6 pt-10">
    <div class="foot grid gap-10 md:grid-cols-[1.3fr_repeat(3,1fr)]">
      <div class="flex flex-col gap-4">
        <MidadLogo :height="72" class="text-gold-light" kasra="#D6B77E" />
        <p class="m-0 font-display text-2xl font-bold text-ivory">عطرٌ يُكتَب، فيبقى</p>
        <p class="m-0 max-w-xs text-sm text-muted-light">عطور بثبات طويل، تصل إلى بابك في كل محافظات مصر.</p>
        <div class="flex gap-2">
          <a href="https://www.instagram.com/" target="_blank" rel="noopener" class="social" aria-label="إنستجرام"><AppIcon name="instagram" /></a>
          <a href="https://www.tiktok.com/" target="_blank" rel="noopener" class="social" aria-label="تيك توك"><AppIcon name="tiktok" /></a>
          <a href="https://wa.me/" target="_blank" rel="noopener" class="social" aria-label="واتساب"><AppIcon name="whatsapp" /></a>
        </div>
      </div>
      <nav v-for="c in cols" :key="c.title" :aria-label="c.title">
        <h2 class="m-0 mb-3 text-[0.875rem] font-semibold text-gold-light">{{ c.title }}</h2>
        <ul class="m-0 flex list-none flex-col gap-0.5 p-0">
          <li v-for="[label, to] in c.links" :key="label"><RouterLink :to="to" class="inline-block py-1.5 text-[0.9375rem] text-sand no-underline hover:text-ivory">{{ label }}</RouterLink></li>
        </ul>
      </nav>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 px-2 pt-4 text-[0.8125rem] text-muted">
      <span>© {{ year }} مداد. جميع الحقوق محفوظة.</span>
      <span class="flex items-center gap-4"><span class="inline-flex items-center gap-1.5"><AppIcon name="cash" :size="16" />الدفع عند الاستلام</span><span>بطاقات بنكية · محافظ إلكترونية</span></span>
    </div>
  </footer>
</template>

<style scoped>
.foot { background: var(--color-ink); color: var(--color-sand); border-radius: var(--radius-tile); padding: clamp(26px, 4vw, 44px); }
.social { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 999px; background: var(--color-smoke); color: var(--color-sand); }
.social:hover { background: var(--color-gold-light); color: var(--color-ink); }
</style>
