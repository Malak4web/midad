import { expect, test } from '@playwright/test'

// TST-23 (the automatable half): keyboard-only shopping, visible focus, and touch
// target sizes. Screen readers (TalkBack, VoiceOver in Arabic) stay a manual pass.

test('skip link is the first stop and lands in the content', async ({ page }) => {
  await page.goto('/c/women')
  await page.keyboard.press('Tab')
  const skip = page.getByRole('link', { name: 'تخطَّ إلى المحتوى' })
  await expect(skip).toBeFocused()
  await expect(skip).toBeVisible()
  await page.keyboard.press('Enter')
  await expect(page).toHaveURL(/#main$/)
})

test('every focus stop on the homepage shows a visible ring', async ({ page }) => {
  await page.goto('/')
  await page.waitForLoadState('networkidle')
  const invisible = []
  for (let i = 0; i < 40; i++) {
    await page.keyboard.press('Tab')
    const r = await page.evaluate(() => {
      const el = document.activeElement
      if (!el || el === document.body) return null
      const cs = getComputedStyle(el)
      const ring = (cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0) || cs.boxShadow !== 'none'
      // components may draw focus on a wrapper (:has(:focus-visible)) instead
      const wrap = el.closest('label, .size, .chip')
      const wrapRing = wrap && (getComputedStyle(wrap).outlineStyle !== 'none' || getComputedStyle(wrap).boxShadow !== 'none')
      return ring || wrapRing ? null : `${el.tagName.toLowerCase()} «${(el.getAttribute('aria-label') || el.textContent).trim().slice(0, 30)}»`
    })
    if (r) invisible.push(r)
  }
  expect(invisible, invisible.join('\n')).toEqual([])
})

test('search, choose a size and buy with the keyboard only; the cart dialog traps and returns focus', async ({ page }) => {
  await page.goto('/')
  // the header's field (the home hero has a second one)
  const search = page.getByRole('banner').getByRole('combobox')
  await search.focus()
  await page.keyboard.type('عود')
  await expect(page.getByRole('option').first()).toBeVisible()
  await page.keyboard.press('ArrowDown')
  await expect(search).toHaveAttribute('aria-activedescendant', /.+/)
  await page.keyboard.press('Enter')
  await expect(page).toHaveURL(/\/p\//)

  // sizes are a radio group: arrows move the choice
  const fifty = page.getByRole('radio', { name: /٥٠ مل/ })
  await fifty.focus()
  await page.keyboard.press('ArrowRight')
  await expect(fifty).not.toBeChecked()
  await page.keyboard.press('ArrowLeft')
  await expect(fifty).toBeChecked()

  const add = page.getByRole('button', { name: 'أضف إلى السلة' })
  await add.focus()
  await page.keyboard.press('Enter')
  await expect(page.getByRole('status')).toContainText('أُضيف')

  const cartBtn = page.getByRole('button', { name: /^السلة،/ })
  await cartBtn.focus()
  await page.keyboard.press('Enter')
  const dialog = page.getByRole('dialog', { name: 'السلة' })
  await expect(dialog).toBeVisible()
  await expect(dialog).toBeFocused()
  for (let i = 0; i < 15; i++) {
    await page.keyboard.press('Tab')
    expect(await dialog.evaluate((d) => d.contains(document.activeElement))).toBe(true)
  }
  await page.keyboard.press('Escape')
  await expect(dialog).toBeHidden()
  await expect(cartBtn).toBeFocused()
})

test('@mobile the tab bar sheets open and close from the keyboard, and hand focus back', async ({ page }) => {
  await page.goto('/')
  const bar = page.getByRole('navigation', { name: 'التنقل الرئيسي' })
  const shop = bar.getByRole('button', { name: 'تسوّق' })
  await shop.focus()
  await page.keyboard.press('Enter')
  const sheet = page.getByRole('dialog', { name: 'تسوّق' })
  await expect(sheet).toBeVisible()
  await expect(shop).toHaveAttribute('aria-expanded', 'true')
  for (let i = 0; i < 12; i++) {
    await page.keyboard.press('Tab')
    expect(await sheet.evaluate((d) => d.contains(document.activeElement))).toBe(true)
  }
  await page.keyboard.press('Escape')
  await expect(sheet).toBeHidden()
  await expect(shop).toBeFocused()

  // search opens on its field
  await bar.getByRole('button', { name: 'بحث' }).click()
  await expect(page.getByRole('dialog').getByRole('combobox')).toBeFocused()
})

test('@mobile tap targets are at least 24×24 (WCAG 2.5.8)', async ({ page }) => {
  const small = []
  for (const path of ['/', '/p/her-blossom', '/discovery', '/checkout']) {
    await page.goto(path)
    await page.waitForLoadState('networkidle')
    small.push(...(await page.evaluate((p) => {
      const out = []
      for (const el of document.querySelectorAll('a[href], button, input:not([type=hidden]), select, textarea, [role=button], label:has(input.sr-only)')) {
        if (el.matches('input.sr-only, .sr-only-focusable')) continue // the skip link only exists while focused
        const r = el.getBoundingClientRect()
        if (!r.width || !r.height || getComputedStyle(el).visibility === 'hidden') continue
        // inline links inside running text are exempt in 2.5.8
        if (el.tagName === 'A' && getComputedStyle(el).display === 'inline' && el.parentElement.closest('p')) continue
        if (r.width < 24 || r.height < 24) out.push(`${p} ${el.tagName.toLowerCase()} «${(el.getAttribute('aria-label') || el.textContent).trim().slice(0, 24)}» ${Math.round(r.width)}×${Math.round(r.height)}`)
      }
      return out
    }, path)))
  }
  expect(small, small.join('\n')).toEqual([])
})
