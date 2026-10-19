const num = new Intl.NumberFormat('ar-EG')

export const formatNumber = (n) => num.format(n)
export const formatPrice = (n) => `${num.format(n)} ج.م`
export const formatMl = (ml) => `${num.format(ml)} مل`
