import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'

// TST-22: axe on every storefront page and every admin page, WCAG 2.2 AA rules.
const STORE = ['/', '/c/men', '/c/women', '/p/her-blossom', '/search?q=عود', '/discovery', '/finder', '/gifts', '/cart', '/checkout', '/track', '/login', '/register', '/forgot-password', '/about', '/help/faq', '/legal/privacy', '/no-such-page']
const ADMIN = ['/admin', '/admin/orders', '/admin/products', '/admin/products/1', '/admin/products/new', '/admin/stock', '/admin/spotlights', '/admin/coupons', '/admin/customers', '/admin/reports', '/admin/shipping', '/admin/settings']

async function scan(page, path) {
  await page.goto(path)
  await page.waitForLoadState('networkidle')
  const { violations } = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze()
  return violations.map((v) => `${path} · ${v.id} (${v.impact}) × ${v.nodes.length}: ${v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(' | ')}`)
}

test('storefront pages have no axe violations', async ({ page }) => {
  test.setTimeout(180_000)
  // a cart line so the cart and checkout show their real state
  await page.goto('/p/her-blossom')
  await page.getByRole('button', { name: 'أضف إلى السلة' }).click()
  await expect(page.getByRole('status')).toContainText('أُضيف')
  const found = []
  for (const path of STORE) found.push(...(await scan(page, path)))
  expect(found, found.join('\n')).toEqual([])
})

test('admin pages have no axe violations', async ({ page }) => {
  test.setTimeout(180_000)
  await page.goto('/login')
  await page.getByLabel(/البريد/).fill('admin@midad.local')
  await page.getByLabel(/كلمة المرور/).fill('Midad-Dev-2026')
  await page.getByRole('button', { name: /دخول/ }).click()
  await page.waitForURL((u) => !u.pathname.startsWith('/login'))
  const found = []
  for (const path of ADMIN) found.push(...(await scan(page, path)))
  expect(found, found.join('\n')).toEqual([])
})

test('@mobile storefront on a phone has no axe violations', async ({ page }) => {
  test.setTimeout(120_000)
  const found = []
  for (const path of ['/', '/p/her-blossom', '/discovery', '/checkout']) found.push(...(await scan(page, path)))
  // the open sheets (shop, cart) are their own surfaces
  await page.goto('/')
  for (const [tab, name] of [['تسوّق', 'shop'], [/^السلة،/, 'cart']]) {
    await page.getByRole('navigation', { name: 'التنقل الرئيسي' }).getByRole('button', { name: tab }).click()
    await page.getByRole('dialog').waitFor()
    await page.waitForTimeout(450)
    const { violations } = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze()
    found.push(...violations.map((v) => `${name} sheet · ${v.id} × ${v.nodes.length}: ${v.nodes[0].target.join(' ')}`))
    await page.keyboard.press('Escape')
    await page.getByRole('dialog').waitFor({ state: 'hidden' })
  }
  expect(found, found.join('\n')).toEqual([])
})
