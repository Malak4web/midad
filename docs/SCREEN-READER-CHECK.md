# Manual screen-reader pass (TST-23)

Automated checks already cover axe rules on every page, keyboard-only buying,
focus visibility, the cart dialog's focus trap, and 24×24 tap targets
(`frontend/e2e/a11y.spec.js`, `frontend/e2e/keyboard.spec.js`). What only a person
can judge is how the store *sounds*. Run this on staging, about 30 minutes per device.

**Devices:** an iPhone with VoiceOver, and a mid-range Android with TalkBack.
Set the phone language to Arabic so the reader uses its Arabic voice.

Mark each line ✓ or write what you heard.

## Home
- [ ] The page title is read as «مداد | عطرٌ يُكتَب، فيبقى».
- [ ] Swiping from the top reaches the main content quickly (skip link, then the headline).
- [ ] Each category section announces its name as a heading (رجالي، حريمي، شرقي ونيش).
- [ ] The featured perfume's name, its tagline, and the three pyramid tiers read in order.
- [ ] The slider announces itself as «سلايدر» with its title, and each card as «١ من ١٠».
- [ ] The decorative waves, the pen line and the drawn ingredients are silent.

## Product
- [ ] Name, then the original it is inspired by, then the price, in that order.
- [ ] The sizes read as a group of choices with their price; a sold-out size says «نفد».
- [ ] After «أضف إلى السلة», the confirmation is announced without moving focus.

## Cart and checkout
- [ ] The cart opens as a dialog named «السلة»; swiping does not leave it until it closes.
- [ ] Quantity buttons say what they change («زيادة كمية …» / «إنقاص كمية …» with the perfume name).
- [ ] Each checkout field reads its label; required fields say so.
- [ ] Submitting empty reads the error message, and each field's error is read on that field.
- [ ] The governorate list reads the governorate names; picking one announces «الشحن …، الإجمالي …».
- [ ] The confirmation page heading «شكراً، وصلنا طلبك» is read first.

## Discovery box and finder
- [ ] Each sample says whether it is chosen; the count («٣ / ٦») is announced as it changes.
- [ ] Choosing a seventh sample announces «اخترت ستّة بالفعل…».
- [ ] The finder's questions read as groups with their options; results are announced when they appear.

## Admin (one pass on a laptop with NVDA or VoiceOver is enough)
- [ ] The order's next action buttons read the action, not just an icon.
- [ ] Confirming a cancellation is read as an alert dialog.

Report: device, OS version, reader, and every line that failed with what was heard instead.
