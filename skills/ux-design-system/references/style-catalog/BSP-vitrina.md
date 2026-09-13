# `BSP-VITRINA` — Bespoke declaration

A small workshop selling collectible resin pieces in numbered runs. Site type `ecommerce`,
archetype `TPL-E-01` (Visual Brand). Route `bespoke`.

**Anonymised.** This declaration records a REAL bespoke resolution, kept because it is the only
worked example of the route, but the client's identity, domain and exact niche are not in this
repository: the repository is public and the work is theirs. `VITRINA` is a pseudonym taken from
the brief's own metaphor, and every measurement below is the real one.

**Axes:** scale `monumental` · ground `ink` · density `generous` · composition `strict-grid` ·
elevation `accent-glow` · accent `polychrome` · chassis `soft-carded` · ornament `none`

## 1. Why the catalog cannot express this

Measured against `_README.md`'s own 8-axis table, the nearest three entries each share exactly
four of eight positions with the resolved set, and every one of them disagrees on a different four:

| Entry | Shares | Disagrees on |
|---|---|---|
| `STY-VITRINE` | ground, composition, chassis, ornament | scale, density, elevation, accent |
| `STY-DIRECT` | scale, ground, elevation, ornament | density, composition, accent, chassis |
| `STY-TECH-SAAS` | density, composition, elevation, ornament | scale, ground, accent, chassis |

Adopting any of them means overriding half the anchor, which is the exact state
`RT_MOCKUP_AXES_MISMATCH`'s docblock calls "a site that is neither anchor". Hence `ROUTE-BESPOKE`
rather than a re-pointed catalog entry.

## 2. Wireframe

```
COMP-ANNOUNCEMENT
COMP-HEADER
COMP-HERO
COMP-CATEGORY-CARD
COMP-PRODUCT-CAROUSEL
COMP-GALLERY
COMP-BOOKING
COMP-NEWSLETTER
COMP-FOOTER
```

`COMP-TESTIMONIAL` is deliberately absent: the shop has no real reviews yet, and
`TGL-TESTIMONIALS` was answered `no`.

## 3. The named polychrome exception

`accent: polychrome` lifts the ONE-colour rule for exactly one bounded set, written down here
beside the set it covers, per `ACC-POLYCHROME`:

**The set:** the three collection marks in `COMP-CATEGORY-CARD`, and the run-number badge on a
product card. Nothing else. Every mark outside that set answers to the ordinary four-role accent
whitelist in pink.

**The second colour:** `#FFE04D`, measured 14.46:1 on `--c-bg` and 13.22:1 on `--c-bg-alt`.

## 4. Resolved axis rationale

| Axis | Position | Evidence |
|---|---|---|
| scale | `monumental` | both client references open with display type at poster size |
| ground | `ink` | client asked for dark; neutral rather than `ink-cool`, whose blue cast fights yellow |
| density | `generous` | both references breathe; `1.35` over the spacing scale |
| composition | `strict-grid` | "vitrina a oscuras, pieza iluminada" — a display case is a grid of equal cells |
| elevation | `accent-glow` | a shadow is invisible on near-black; the reference separates with a magenta glow |
| accent | `polychrome` | the only position that admits the client's pink AND yellow without two accents |
| chassis | `soft-carded` | the lit vitrine cell: a surface step at rest, the glow reserved for touch |
| ornament | `none` | the photography is the ornament; a rule or pattern would compete with it |
