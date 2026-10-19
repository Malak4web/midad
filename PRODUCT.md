# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

- Frontend: Vue 3 + Vite + Tailwind CSS (SPA).
- Backend: Laravel, built from scratch with no third-party Laravel packages (exact boundary for first-party packages still open, see Capabilities).
- Database: MySQL.

## Users

Two audiences with equal weight:

- Young buyers (roughly 18–30) arriving from TikTok and Reels on a phone, looking for a long-lasting alternative to a famous perfume at a reasonable price.
- Older, better-off buyers (roughly 25–40) buying for themselves or as a gift, who care about the unboxing, packaging and gift experience.

## Product Purpose

Midad (مداد) is an Egyptian online perfume brand selling 30 perfumes in three collections: men, women, and oriental & niche. Each perfume carries its own Midad name and is described as "inspired by" a known perfume. Success means a visitor finds the right scent quickly, trusts it enough to buy without smelling it, and returns.

## Positioning

A calligraphy-rooted Arabic brand that treats perfume the way a calligrapher treats ink: "عطرٌ يُكتَب، فيبقى". Mid-premium pricing (roughly 600–1500 EGP for 50 ml), justified by packaging, longevity and the gift experience.

## Operating Context

- Market: Egypt only. Currency: EGP. Shipping within Egyptian governorates.
- Language: Arabic only, RTL. Latin perfume names (Cinzel) stay as they are.
- Payments: cash on delivery plus online payment.
- Sizes: 10, 30, 50, 100 ml per perfume.

## Capabilities and Constraints

- Required features beyond normal shopping: scent finder quiz, discovery set (samples), gift wrapping with a message card, cash on delivery.
- "Inspired by" reference is shown on the storefront as a description line (user decision; carries trademark and ad-platform risk).
- Laravel: first-party packages allowed (Sanctum, Queues, Mail, Storage); no third-party packages.
- Payments: Paymob via direct HTTP (no SDK) plus cash on delivery. Shipping: manual per-governorate rates managed in admin; carrier integration later.
- Admin phase 1: products, categories, stock; orders and shipping; coupons and offers (including discovery-box credit); customers and reports.
- Testing: PHPUnit, Vitest + Vue Test Utils, Playwright.
- Homepage direction (chosen 2026-10-17): classic storefront structure merged with the discovery box, plus one section per category that features a single perfume with its own identity and a slider of the rest; curved section edges flow into the next section so the page reads as one continuous painting.

## Brand Commitments

Binding brand guide: `Midad-Brand-Guidelines.pdf`.

- Palette: Midad Black #0E0D0C, Smoked Glass #3B342C, Gold Leaf #B08D57, Light Gold #D6B77E, Parchment Ivory #F2EADB, Sand #D8CAB0. Gold never exceeds 10% of a design.
- Type: Amiri (Arabic display), IBM Plex Sans Arabic (Arabic text/UI), Cinzel (Latin MIDAD and perfume names, caps), Montserrat (secondary Latin data).
- Logo: calligraphic مداد forming a bottle, with spray dots and a gold kasra. Never redrawn, stretched, recoloured, or shadowed.
- Pattern: the "spray" (رذاذ), small gold squares at 18°, never behind the logo.
- Photography: two moods only, "Midad Night" (black marble, 2700K lamps) and "Midad Day" (linen, travertine, soft daylight). Warm only; no cold blue light, glossy plastic, or red roses.
- Voice: short, refined fusha; suggestion over exaggeration; no repeated exclamation marks; no emoji in official content; no discount shouting.

## Evidence on Hand

- Brand guide PDF and product consultation PDF (30 perfumes, notes, ingredients, inspired-by names).
- Not on hand yet: logo SVG, product photography, prices per size, top/heart/base note split, reviews. Do not fabricate reviews or prices.

## Product Principles

1. Help people choose without smelling: notes, comparisons, and the finder do the work a shop assistant would.
2. Luxury is in the details, never in noise or discount banners.
3. Arabic-first: calligraphy and RTL are the identity, not a translation.
4. The gift is a first-class path, not an add-on.
5. Phone-first for the social audience, without losing the premium feel.
