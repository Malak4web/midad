import { expect, test } from '@playwright/test'

const address = async (page, { payment = 'cod', phone = '01012345678' } = {}) => {
  await page.getByLabel('الاسم بالكامل').fill('سارة أحمد')
  await page.getByLabel('رقم الموبايل').fill(phone)
  await page.getByLabel('المحافظة').selectOption({ label: 'القاهرة' })
  await page.getByRole('textbox', { name: 'العنوان', exact: true }).fill('شارع عباس العقاد ١٢')
  const label = { cod: 'الدفع عند الاستلام', card: 'بطاقة بنكية', wallet: 'محفظة إلكترونية' }[payment]
  await page.locator('label.pay', { hasText: label }).click()
}

test('TST-12 guest buys with cash on delivery from category to confirmation', async ({ page }) => {
  await page.goto('/c/men')
  await expect(page.getByRole('heading', { level: 1, name: 'رجالي' })).toBeVisible()
  await page.getByRole('link', { name: 'وانتد نايت' }).click()
  await expect(page.getByRole('heading', { level: 1, name: 'وانتد نايت' })).toBeVisible()
  await page.getByText('٣٠ مل', { exact: true }).click()
  await page.getByRole('button', { name: 'أضف إلى السلة' }).click()
  await expect(page.getByRole('status')).toContainText('أُضيف وانتد نايت')

  await page.goto('/checkout')
  await address(page)
  await expect(page.locator('aside[aria-label="ملخص الطلب"]')).toContainText('٦٠ ج.م') // Cairo shipping, placeholder rate
  await page.getByRole('button', { name: 'تأكيد الطلب' }).click()
  await expect(page.getByRole('heading', { level: 1, name: 'شكراً، وصلنا طلبك' })).toBeVisible()
  await expect(page.getByText('تم استلام الطلب').first()).toBeVisible()
})

test('TST-13 card payment through the sandbox, then a failed one', async ({ page }) => {
  await page.goto('/p/lady-rose')
  await page.getByRole('button', { name: 'أضف إلى السلة' }).click()
  await expect(page.getByRole('status')).toContainText('أُضيف')
  await page.goto('/checkout')
  await address(page, { payment: 'card' })
  await page.getByRole('button', { name: 'المتابعة للدفع' }).click()
  await expect(page.getByRole('heading', { name: 'ادفع طلبك' })).toBeVisible()
  await page.getByRole('button', { name: 'محاكاة دفع ناجح' }).click()
  await expect(page.getByText('بطاقة بنكية · مدفوع')).toBeVisible()

  await page.goto('/p/paris-glow')
  await page.getByRole('button', { name: 'أضف إلى السلة' }).click()
  await expect(page.getByRole('status')).toContainText('أُضيف')
  await page.goto('/checkout')
  await address(page, { payment: 'wallet', phone: '01112345678' })
  await page.getByRole('button', { name: 'المتابعة للدفع' }).click()
  await page.getByRole('button', { name: 'محاكاة رفض البطاقة' }).click()
  await expect(page.getByText('لم يكتمل الدفع')).toBeVisible()
  await expect(page.getByRole('button', { name: 'ادفع الآن' })).toBeVisible()
})

test('TST-14 discovery box: six picks, one box in the cart', async ({ page }) => {
  await page.goto('/discovery')
  const picks = page.locator('button.pick')
  await expect(picks.first()).toBeVisible()
  const add = page.getByRole('button', { name: /أضف العلبة/ })
  for (let i = 0; i < 5; i++) await picks.nth(i).click()
  await expect(add).toBeDisabled()
  await picks.nth(5).click()
  await add.click()
  await expect(page.getByRole('status')).toContainText('أُضيفت مجموعة الاكتشاف')
  await page.goto('/cart')
  await expect(page.locator('main').getByText('مجموعة الاكتشاف', { exact: true })).toBeVisible()
})

test('TST-15 gift card travels with the order', async ({ page }) => {
  await page.goto('/gifts')
  // for her, a perfume, the card, then the box closes
  await page.getByRole('button', { name: /^لها/ }).click()
  await page.getByRole('button', { name: 'التالي' }).click()
  await page.locator('.plist button').first().click()
  await page.getByRole('button', { name: 'التالي' }).click()
  await page.getByLabel('إلى').fill('نور')
  await page.getByLabel(/رسالتك/).fill('كل عام وأنتِ الأثر')
  await expect(page.locator('figure[aria-label="معاينة الهدية"]')).toContainText('كل عام وأنتِ الأثر')
  await page.getByRole('button', { name: 'اقفل العلبة' }).click()
  await page.getByRole('button', { name: 'أضف الهدية إلى السلة' }).click()
  await expect(page.getByRole('status')).toContainText('أُضيفت الهدية')
  await page.goto('/checkout')
  await expect(page.getByText('هدية إلى نور')).toBeVisible()
})

test('search by the original perfume name', async ({ page }) => {
  await page.goto('/search?q=بلو دي شانيل')
  await expect(page.getByRole('link', { name: 'بلو رويال' })).toBeVisible()
})

test('register, then see the account page', async ({ page }) => {
  await page.goto('/register')
  await page.getByLabel(/^الاسم/).fill('عمر')
  await page.getByLabel('البريد الإلكتروني').fill(`omar${Date.now()}@example.com`)
  await page.getByLabel('رقم الموبايل').fill('01223456789')
  await page.getByLabel(/^كلمة المرور/).fill('midad2026')
  await page.getByLabel('تأكيد كلمة المرور').fill('midad2026')
  await page.getByRole('button', { name: 'إنشاء الحساب' }).click()
  await expect(page.getByRole('heading', { level: 1, name: 'أهلاً، عمر' })).toBeVisible()
  await expect(page.getByText('لا توجد طلبات بعد')).toBeVisible()
})

test('@mobile homepage and cart drawer on a phone', async ({ page }) => {
  await page.goto('/')
  await expect(page.getByRole('heading', { level: 1, name: /عطرٌ يُكتَب/ })).toBeVisible()
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)
  expect(overflow).toBeLessThanOrEqual(0)
  await page.getByRole('button', { name: /^السلة،/ }).click()
  await expect(page.getByRole('dialog', { name: 'السلة' })).toBeVisible()
})

test('@mobile the phone shell: buy bar on the product, cart sheet, pay bar in checkout', async ({ page }) => {
  await page.goto('/p/her-blossom')
  const bar = page.locator('.buybar')
  // the phone product screen keeps the price and the add button in reach the whole time
  await expect(bar).toBeVisible()
  await page.evaluate(() => scrollTo(0, 1600))
  await expect(bar).toBeVisible()
  await bar.getByRole('button', { name: /أضف/ }).click()
  await expect(page.getByRole('status')).toContainText('أُضيف')

  const tabs = page.getByRole('navigation', { name: 'التنقل الرئيسي' })
  await tabs.getByRole('button', { name: /^السلة، ١/ }).click()
  const sheet = page.getByRole('dialog', { name: 'السلة' })
  await expect(sheet).toBeVisible()
  await sheet.getByRole('link', { name: 'إتمام الطلب' }).click()

  await expect(page).toHaveURL(/\/checkout$/)
  await expect(tabs).toHaveCount(0) // checkout owns the bottom of the screen
  await expect(page.locator('.paybar')).toContainText('الإجمالي')
})
