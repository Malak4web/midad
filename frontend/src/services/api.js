// Every call the SPA makes to the Laravel API, in one place (INT-01).
import { http } from './http'

const qs = (params = {}) => {
  const s = new URLSearchParams(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '')).toString()
  return s ? `?${s}` : ''
}

export const api = {
  categories: () => http('/categories'),
  category: (slug, params, signal) => http(`/categories/${encodeURIComponent(slug)}${qs(params)}`, { signal }),
  products: (params, signal) => http(`/products${qs(params)}`, { signal }),
  product: (slug, signal) => http(`/products/${encodeURIComponent(slug)}`, { signal }),
  search: (q, limit, signal) => http(`/search${qs({ q, limit })}`, { signal }),
  notes: () => http('/notes'),
  finderQuestions: () => http('/finder/questions'),
  finderResult: (answers) => http('/finder/result', { method: 'POST', body: { answers } }),
  governorates: () => http('/governorates'),

  cart: {
    get: (governorateId) => http(`/cart${qs({ governorate_id: governorateId })}`),
    add: (slug, ml, quantity = 1) => http('/cart/items', { method: 'POST', body: { slug, size_ml: ml, quantity } }),
    addBox: (productIds) => http('/cart/discovery-box', { method: 'POST', body: { product_ids: productIds } }),
    update: (id, quantity) => http(`/cart/items/${id}`, { method: 'PATCH', body: { quantity } }),
    remove: (id) => http(`/cart/items/${id}`, { method: 'DELETE' }),
    coupon: (code) => http('/cart/coupon', { method: 'POST', body: { code } }),
    removeCoupon: () => http('/cart/coupon', { method: 'DELETE' }),
    gift: (wrap, recipient, message) => http('/cart/gift', { method: 'PUT', body: { wrap, recipient, message } }),
  },

  auth: {
    me: () => http('/auth/me'),
    login: (email, password) => http('/auth/login', { method: 'POST', body: { email, password, remember: true } }),
    register: (body) => http('/auth/register', { method: 'POST', body }),
    logout: () => http('/auth/logout', { method: 'POST' }),
    forgot: (email) => http('/auth/forgot-password', { method: 'POST', body: { email } }),
    reset: (body) => http('/auth/reset-password', { method: 'POST', body }),
    verify: (path) => http(path),
    profile: (body) => http('/auth/profile', { method: 'PATCH', body }),
  },

  checkout: (body) => http('/checkout', { method: 'POST', body }),
  order: (number, phone) => http(`/orders/${encodeURIComponent(number)}${qs({ phone })}`),
  pay: (number, phone) => http(`/orders/${encodeURIComponent(number)}/pay`, { method: 'POST', body: { phone } }),
  sandbox: (ref) => http(`/payments/sandbox/${encodeURIComponent(ref)}`),
  sandboxComplete: (ref, result) => http(`/payments/sandbox/${encodeURIComponent(ref)}`, { method: 'POST', body: { result } }),

  account: {
    orders: (page = 1) => http(`/account/orders${qs({ page })}`),
    addresses: () => http('/account/addresses'),
    saveAddress: (body, id) => http(id ? `/account/addresses/${id}` : '/account/addresses', { method: id ? 'PUT' : 'POST', body }),
    deleteAddress: (id) => http(`/account/addresses/${id}`, { method: 'DELETE' }),
  },
}
