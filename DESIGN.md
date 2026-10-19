---
name: Midad
description: A calligraphy-rooted Arabic perfume house; the storefront reads as one continuous painted scroll, written in ink.
colors:
  midad-black: "#0e0d0c"
  smoked-glass: "#3b342c"
  smoked-glass-deep: "#2c2620"
  gold-leaf: "#b08d57"
  light-gold: "#d6b77e"
  gold-ink: "#765a30"
  parchment-ivory: "#f2eadb"
  paper: "#f8f3e8"
  sand: "#d8cab0"
  lead-smoke: "#3d362e"
  muted: "#5f564b"
  muted-light: "#a89c8a"
  danger: "#a0462e"
typography:
  display:
    fontFamily: "Amiri, 'Traditional Arabic', serif"
    fontSize: "clamp(3.25rem, 8.4vw, 6rem)"
    fontWeight: 700
    lineHeight: 1.08
  headline:
    fontFamily: "Amiri, 'Traditional Arabic', serif"
    fontSize: "clamp(2.5rem, 5vw, 4rem)"
    fontWeight: 700
    lineHeight: 1
  title:
    fontFamily: "Amiri, 'Traditional Arabic', serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.25
  tagline:
    fontFamily: "Amiri, 'Traditional Arabic', serif"
    fontSize: "clamp(1.375rem, 2.4vw, 1.875rem)"
    fontWeight: 400
    lineHeight: 1.375
  body:
    fontFamily: "'IBM Plex Sans Arabic', 'Segoe UI', Tahoma, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.7
  lead:
    fontFamily: "'IBM Plex Sans Arabic', 'Segoe UI', Tahoma, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.9
  label:
    fontFamily: "'IBM Plex Sans Arabic', 'Segoe UI', Tahoma, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  latin-caps:
    fontFamily: "Cinzel, 'Trajan Pro', Georgia, serif"
    fontSize: "0.6875rem"
    fontWeight: 400
    lineHeight: 1.4
    letterSpacing: "0.18em"
  data:
    fontFamily: "Montserrat, 'Helvetica Neue', Arial, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 400
    lineHeight: 1.4
    letterSpacing: "0.22em"
rounded:
  none: "0px"
  focus: "2px"
  pill: "9999px"
spacing:
  xs: "8px"
  sm: "12px"
  md: "16px"
  lg: "24px"
  xl: "40px"
  section: "64px"
  section-lg: "96px"
  gutter: "clamp(16px, 4vw, 48px)"
  container: "1280px"
components:
  button-primary:
    backgroundColor: "{colors.midad-black}"
    textColor: "{colors.parchment-ivory}"
    rounded: "{rounded.none}"
    padding: "14px 28px"
  button-primary-hover:
    backgroundColor: "{colors.smoked-glass}"
    textColor: "{colors.parchment-ivory}"
  button-gold:
    backgroundColor: "{colors.light-gold}"
    textColor: "{colors.midad-black}"
    rounded: "{rounded.none}"
    padding: "14px 28px"
  button-ghost-on-ink:
    backgroundColor: "transparent"
    textColor: "{colors.parchment-ivory}"
    rounded: "{rounded.none}"
    padding: "14px 28px"
  chip:
    backgroundColor: "transparent"
    textColor: "{colors.midad-black}"
    rounded: "{rounded.pill}"
    padding: "8px 16px"
  chip-selected:
    backgroundColor: "{colors.midad-black}"
    textColor: "{colors.parchment-ivory}"
  input:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.midad-black}"
    rounded: "{rounded.none}"
    height: "44px"
    padding: "0 12px"
  header:
    backgroundColor: "{colors.parchment-ivory}"
    textColor: "{colors.midad-black}"
    height: "72px"
  icon-button:
    rounded: "{rounded.none}"
    size: "44px"
---

# Design System: Midad

## Overview

**Creative North Star: "The Calligrapher's Scroll"**

