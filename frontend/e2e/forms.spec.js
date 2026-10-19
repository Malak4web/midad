import { expect, test } from '@playwright/test'

// TST-11: forms say what is wrong, where, in Arabic, and do not double-submit.
const field = (page, label) => page.getByLabel(label, { exact: false })

test('checkout: empty submit marks every required field, then a bad phone is explained', async ({ page }) => {
  await page.goto('/p/her-blossom')
  await page.getByRole('button', { name: 'أضف إلى السلة' }).click()
  await expect(page.getByRole('status')).toContainText('أُضيف')
  await page.goto('/checkout')

  await page.getByRole('button', { name: 'تأكيد الطلب' }).click()
  await expect(page.getByRole('alert')).toHaveText('راجع الحقول المعلّمة بالأحمر.')
  for (const label of ['الاسم بالكامل', 'رقم الموبايل', 'العنوان']) {
    await expect(field(page, label)).toHaveAttribute('aria-invalid', 'true')
  }
  await expect(page.getByLabel('المحافظة')).toHaveAttribute('aria-invalid', 'true')

  await field(page, 'الاسم بالكامل').fill('سارة أحمد')
  await field(page, 'رقم الموبايل').fill('0123')
  await page.getByLabel('المحافظة').selectOption({ label: 'القاهرة' })
  await page.getByRole('textbox', { name: 'العنوان', exact: true }).fill('شارع ١')
  await page.getByRole('button', { name: 'تأكيد الطلب' }).click()
  const phone = field(page, 'رقم الموبايل')
  await expect(phone).toHaveAttribute('aria-invalid', 'true')
  await expect(page.locator(`#${await phone.getAttribute('aria-describedby')}`)).toHaveText('اكتب رقم موبايل مصري صحيح من ١١ رقماً يبدأ بـ 01.')
  await expect(field(page, 'الاسم بالكامل')).toHaveAttribute('aria-invalid', 'false')
})

test('checkout: Arabic digits and +20 are accepted, and the button cannot double-submit', async ({ page }) => {
  await page.goto('/p/her-blossom')
  await page.getByRole('button', { name: 'أضف إلى السلة' }).click()
  await expect(page.getByRole('status')).toContainText('أُضيف')
  await page.goto('/checkout')
  await field(page, 'الاسم بالكامل').fill('سارة أحمد')
  await field(page, 'رقم الموبايل').fill('+٢٠ ١٠ ١٢٣٤ ٥٦٧٨')
  await page.getByLabel('المحافظة').selectOption({ label: 'القاهرة' })
  await page.getByRole('textbox', { name: 'العنوان', exact: true }).fill('شارع ١')

  // slow the order request down to see the loading state
  let orders = 0
  await page.route('**/api/checkout', async (route) => {
    orders++
    await new Promise((r) => setTimeout(r, 800))
    await route.continue()
  })
  const btn = page.getByRole('button', { name: 'تأكيد الطلب' })
  await btn.click()
  const busy = page.getByRole('button', { name: 'جارٍ تسجيل الطلب…' })
  await expect(busy).toBeDisabled()
  await busy.click({ force: true }).catch(() => {})
  await expect(page.getByRole('heading', { level: 1, name: 'شكراً، وصلنا طلبك' })).toBeVisible()
  expect(orders).toBe(1)
  expect(page.url()).toContain('phone=01012345678') // normalized on the server
})

test('register: server errors land on their fields', async ({ page }) => {
  await page.goto('/register')
  await field(page, 'الاسم').first().fill('منى')
  await field(page, 'البريد الإلكتروني').fill('not-an-email')
  await field(page, 'رقم الموبايل').fill('123')
  await field(page, 'كلمة المرور').first().fill('short')
  await page.getByRole('button', { name: /إنشاء الحساب|حساب جديد|تسجيل/ }).click()
  for (const label of ['البريد الإلكتروني', 'رقم الموبايل']) {
    await expect(field(page, label)).toHaveAttribute('aria-invalid', 'true')
  }
  await expect(field(page, 'كلمة المرور').first()).toHaveAttribute('aria-invalid', 'true')
})
