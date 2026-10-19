// WCAG 2.x contrast. Spotlight palettes come from the admin, so text color is
// verified at render time and the admin editor blocks unreadable palettes (FE-41).

function channel(c) {
  const v = c / 255
  return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4
}

export function hexToRgb(hex) {
  const h = hex.replace('#', '')
  const full = h.length === 3 ? h.split('').map((x) => x + x).join('') : h
  const n = parseInt(full, 16)
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255]
}

export function luminance(hex) {
  const [r, g, b] = hexToRgb(hex)
  return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b)
}

export function contrastRatio(a, b) {
  const [l1, l2] = [luminance(a), luminance(b)].sort((x, y) => y - x)
  return (l1 + 0.05) / (l2 + 0.05)
}

// Returns fg if it passes `min` on bg, otherwise the readable one of ink/ivory.
export function readableOn(bg, fg, min = 4.5) {
  if (contrastRatio(bg, fg) >= min) return fg
  return contrastRatio(bg, '#0E0D0C') >= contrastRatio(bg, '#F2EADB') ? '#0E0D0C' : '#F2EADB'
}