Midad treats a page the way a calligrapher treats a sheet: one continuous surface, written top to bottom in ink. The homepage is not a stack of banners; it is a single painted scroll. A fixed Midad frame (ink, ivory, smoke, sand) holds a sequence of full-bleed bands, and each band flows into the next through a soft wave filled with the next band's colour. Between the frame bands, each category gets its own perfume world, painted in that perfume's palette, texture and ingredients, so the page carries very different moods without ever breaking into separate boxes.

One gold ink line is written down the whole scroll as the visitor reads. It sways down a side gutter like a hand-held reed pen, presses heavier where it rides each wave across to the other side, and leaves a small gold square, the brand's spray mark, at each crossing. That line is the page's only authored motion; everything else is quiet.

The density is low and the voice restrained: large Amiri headlines, generous bands (64 to 96px of vertical padding), square-cornered controls, and gold used as a signature, never as a fill. Depth comes from light and material (lamp glow, marble, resin, silk, smoke) drawn procedurally, not from UI shadows.

**Key Characteristics:**
- One continuous scroll of full-bleed bands joined by soft waves; no hard section edges.
- Fixed brand frame plus per-perfume worlds that bring their own palette, texture, plinth, light and line-drawn ingredients.
- A single gold reed-pen line as the only authored motion, with spray-square marks at wave crossings.
- Square corners everywhere except pill chips; thin 1px borders do the structural work.
- Arabic-first RTL typography: Amiri for display, Plex Arabic for text, Cinzel only for Latin caps, Montserrat only for Latin data.
- Smoked-glass bottles drawn to the brand guide's real millimetre proportions; no stock photography.

## Colors

A warm, near-monochrome frame of ink, ivory, smoke and sand, signed rather than filled with gold; perfume worlds bring their own palettes inside it.

