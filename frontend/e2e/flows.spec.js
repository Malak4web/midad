import { expect, test } from '@playwright/test'

// the Attar's answer chips: exact text, so «هدية له» never matches «هدية لها»
const chip = (page, label) => page.locator('.chip', { hasText: new RegExp(`^\s*${label}\s*$`) })

async function converse(page, labels) {
  for (const l of labels) {
    await chip(page, l).waitFor({ timeout: 20_000 })
    await chip(page, l).click()
  }
  await page.locator('.pick').waitFor({ timeout: 30_000 })
  return (await page.locator('.pick h3').textContent()).trim()
}

test('TST-16 the Attar asks, listens, and recommends what the server ranks; he remembers a returning visitor', async ({ page, request }) => {
  test.setTimeout(120_000)
  await page.goto('/finder')
  const log = page.locator('ol[aria-live="polite"]')
  await expect(log).toContainText('أنا خبير العطور', { timeout: 15_000 })

  // he asks a name, and uses it
  await page.getByPlaceholder('اسمك الأول').fill('سارة')
  await page.getByRole('button', { name: 'تفضّل' }).click()
  await expect(log).toContainText('تشرّفت يا سارة')

  const pick = await converse(page, ['هدية لها', 'النهار والعمل', 'زهري وناعم'])
  await expect(log).toContainText('يا سارة، هذا عطرك')
  // his pick is the server's first for the same answers
  const api = await request.post('/api/finder/result', { data: { answers: { who: 'her', when: 'day', mood: 'floral' } } })
  expect(pick).toBe((await api.json()).data[0].product.name)
  // each answer got its own reply
  await expect(log).toContainText('هديةٌ لها')
  await expect(log).toContainText('الزهور')

  // adding from his card
  await page.locator('.pick').getByRole('button', { name: /أضف/ }).click()
  await expect(page.getByRole('status').first()).toContainText('أُضيف')
  await expect(log).toContainText('اختيارٌ موفّق')

  // a new visit: he greets her by name and recalls the last pick
  await page.reload()
  await expect(log).toContainText('أهلاً بعودتك', { timeout: 15_000 })
  await expect(log).toContainText(`«${pick}»`)
})

async function adminLogin(page) {
  await page.goto('/login')
  await page.getByLabel(/البريد/).fill('admin@midad.local')
  await page.getByLabel(/كلمة المرور/).fill('Midad-Dev-2026')
  await page.getByRole('button', { name: /دخول/ }).click()
  await page.waitForURL((u) => !u.pathname.startsWith('/login'))
}

test('TST-17 admin adds a perfume, features it, and the homepage shows it in its own identity', async ({ page }) => {
  test.setTimeout(90_000)
  const name = 'ليلة اختبار'
  await adminLogin(page)

  await page.goto('/admin/products/new')
  await page.getByLabel(/الاسم بالعربي/).fill(name)
  await page.getByLabel(/الاسم اللاتيني/).fill('Test Night')
  await page.getByLabel('مستوحى من').fill('عطر للاختبار')
  await page.getByLabel('النوتة الأبرز').fill('ياسمين ومسك')
  await page.getByLabel('القسم').selectOption({ label: 'نسائي' })
  for (const [ml, price] of [[30, '700'], [50, '1000'], [100, '1500']]) {
    await page.getByLabel(`سعر ${ml} مل`).fill(price)
    await page.getByLabel(`مخزون ${ml} مل`).fill('10')
  }
  await page.getByRole('button', { name: 'إنشاء العطر' }).click()
  await page.waitForURL(/\/admin\/products\/\d+$/)

  await page.goto('/admin/spotlights')
  await page.getByRole('tab', { name: /نسائي/ }).click()
  await page.getByLabel('العطر المميز').selectOption({ label: name })
  await page.getByLabel(/الجملة التعريفية/).fill('ياسمين الليل على جلدٍ دافئ.')
  await page.getByRole('button', { name: 'حفظ ونشر في الرئيسية' }).click()
  await expect(page.getByRole('status')).toBeVisible()

  // the homepage spotlight for women is now this perfume, with its tagline;
  // it was saved without a scent pyramid, and the page must still render whole
  await page.goto('/')
  await expect(page.locator('h3', { hasText: name })).toBeVisible()
  await expect(page.getByText('ياسمين الليل على جلدٍ دافئ.')).toBeVisible()
  await expect(page.getByRole('heading', { level: 2, name: 'رجالي' })).toBeVisible()
  await expect(page.getByRole('heading', { level: 2, name: 'شرقي ونيش' })).toBeVisible()

  // and it is a real product: its page sells it
  await page.goto('/p/test-night')
  await expect(page.getByRole('heading', { level: 1, name })).toBeVisible()
  await expect(page.getByRole('button', { name: 'أضف إلى السلة' })).toBeEnabled()
  await expect(page.locator('main')).not.toContainText(/undefined|null/)
})

test('TST-18 admin confirms an order and the customer sees the new status', async ({ page, browser }) => {
  test.setTimeout(90_000)
  // the customer, as a guest
  const customer = await (await browser.newContext()).newPage()
  await customer.goto('/p/her-blossom')
  await customer.getByRole('button', { name: 'أضف إلى السلة' }).click()
  await expect(customer.getByRole('status')).toContainText('أُضيف')
  await customer.goto('/checkout')
  await customer.getByLabel('الاسم بالكامل').fill('منى علي')
  await customer.getByLabel('رقم الموبايل').fill('01155555555')
  await customer.getByLabel('المحافظة').selectOption({ label: 'الجيزة' })
  await customer.getByRole('textbox', { name: 'العنوان', exact: true }).fill('شارع التحرير ٥')
  await customer.locator('label.pay', { hasText: 'الدفع عند الاستلام' }).click()
  await customer.getByRole('button', { name: 'تأكيد الطلب' }).click()
  await expect(customer.getByRole('heading', { level: 1, name: 'شكراً، وصلنا طلبك' })).toBeVisible()
  const number = decodeURIComponent(new URL(customer.url()).pathname.split('/').pop())
  expect(number).toMatch(/^MD-/)

  // the admin confirms it
  await adminLogin(page)
  await page.goto(`/admin/orders/${number}`)
  await page.getByRole('button', { name: 'تأكيد الطلب' }).click()
  await expect(page.getByRole('status')).toBeVisible()
  await expect(page.getByText('مؤكد').first()).toBeVisible()

  // the customer tracks it and sees "confirmed" as the current step
  await customer.goto('/track')
  await customer.getByLabel(/رقم الطلب/).fill(number)
  await customer.getByLabel(/رقم الموبايل المستخدم/).fill('01155555555')
  await customer.getByRole('button', { name: 'عرض الطلب' }).click()
  await expect(customer.locator('[aria-current="step"]')).toHaveText('تم التأكيد')
})
