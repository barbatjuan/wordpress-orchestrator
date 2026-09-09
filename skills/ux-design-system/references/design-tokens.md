# Design tokens

`web-templates/references/design-system.md` is the **single authority** for token NAMES and
VALUES. This file covers only the ROLES: what each token is FOR, how to derive a value for a
brand, and when to use it. Never restate a number here — read it there. The orchestrator gets
the brand input from `project-context` (logo, palette) or asks the user.

Swap the values per brand; keep the roles.

## Palette roles

| Job | Tokens | What it is FOR |
|-----|--------|----------------|
| Dominant | `--c-bg`, `--c-bg-alt` | white / near-white background (or near-black in a dark brand). Carries most of the page; nothing competes with it. |
| Contrast | `--c-text`, `--c-primary` | near-black type and inverted dark surfaces (footer, announcement bar, solid CTA). Structure and readability. |
| Accent | `--c-accent` | ONE color. CTAs, action icons, important links, active states. Never body text, never decoration. |
| Neutrals | `--c-text-muted`, `--c-border` | meta text, dividers, tinted sections. Derived from the contrast color, desaturated. |
| States | `--c-success`, `--c-error`, `--c-sale` | stock/confirmation, errors, discount badges and offer prices. Semantic only — never reused as decoration. |

`--c-secondary` is the second action level (outline / ghost buttons). It may equal the contrast
color; it must never equal the accent, or the one-accent rule collapses.

### Deriving a palette from a logo
1. Take the logo's most saturated color → candidate **accent**. If the logo has two, the second
   becomes a hover/active shade, not a second accent.
2. **Dominant** = the lightest neutral in the brand material, or plain white.
3. **Contrast** = the darkest brand neutral pushed to near-black. Check 4.5:1 against the dominant.
4. Derive the neutrals from the contrast, not from grey: muted text ≈ **37%** of the way toward
   the dominant, soft body ink ≈ 23%, border ≈ 89%. Sampling off the contrast keeps the whole page
   coherent, and it is what lets a dark ground work without a second palette.
   Muted read 55–60% here and was never built at that value, because it cannot be: 57% of the way
   from a near-black to white lands on 2.76:1, and this role paints body copy. Measured figures.
5. Accent hover = the accent darkened ~10%. Verify the CTA label still passes contrast on it.

Reject an accent that fails 4.5:1 with both white and near-black label text — pick a darker
variant instead of adding a second color.

## Typography roles
- `--font-primary` — headings + UI. Heavy weight and tight line-height read premium, but the
  family itself comes from the chosen style (`references/style-catalog/`) — never copy an
  illustrative example verbatim as a default.
- `--font-secondary` — body. Normal weight, open line-height. May equal `--font-primary`. Never
  a third family. See `references/style-catalog/` for the concrete pairings — one per style.
- Hierarchy per section: eyebrow (`--fs-eyebrow`, uppercase, letter-spaced) → heading → paragraph.
  One `--fs-h1` per page.
- The scale is fluid (`clamp()`), so sizes come from the token, never from a per-section override.
- **A big scale position is a CONSTRAINT ON THE COPY, and it has to reach the brief.** `--fs-h1-max`
  is a promise about a heading's size, not about the column it lands in: at `monumental` the cap is
  120px, and 120px type needs about `0.757em × <characters>` of track — 636px for a 7-letter word in
  Archivo Expanded. A hero holding its copy in 6 of 12 columns has 491px at 1280 and does not reach
  that until ~1920. So at `monumental` and `editorial`, **headlines want short words**: a
  10-character word cannot sit in a two-column hero at that scale, and the answer is to change the
  headline or the layout — never to loosen the guard that keeps it from breaking mid-word. Tell
  `wordpress-copywriter` the longest word each heading may carry, along with the scale position.
  The measurements, the guard and what it costs at each width are in
  `html-mockup/references/mockup-guide.md` § "An overflow check cannot see a chopped word".

## Spacing & radii roles
- Section rhythm: the same padding tokens on every section, stepping up mobile → tablet → desktop.
  A bespoke margin anywhere breaks the rhythm.
- Grid gaps use one step of the `--sp-*` scale; tight mobile gaps use the step below. Nothing
  outside the scale.
- Radii carry meaning: containers are the softest, then cards; buttons, inputs and images share
  the smallest step. Separate tokens are what let one anchor go sharp and another soft without
  touching a single module — each style's Card recipe in `references/style-catalog/` says which.
- Audit margins as a dedicated pass — inconsistent spacing is the #1 tell of a cheap template.

## Imagery
- Real, high-quality photography (stock via a licensed source). Cover-fit with subtle dark
  gradient scrims over hero/CTA photos so white text stays legible.

## Perceptual axes
Eight axes carry what makes two sites feel different; the accent COLOUR is NOT one of them, it
derives from the brand (the accent's POLICY — reserved, duotone, gradient… — is the axis).
Values live in `web-templates/references/design-system.md`.
- **Scale** — the RANGE between body and display, and how tight the display leads. The single
  largest perceptual difference between two sites, and the one the framework never varied.
- **Ground** — what the page is made of. Choosing white is a decision and is recorded as one;
  white-by-default is how a site reads as a template.
- **Density** — one multiplier over the whole spacing scale, so the rhythm stays consistent while
  the airiness changes completely.
- **Composition** — which section blueprint is on offer, not free improvisation. Each position
  names one blueprint in `references/layout-patterns.md`; that blueprint, not a sentence, is the
  value. Apply it to every section.
- **Elevation** — how separation is expressed: air, a hairline, a shadow, or an accent glow.
- **Accent policy** — how the one accent colour is spent: reserved to CTAs, a tinted field, a
  duotone photo grade, a gradient, metallic, or polychrome. Never the colour itself.
- **Chassis** — how a content block is physically bounded: bare, carded, bordered, a hard
  shadow, layered, or nothing at all.
- **Ornament** — the one optional texture a style permits: a rule, a pattern, an illustration,
  or none.
