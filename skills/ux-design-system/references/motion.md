# Motion, cards, glass, buttons — the premium recipes

Battle-tested on Elementor; the values are builder-agnostic (apply via each builder's
native custom-CSS field, scoped to the element, never a global stylesheet).

## Hover timing (calm, not brusque)
- Curve: `cubic-bezier(.22,1,.36,1)`. Duration: `.35s` color, `.5s` lift/shadow, `.7s` image zoom.
- Lift: `translateY(-4px)` (cards) or `-6px` (feature cards). Image: `scale(1.045)`.
- Shadow on hover: `0 18px 40px -12px rgba(21,24,26,.16)` (soft, not a hard border swap).
- `will-change:transform`. Avoid `translateY(-6px)`+`scale(1.06)`+`.35s` → feels snappy/cheap.

## Elevation tokens (`--elev-rest`, `--elev-hover`)
The hardcoded shadow above is one elevation position's value, not the only one — a site can also
sit on a hairline, an accent glow, or nothing at all. `--elev-rest` / `--elev-hover` replace that
hardcoded shadow per the position chosen on the elevation axis; see
`web-templates/references/design-system.md` for the four positions' values. The hover curve and
durations above are unchanged regardless of which elevation position is in play.

## Premium feature card (reuse everywhere)
White card, 1px neutral border, radius 16, padding ~34/30. Accent circular icon chip
(icon widget `view:stacked`, `shape:circle`, accent fill, white glyph). On hover:
lift + soft shadow + a top accent bar revealing via `::before{transform:scaleX(0)→1}`.
One recipe, used in home advantages AND inner-page value cards, etc.

## Product-card grid (WooCommerce)
Equal height is mandatory on 2-col mobile: `ul.products{align-items:stretch}` +
`li.product{display:flex;flex-direction:column;height:100%}` + push the button down with
`.button{margin-top:auto}`. Force the CTA to the accent color with `!important` (theme wins otherwise).

## Glass
Never put `backdrop-filter` on the container — it becomes a containing block that traps
`position:fixed` descendants (e.g. a side cart) inside its box. Put the frost on a
`::before` layer: `selector{position:relative} selector::before{content:"";position:absolute;
inset:0;z-index:-1;background:rgba(255,255,255,.72);backdrop-filter:saturate(180%) blur(16px)}`.

## Button system (two families, real hover in both states)
- **The label is centred on BOTH axes**, always, unless someone explicitly asks otherwise:
  `display:inline-flex; align-items:center; justify-content:center`, keeping `text-align:center`
  for a label that wraps to two lines.
  Why `text-align:center` is not enough: it centres horizontally and says nothing about the
  vertical. A button is only as tall as its own line box until something stretches it — and
  `align-items:stretch` is the DEFAULT of every flex row, so a CTA row, a nav bar or an
  equal-height card grid stretches its buttons to match the tallest sibling as a matter of course.
  A stretched button renders its label pinned to the TOP with all the slack underneath.
  Measured on a hero CTA row stretched to 179.5px around a 21px label: the label's centre sat
  65.2px above the button's centre, with `text-align:center` present and every text-based check
  passing. This one is invisible to grep and visible only in a render, which is why `qa-review`
  measures it on the built page (house-rules row 29) instead of looking for the declaration.
- Solid accent: accent bg → darker accent on hover + `translateY(-2px)` + accent glow shadow.
- Ghost/outline: transparent, dark text, soft border → on hover faint accent-tint bg +
  accent text + accent border (NOT white text, which vanishes on light bg).
- Ghost on dark heroes: white text/border → white fill + dark text on hover.
- Drive transition + lift via the element's native custom-CSS so it never depends on
  conditionally-enqueued hover assets.
