import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import { useAuthStore } from '@/stores/auth'

// one directory level only: Vite's dynamic-import globs do not cross '/'
const v = (name) => () => import(`@/views/${name}.vue`)

export const routes = [
  { path: '/', name: 'home', component: HomeView, meta: { bareFooter: true } },
  { path: '/c/:slug', name: 'category', component: v('CategoryView'), meta: { title: 'العطور' } },
  { path: '/p/:slug', name: 'product', component: v('ProductView'), meta: { title: 'العطر' } },
  { path: '/search', name: 'search', component: v('SearchView'), meta: { title: 'نتائج البحث' } },
  { path: '/discovery', name: 'discovery', component: v('DiscoveryView'), meta: { title: 'مجموعة الاكتشاف' } },
  { path: '/finder', name: 'finder', component: v('FinderView'), meta: { title: 'اسأل خبير العطور' } },
  { path: '/gifts', name: 'gifts', component: v('GiftsView'), meta: { title: 'الهدايا' } },
  { path: '/cart', name: 'cart', component: v('CartView'), meta: { title: 'السلة' } },
  { path: '/checkout', name: 'checkout', component: v('CheckoutView'), meta: { title: 'إتمام الطلب' } },
  { path: '/pay/sandbox', name: 'pay-sandbox', component: v('PaySandboxView'), meta: { title: 'الدفع' } },
  { path: '/order/:number', name: 'order', component: v('OrderView'), meta: { title: 'طلبك' } },
  { path: '/track', name: 'track', component: v('TrackView'), meta: { title: 'تتبّع طلبك' } },
  { path: '/login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { title: 'تسجيل الدخول', guest: true } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/RegisterView.vue'), meta: { title: 'حساب جديد', guest: true } },
  { path: '/forgot-password', name: 'forgot', component: () => import('@/views/auth/ForgotView.vue'), meta: { title: 'نسيت كلمة المرور' } },
  { path: '/reset-password', name: 'reset', component: () => import('@/views/auth/ResetView.vue'), meta: { title: 'كلمة مرور جديدة' } },
  { path: '/verify-email', name: 'verify', component: () => import('@/views/auth/VerifyView.vue'), meta: { title: 'تأكيد البريد' } },
  { path: '/account', name: 'account', component: v('AccountView'), meta: { title: 'حسابي', auth: true } },
  { path: '/about', name: 'about', component: v('ContentView'), props: { page: 'about' }, meta: { title: 'قصة مداد' } },
  { path: '/help/:page', name: 'help', component: v('ContentView'), props: true, meta: { title: 'مساعدة' } },
  { path: '/legal/:page', name: 'legal', component: v('ContentView'), props: true, meta: { title: 'قانوني' } },
  {
    path: '/admin',
    component: () => import('@/views/admin/AdminLayout.vue'),
    meta: { auth: true, staff: true, shell: 'admin' },
    children: [
      { path: '', name: 'admin', component: () => import('@/views/admin/AdminDashboard.vue'), meta: { title: 'لوحة التحكم' } },
      { path: 'orders', name: 'admin-orders', component: () => import('@/views/admin/AdminOrders.vue'), meta: { title: 'الطلبات' } },
      { path: 'orders/:number', name: 'admin-order', component: () => import('@/views/admin/AdminOrder.vue'), meta: { title: 'طلب' } },
      { path: 'products', name: 'admin-products', component: () => import('@/views/admin/AdminProducts.vue'), meta: { title: 'العطور' } },
      { path: 'products/new', name: 'admin-product-new', component: () => import('@/views/admin/AdminProduct.vue'), meta: { title: 'عطر جديد', adminOnly: true } },
      { path: 'products/:id', name: 'admin-product', component: () => import('@/views/admin/AdminProduct.vue'), meta: { title: 'تعديل عطر' } },
      { path: 'stock', name: 'admin-stock', component: () => import('@/views/admin/AdminStock.vue'), meta: { title: 'المخزون' } },
      { path: 'spotlights', name: 'admin-spotlights', component: () => import('@/views/admin/AdminSpotlights.vue'), meta: { title: 'الأقسام والهويات', adminOnly: true } },
      { path: 'coupons', name: 'admin-coupons', component: () => import('@/views/admin/AdminCoupons.vue'), meta: { title: 'الكوبونات', adminOnly: true } },
      { path: 'customers', name: 'admin-customers', component: () => import('@/views/admin/AdminCustomers.vue'), meta: { title: 'العملاء', adminOnly: true } },
      { path: 'reports', name: 'admin-reports', component: () => import('@/views/admin/AdminReports.vue'), meta: { title: 'التقارير', adminOnly: true } },
      { path: 'shipping', name: 'admin-shipping', component: () => import('@/views/admin/AdminShipping.vue'), meta: { title: 'الشحن', adminOnly: true } },
      { path: 'settings', name: 'admin-settings', component: () => import('@/views/admin/AdminSettings.vue'), meta: { title: 'الإعدادات', adminOnly: true } },
    ],
  },
  { path: '/:pathMatch(.*)*', name: 'not-found', component: v('NotFoundView'), meta: { title: 'الصفحة غير موجودة' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, saved) {
    if (saved) return saved
    if (to.hash) return { el: to.hash, top: 90 }
    if (to.name === from.name && to.path === from.path) return false // filter changes keep the scroll
    return { top: 0 }
  },
})

router.beforeEach(async (to) => {
  if (!to.meta.auth && !to.meta.guest) return true
  const auth = useAuthStore()
  if (!auth.checked) await auth.fetch()
  if (to.meta.auth && !auth.signedIn) return { name: 'login', query: { next: to.fullPath } }
  if (to.meta.guest && auth.signedIn) return { name: 'account' }
  if (to.meta.staff && !auth.isStaff) return { name: 'not-found', params: { pathMatch: to.path.slice(1).split('/') } }
  if (to.meta.adminOnly && auth.user?.role !== 'admin') return { name: 'admin' }
  return true
})

export default router
