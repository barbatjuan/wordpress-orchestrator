---
name: ux-design-system
description: "Trigger: premium web design, enfoque, marca del cliente, hero, layout, cards, hover effects, responsive, microinteractions, design tokens, colores globales, spacing, palette. Builder-agnostic visual language: the Plantilla's Enfoque as vocabulary, the client's brand applied inside it, and tokens that land in Elementor's global Site Settings (Elementor or Divi)."
license: Apache-2.0
metadata:
  author: "juan"
  version: "2.0"
---

# UX Design System

The visual language, independent of the builder. A client's look starts from the Plantilla
`web-templates` chose and its **Enfoque**; this skill places the client's brand inside it and
resolves the tokens the lienzo, the maqueta and the build share.

## Activation Contract
After `web-templates`, before Claude Design (the design skill) and `html-mockup`. Never straight
to builder-core.

## The Design Space
- **Enfoque** (`web-templates/references/enfoques.md`): eight axes, a type pair and an image
  direction. Words to reason with, never a token engine.
- **The client's brand**: logo, colours, type pair, photography, placed in the Enfoque's roles.
- Legacy, not offered to new clients, removal pending: `references/style-catalog/`.

## Hard Rules
- **The Enfoque comes from the Plantilla**, never re-picked from a catalogue. References that pull
  it elsewhere go back to `web-templates`, never a repaint.
  (no verifier: which Enfoque a lienzo took is read by the reviewer against the ficha and the decision record)
- **The brand changes the skin, not the ADN**: colours, logo, photographs and type pair, each in
  the role the ficha gives it.
  (no verifier: an ADN is read by eye against the ficha at client approval)
- **Tokens land in Elementor's global Site Settings — global colours and global fonts — never in
  custom CSS.** A token with no slot is a design change, noted in the ficha's Mapeo nativo.
  (verifier: `qa-review` house-rule row 37 counts the custom CSS the build carries against the ficha's ceiling)
- **Every colour pair is measured**: 4.5:1 text, 3:1 interface. A failing accent gets a darker
  variant, never a second colour.
  (no verifier: no rule measures a client palette; `html-mockup/assets/herramientas/color.php` is run by whoever resolves it)
- One accent — ONLY CTAs, action icons, important links; neutrals carry the rest.
- Motion is calm: `cubic-bezier(.22,1,.36,1)`, ~.35–.7s, small moves, never a snap.
- Two button families: solid accent + ghost, each with a legible hover, label centred on both axes.
- ONE card recipe site-wide; one spacing rhythm, audited as a pass.

## Execution Steps
1. **Read the Plantilla**: its ficha and its Enfoque in `enfoques.md` — the eight axes as the
   Plantilla resolves them are the start. Ruta a medida: the axes declared in the decision record.
2. **References against the axes** (reuse recomendador step 4). An axis pulled away from the ficha
   is one question to the user; ADN axes do not move.
3. **Brand into roles**: palette from the logo (`references/design-tokens.md`), type pair, and a
   photo brief from the Plantilla's manifiesto (rol and framing per image).
4. **Measure** every pair (`color.php --contraste`).
5. **Recent deliveries**: a repeat of the Enfoque in the last five rows of
   `references/shipped-log.md` goes to the user.
6. **Map to Site Settings**; list what has no slot.
7. **Hand over** to Claude Design for the client's lienzo, then `html-mockup`.

## Output Contract
Enfoque with its axes, what the brand changed, palette with roles and measured ratios, type pair,
the Site Settings mapping, tokens without a slot, what to avoid, the recent-delivery check. No
builder code.

## References
- `references/design-tokens.md` — palette and type roles, Site Settings.
- `references/motion.md` — hover timings, card recipe, button system.
- `references/layout-patterns.md` — grid tracks, disclosure lists, responsive rules.
- `references/shipped-log.md` — the delivery ledger.
