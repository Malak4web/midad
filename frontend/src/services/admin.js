// Admin API (INT-11). Same fetch client and CSRF handling as the storefront.
import { ensureCsrf, http } from './http'

const qs = (p = {}) => {
  const s = new URLSearchParams(Object.entries(p).filter(([, v]) => v !== undefined && v !== null && v !== '')).toString()
  return s ? `?${s}` : ''
}

export const admin = {
  dashboard: () => http('/admin/dashboard'),

  orders: (f) => http(`/admin/orders${qs(f)}`),
  order: (n) => http(`/admin/orders/${encodeURIComponent(n)}`),
  transition: (n, status, note) => http(`/admin/orders/${encodeURIComponent(n)}/status`, { method: 'POST', body: { status, note } }),
  orderNote: (n, internal_note) => http(`/admin/orders/${encodeURIComponent(n)}/note`, { method: 'PUT', body: { internal_note } }),
  refund: (n, note) => http(`/admin/orders/${encodeURIComponent(n)}/refund`, { method: 'POST', body: { note } }),
  waybillUrl: (n) => `/api/admin/orders/${encodeURIComponent(n)}/waybill`,

  products: (f) => http(`/admin/products${qs(f)}`),
  product: (id) => http(`/admin/products/${id}`),
  saveProduct: (body, id) => http(id ? `/admin/products/${id}` : '/admin/products', { method: id ? 'PUT' : 'POST', body }),
  deleteProduct: (id) => http(`/admin/products/${id}`, { method: 'DELETE' }),
  deleteImage: (id, imageId) => http(`/admin/products/${id}/images/${imageId}`, { method: 'DELETE' }),
  sortImages: (id, ids) => http(`/admin/products/${id}/images/order`, { method: 'PUT', body: { ids } }),
  async uploadImage(id, file, alt = '') {
    // multipart: the JSON client cannot send files
    await ensureCsrf()
    const xsrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
    const fd = new FormData()
    fd.append('image', file)
    if (alt) fd.append('alt', alt)
    const res = await fetch(`/api/admin/products/${id}/images`, {
      method: 'POST',
      body: fd,
      credentials: 'include',
      headers: { Accept: 'application/json', ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf[1]) } : {}) },
    })
    const json = await res.json().catch(() => null)
    if (!res.ok) throw Object.assign(new Error(json?.message || 'تعذّر رفع الصورة.'), { status: res.status, errors: json?.errors || {} })
    return json
  },
  lowStock: () => http('/admin/stock/low'),
  adjustStock: (variantId, change, reason, note) => http(`/admin/variants/${variantId}/stock`, { method: 'POST', body: { change, reason, note } }),
  movements: (variantId) => http(`/admin/variants/${variantId}/movements`),

  categories: () => http('/admin/categories'),
  saveCategory: (id, body) => http(`/admin/categories/${id}`, { method: 'PUT', body }),
  saveSpotlight: (body, id) => http(id ? `/admin/spotlights/${id}` : '/admin/spotlights', { method: id ? 'PUT' : 'POST', body }),
  deleteSpotlight: (id) => http(`/admin/spotlights/${id}`, { method: 'DELETE' }),

  governorates: () => http('/admin/governorates'),
  saveGovernorates: (rows) => http('/admin/governorates', { method: 'PUT', body: { rows } }),
  settings: () => http('/admin/settings'),
  saveSettings: (body) => http('/admin/settings', { method: 'PUT', body }),
  finder: () => http('/admin/finder'),
  saveFinderAnswer: (id, body) => http(`/admin/finder/answers/${id}`, { method: 'PUT', body }),

  coupons: (f) => http(`/admin/coupons${qs(f)}`),
  saveCoupon: (body, id) => http(id ? `/admin/coupons/${id}` : '/admin/coupons', { method: id ? 'PUT' : 'POST', body }),
  deleteCoupon: (id) => http(`/admin/coupons/${id}`, { method: 'DELETE' }),

  customers: (f) => http(`/admin/customers${qs(f)}`),
  customer: (id) => http(`/admin/customers/${id}`),

  reports: (f) => http(`/admin/reports${qs(f)}`),
  ordersCsvUrl: (f) => `/api/admin/reports/orders.csv${qs(f)}`,
}

export const STATUS = {
  pending_payment: 'بانتظار الدفع',
  placed: 'جديد',
  confirmed: 'مؤكد',
  preparing: 'قيد التجهيز',
  shipped: 'تم الشحن',
  delivered: 'تم التوصيل',
  cancelled: 'ملغي',
  returned: 'مرتجع',
}
export const PAY = { cod: 'عند الاستلام', card: 'بطاقة', wallet: 'محفظة' }
export const PAY_STATUS = { unpaid: 'غير مدفوع', pending: 'معلّق', paid: 'مدفوع', failed: 'فشل', refunded: 'مسترد' }