### Primary
- **Midad Black** (`midad-black`): the ink. Hero band, footer, announcement bar, primary buttons, selected chips, cart badge, toasts, and the default text colour on light bands. Also one of the two contrast fallbacks for perfume palettes.
- **Gold Leaf** (`gold-leaf`): the signature metal. Nav underline on hover, the logo's kasra, foil-stamp marks, caret colour. Never a large fill.
- **Light Gold** (`light-gold`): gold on dark grounds. Gold-filled primary buttons on ink (the hero CTA, add-to-cart on the product page, the discovery box), footer logo and column titles, spray-square marks on the ink line, selection highlight.
- **Gold Ink** (`gold-ink`, #765A30): a digital-only text gold for small functional text on ivory and paper, where Gold Leaf reads at 2.6:1 and fails WCAG; Gold Ink reads at 5.4:1. For step numerals, required-field asterisks, discount and credit rows, the finder's question count. Never for brand names, the logo or the wordmark. Not in the printed guide: **needs the client's sign-off** to stand.

### Neutral
- **Parchment Ivory** (`parchment-ivory`): the page ground and the header; light frame bands (discovery, finder); text on ink. The second contrast fallback.
- **Paper** (`paper`): a lighter ivory for fields, the search dropdown, the gift card and the discovery tray; anything that should read as a sheet laid on ivory.
- **Sand** (`sand`): header and field hairlines, the gift band's ground, hero lead text on ink, footer text.
- **Smoked Glass** (`smoked-glass`) and **Smoked Glass Deep** (`smoked-glass-deep`): borders and chip fills on ink, primary-button hover, and Midad's own fallback spotlight palette.
- **Lead Smoke** (`lead-smoke`): the lead paragraph colour on light bands (1.125rem, 1.9 line-height); warmer and darker than `muted` so long Arabic copy holds contrast on ivory and sand.
- **Muted** (`muted`) / **Muted Light** (`muted-light`): secondary copy and placeholders on light / dark grounds respectively; the Montserrat size caption under the hero lineup.
- **Danger** (`danger`): error states only.

### Perfume-world palettes
Each spotlight carries its own five-role palette from the admin: `bg`, `surface`, `fg`, `muted`, `accent`. The band paints `bg`; product cards paint `surface`; the perfume's Latin name, the ingredient drawings, the stage light and the add-to-cart button take `accent`. These are data, not tokens: the system defines the roles, not the values. When a category has no spotlight, it falls back to Midad's own palette (smoked-glass ground, ivory text, light-gold accent).

### Named Rules
**The Gold Signature Rule.** Gold covers at most 10% of any view. It marks (a line, a stamp, a ring, a numeral, one CTA on ink); it never paints a band, a card or a panel.

**The Readable World Rule.** A perfume palette may be anything, but its text may not be unreadable. `fg` must reach 4.5:1 on `bg` and `muted` 3:1, checked at render time; a failing value falls back to whichever of Midad Black or Parchment Ivory reads better. The accent button's label is resolved the same way.

**The Next-Band Wave Rule.** Every band ends in a soft wave filled with the next band's background colour, so the eye never meets a straight seam. Each divider uses a different wave shape; the page never repeats one.

## Typography

**Display Font:** Amiri (with Traditional Arabic, serif)
**Body Font:** IBM Plex Sans Arabic (with Segoe UI, Tahoma, sans-serif)
**Latin Caps Font:** Cinzel (with Trajan Pro, Georgia, serif)
**Data Font:** Montserrat (with Helvetica Neue, Arial, sans-serif)

**Character:** Amiri carries the calligrapher's voice at large sizes, set bold with tight leading so headlines read as written strokes; Plex Arabic is the calm shop assistant underneath. The two Latin faces are guests with strict jobs: Cinzel spells perfume names and MIDAD in tracked capitals, Montserrat sets small technical data.

### Hierarchy
- **Display** (Amiri 700, clamp(3.25rem, 8.4vw, 6rem), 1.08): the hero headline; the featured perfume's name in a spotlight uses the same scale at 1.02 leading.
- **Headline** (Amiri 700, clamp(2.5rem, 5vw, 4rem) up to clamp(3rem, 7vw, 5.5rem), 1): band titles (category names, "ابدأ بستّة", gift, finder).
- **Title** (Amiri 700, 1.5rem, ~1.25): product-card names; slider titles use Amiri 400 at 1.75rem.
- **Tagline** (Amiri 400, clamp(1.375rem, 2.4vw, 1.875rem), snug): a perfume's one-line sentence; the scent-pyramid notes use Amiri at 1.375rem.
- **Lead** (Plex 400, 1.125rem, 1.9): the paragraph under a band title, max 38 to 40ch.
- **Body** (Plex 400, 1rem, 1.7): default text; buttons are Plex 400/500 at 1rem.
- **Label** (Plex 400, 0.8125 to 0.9375rem): nav (0.9375rem), "inspired by" lines, captions, legends, footer links.
- **Latin Caps** (Cinzel 400, 0.6875rem on cards up to clamp(0.875rem, 1.4vw, 1.0625rem) in spotlights, tracking 0.18em to 0.34em, uppercase): perfume Latin names and the MIDAD wordmark only.
- **Data** (Montserrat 400, 0.6875 to 0.75rem, tracking 0.2 to 0.22em): size captions ("10 · 30 · 50 · 100 ML"), pyramid tier names (TOP / HEART / BASE), step numerals, the cart count.

### Named Rules
**The Numerals Rule.** Arabic UI shows Arabic-Indic numerals (prices, sizes, counts, ml, character counters), formatted through the `ar-EG` locale. Latin digits appear only inside Montserrat data captions.

**The Guest Script Rule.** Latin never sets running text. Cinzel is caps-only for names; Montserrat is small data only, marked `dir="ltr"`. Everything a visitor reads as a sentence is Arabic.

## Layout

The page is a vertical sequence of full-bleed bands (`<section>` per band, isolated stacking), each holding a centred container of 1280px max width with a fluid side gutter of clamp(16px, 4vw, 48px). Bands breathe at 64px vertical padding, rising to 96px from the md breakpoint; spotlight bands use 64/80px top. Inside a band the common shape is a two-column grid from lg (1024px) up, text on the start (right) side and the drawn object on the end (left) side, collapsing to one column below with the object moved above the text in spotlights. Wave dividers sit at each band's foot at clamp(44px, 7vw, 96px) tall.

The header is sticky, 72px, ivory at 95% with a light backdrop blur and a sand hairline. Product rows are native scroll-snap sliders: cards clamp(220px, 23%, 280px) wide on desktop, 72% on phones (≤640px), 16px apart, with 44px square prev/next buttons. Internal rhythm steps through 8, 12, 16, 24 and 40px; headings to body sit 20 to 24px apart.

### Named Rules
**The RTL-First Rule.** Layout is authored in logical properties (start/end, inline) for RTL. The ink line begins on the right; slider "forward" moves visually left; ornaments in textured worlds stay on the visual (left) half so they never sit under text.

## Elevation & Depth

The interface itself is flat: cards, buttons, chips, inputs and bands carry no shadow at rest or on hover. Depth belongs to objects and light inside the scene: bottles cast soft drop shadows onto drawn plinths and marble slabs, stage lights glow behind featured bottles, and each perfume world adds grain and material through procedural gradients and inline SVG. The few UI shadows are reserved for layers that genuinely float above the page.

### Shadow Vocabulary
- **Bottle cast** (`filter: drop-shadow(0 22px 22px rgba(0,0,0,.55))`, spotlights `0 26px 22px rgba(0,0,0,.45)`): every drawn bottle on a stage.
- **Floating sheet** (`box-shadow: 0 18px 40px -20px rgba(14,13,12,.45)`): search dropdown.
- **Toast** (`box-shadow: 0 12px 30px -12px rgba(14,13,12,.6)`): ink toasts.
- **Drawer** (`box-shadow: 0 0 60px rgba(14,13,12,.35)`): the cart drawer over a 50% ink scrim.
- **Laid paper** (`box-shadow: 0 34px 50px -28px rgba(14,13,12,.55)`): physical objects drawn on a band (gift card, discovery box).

### Named Rules
**The Flat Interface Rule.** Controls and cards never lift. Shadows belong to drawn objects resting in light, or to layers that truly overlay the page (dropdown, toast, drawer).

**The Painted Light Rule.** Atmosphere is procedural: gradients, SVG noise and line art, painted from the band's own palette. No raster backgrounds ship; textures stay off the text half.

## Shapes

Square by default. Buttons, cards, inputs, size options, icon buttons, the header search and the drawer have 0px corners and 1px borders, often drawn in `currentColor` mixed to 18 to 60% so a component inherits the world it sits in. The only round shapes are pill chips (search suggestions, finder answers) and the cart count badge. Focus rings are 2px gold (or `currentColor` inside a perfume world) with a 2 to 3px offset and a 2px corner. The recurring brand geometry is the spray square: a small square rotated 18°, used as the ink line's nib and its crossing marks. Bands end in soft, never-repeating sine waves rather than straight edges.

## Components

### Buttons
Quiet, square and confident; colour does the talking, not shape.
- **Shape:** square corners (0px), 14px × 28px padding, Plex 1rem, optional 18px arrow icon after the label.
- **Primary:** Midad Black with Parchment Ivory text; hover deepens to Smoked Glass (colour transition only).
- **Gold:** Light Gold with Midad Black text, used once, as the primary CTA on the ink hero.
- **Ghost on ink:** transparent, ivory text, a sand hairline at 50% that turns light gold on hover.
- **World accent (spotlight add-to-cart):** fills with the perfume's `accent`; label colour resolved by contrast; hover is `brightness(1.1)`; disabled at 50% opacity.
- **Icon buttons:** 40 to 44px squares with a `currentColor` hairline; hover fills with 12% `currentColor`.

### Chips
- **Style:** pill (9999px), 1px border, Plex 0.875 to 0.9375rem. On ink: smoked-glass border over a 60% smoked-glass-deep fill with sand text. On ivory: 25% ink border.
- **State:** hover takes the gold-light (on ink) or full ink (on ivory) border; selected fills Midad Black with ivory text; keyboard focus draws a two-tone ring (2px ink line between Light Gold edges) that reads on every ground.

### Cards / Containers
- **Corner Style:** square (0px).
- **Background:** the perfume world's `surface`; text in its `fg` and `muted`.
- **Shadow Strategy:** none (see The Flat Interface Rule); an accent radial glow at 20% sits behind the bottle.
- **Border:** none on the card; the add button carries a `fg` hairline at ~33%.
- **Internal Padding:** 20px sides and bottom; a 4:5 bottle stage above. Hover lifts only the bottle (6px, 500ms ink easing), never the card. The whole card is one link via a stretched title.

### Inputs / Fields
- **Style:** Paper ground, 1px hairline (sand in the header search, 25% ink in forms), square corners, 40 to 44px tall, 12px inline padding, gold caret.
- **Focus:** the border shifts to gold (search) or full ink (forms); no glow.
- **Size picker:** four equal square options in a row, each showing size and price in Arabic-Indic numerals; borders in `currentColor` at 28% (rest), 60% (hover), 100% plus a 1px inset ring (selected); sold-out at 45% opacity.

### Navigation
- **Header:** ivory, 72px, logo and Cinzel MIDAD wordmark at the start, Plex 0.9375rem links 28px apart; hover draws a 1px gold underline from the right over 300ms. Search field 288px from xl, otherwise an icon toggle.
- **Mobile:** an ivory list below the header with sand hairlines between items, links set in Amiri at 1.5rem.
- **Footer:** ink with sand text, light-gold column titles and logo, square social icon buttons with smoked-glass borders turning light gold on hover. It marks the end of the ink line.

### The Ink Line (signature)
One SVG stroke drawn over the whole main column: a 1.3px line in Gold Leaf (#B08D57), pressed to 3.2px where it rides a wave crossing, with a 10px Light Gold square rotated 18° at each crossing and an 8px nib square leading the stroke. It is revealed by scroll to 82% of the viewport height, never rewinds, keeps written ink across resizes, and under reduced motion is shown complete. The line's path is computed from the same wave function the dividers use, so it always lies exactly on the edge.

### Spotlight Stage (signature)
A featured perfume's world: a square stage with the world's light (radial lamp glow, silk highlight or a conic smoke beam), a 100ml bottle on a world-specific plinth (wood-grain resin, rounded silk, veined marble), and up to four 80×80 line-drawn ingredients stroked at 1.4px in the accent, captioned in Plex. Beside it: Amiri name, Cinzel Latin name in the accent, Amiri tagline, a three-tier scent pyramid (Arabic tier name over a Montserrat TOP/HEART/BASE caption, notes in Amiri), the size picker and the accent button.

### Bottle Art (signature)
Every bottle is drawn, never photographed: smoked-glass body, gold collar and cap with a five-stop metallic gradient, the logo and wordmark on the glass, at the brand guide's real millimetre proportions for 10, 30, 50 and 100ml. Sizes are shown together at true relative scale.

## Do's and Don'ts

### Do:
- **Do** end every band in a wave filled with the next band's colour, and give each divider its own wave shape.
- **Do** keep gold under 10% of any view: lines, stamps, rings, numerals, one CTA on ink.
- **Do** let a perfume world bring its own `bg / surface / fg / muted / accent`, texture, plinth, light and ingredient drawings, and run its text colours through the contrast fallback.
- **Do** use Arabic-Indic numerals in Arabic UI via the `ar-EG` formatter; reserve Latin digits for Montserrat data captions.
- **Do** keep controls square with 1px hairlines; draw borders in `currentColor` so components inherit their world.
- **Do** draw bottles with the guide's real proportions, and render atmosphere procedurally (gradients, SVG grain, line art).
- **Do** ease object motion with the ink curve, cubic-bezier(0.16, 1, 0.3, 1), and respect reduced motion.

### Don't:
- **Don't** add a second authored motion; the ink line is the page's only one. Hover states change colour, borders, or nudge a bottle, nothing more.
- **Don't** fill bands, cards or panels with gold, or use gold as body text on ivory.
- **Don't** shadow cards, buttons or chips, or lift them on hover.
- **Don't** separate bands with straight rules or hard colour cuts.
- **Don't** set running text in Latin, or use Cinzel and Montserrat outside names and data.
- **Don't** redraw, stretch, recolour or shadow the logo, and never place the spray pattern behind it.
- **Don't** use cold blue light, glossy plastic, or red roses in any world's texture or imagery.
- **Don't** put texture ornament under text; it stays on the visual half of a band.
