# Layout patterns + responsive

Section blueprints proven to read as premium. Builder-agnostic; the builder-core skill
implements them with native widgets/modules only.

## Page rhythm (alternate to avoid monotony)
Hero (dark photo + scrim) → advantages (light, feature cards) → services (grey tint grid) →
featured products (light) → process (dark) → testimonials (light) → banner CTA → final CTA (dark).
Alternate light / grey / dark backgrounds so adjacent sections never blur together.

## Sections
- **Hero**: full-bleed photo + gradient overlay, eyebrow + H1 + subcopy at ~58% width, two
  buttons (solid + ghost), a thin counter row. Mobile: center everything, counters centered.
- **Feature grid**: 3×2 premium feature cards (see `motion.md`). Distinct from image cards.
- **Services / catalog**: image cards in a grid, one card recipe, hover zoom.
- **Banner CTA**: rounded full-bleed photo, dark gradient, copy + button on the left.
- **Testimonials**: native carousel with infinite loop + autoplay + pause-on-hover (no custom JS).
- **Process**: numbered steps on a dark section.

## Header / navigation
- Sticky, glass (frost on `::before`, see `motion.md`). Logo left; nav + phone + cart + CTA right,
  one row on desktop.
- Desktop nav underline: accent line fading in on hover/active — scope to `@media(min-width:1025px)`
  so it never leaks into the mobile panel as odd bars.
- Mobile menu: modern, not a bare burger. A full-screen overlay on open reads best; scope the
  overlay CSS to the open state (`[aria-expanded="true"]`) so it isn't stuck open when closed.

## Composition blueprints

The composition axis resolves to exactly ONE of these four, named by id below (`centered` is
`LP-CENTERED`, `asymmetric` `LP-ASYMMETRIC`, `strict-grid` `LP-STRICT-GRID`, `broken-grid`
`LP-BROKEN-GRID`). They are the reason two designs over identical content do not render the same page, so each one
fixes three things a sentence never could: how many columns, where the content sits inside them,
and what an image is allowed to do. Apply the chosen blueprint to EVERY section — mixing two is
how a page goes back to reading as a template. Everything else on this page (the section recipes
above, the header, motion) is shared and does not change with the position.

### `LP-CENTERED`
- Grid: 12 columns; content in columns 3–11, capped at `--content-width`, identical on every section.
- Hero: eyebrow, H1 and subcopy on one centred axis; both buttons centred beneath, side by side.
- Section headings: centred, eyebrow centred above them.
- Images: always inside the container, equal margins left and right. Nothing bleeds, ever.
- Grids: symmetric only — 2, 3 or 4 equal columns; an odd last row centres its remainder.

### `LP-ASYMMETRIC`
- Grid: 12 columns declared with named lines so an edge is a line, not a margin:
  `[full-start] minmax(pad,1fr) [wide-start] repeat(12,[c] minmax(0,var(--col))) [wide-end c] minmax(pad,1fr) [full-end]`.
- **`--content-width` must be a PROPORTION of the viewport for this grid to hold, and getting
  that backwards has shipped twice.** The tracks have to sum to the viewport, so capping the 12
  columns leaves the `1fr` gutter as the only track that can absorb a wider screen — and nothing
  bounds a `1fr`. A fixed `1140px` band measured 150 / 390 / **710px** of gutter at 1440 / 1920 /
  2560; the fluid-but-capped band that replaced it still grew, 15.3% → 25.0% → 37.5% of total
  margin at 1440 / 2000 / 2560, because a cap only changes how fast the margin runs away, never
  that it does. `clamp(1140px, 85vw, 100vw)` holds it flat at 15% at every width above 1341px.
  `design-system.md` § Contenedores carries the value, its derivation and what it costs.
- Hero: copy left at ~58% width, ONE image bleeding the right viewport edge —
  `grid-column: c 8 / full-end`. **Not** `margin-right: calc(50% - 50vw)`: percentage margins on a
  grid item resolve against that item's own grid area, not the container, so the bleed overshoots
  (measured 312px past a 1265px viewport) and `overflow-x:clip` then hides the damage.
- Section headings: left-aligned, never centred; the eyebrow sits above and left.
- Images: exactly one bleed per section, always on the same edge down the whole page.
- Grids: two columns at 7/5 or 5/7, alternating direction section to section. Never 50/50.
- **ONLY MEDIA MAY REACH `full-start` / `full-end`.** A photograph touching the screen edge is a
  bleed. A paragraph touching it is an amputation. A CARD touching it is sliced — a card is a
  bordered surface carrying a heading and a paragraph, and the reader parses it as one object, so
  bleeding its image while insetting its copy does not make it a bleed, it makes it a card with a
  printing error (measured: frame right 2000.0, body ink 1968.0, and the reader called it cut off
  across two rounds of fixes). A FORM CONTROL touching it is a broken page: no hit-slop on one
  side, the border that says where the control ends coincident with the edge of the screen, and a
  name field 1453.3px wide at 2560. Card rows, copy blocks and forms end at the band — `c 13`,
  which is the same line as `wide-end`. `framework-audit.php`'s `RT_MOCKUP_BLEED_NOT_MEDIA` decides
  this from the selector's subject; `documentElement.scrollWidth === clientWidth` throughout every
  case above, so no overflow gate can see any of it.
  A rail that ends at `full-end` keeps the bleed on its images and steps its last card's TEXT back
  by the page padding. Measured on the gallery at 2560: the last case study's title sat at right
  `2560.0` on a 2560 viewport with `scrollWidth === clientWidth`, so nothing overflowed, no
  overflow gate could see it, and the reader read it as cut off — which it was, of paper.

### `LP-STRICT-GRID`
- Grid: 12 columns, one gutter (`--sp-m`), zero bleeds — every element starts and ends on a column line.
- Hero: copy in columns 1–6, image in columns 7–12, both inside the container, no overlap.
- Section headings: left-aligned on the first column line, sharing the grid with the content below.
- Images: one fixed aspect ratio per section (all 4:3 or all 1:1) so rows line up across the grid.
- Grids: 3 or 4 equal columns, equal gutters, equal card heights. Rows must visibly align.

### `LP-BROKEN-GRID`
- Grid: the same named-line 12 columns as `LP-ASYMMETRIC` — including its fluid `--content-width`,
  for the same reason — kept as a reference the page deliberately violates. Crossing the container
  is `grid-column: c 1 / full-end`; bleeding two edges is `full-start / full-end`. Naming a line is
  what makes the violation safe.
- Hero: oversized H1 crossing the container's right edge; the image sits BEHIND it, offset. **The
  copy is vertically centred in the hero row** (`justify-content:center` on the column-flex head),
  because the bleeding image sizes that row and the copy does not: measured, the row grew 484.9 →
  534.6 → 714.6px across 1440 / 1920 / 2560 while the copy ink stayed 399.5px, and everything
  under the CTAs was void. Do not give that frame `height:100%` and think it fills the row — the
  row is auto-sized, a percentage height has no definite basis, and it silently computes to `auto`.
- Every section: at least one element crossing a column line or overlapping its neighbour by ~`--sp-m`.
- Images: at least one per page bleeding two edges; overlaps stack with `z-index` in a shared grid
  row, never with negative margins that collapse on small screens.
- Grids: deliberately uneven columns (7/5, 4/8), with one card offset vertically by `--sp-l`.
- Mobile: every overlap collapses to a single stacked column — a broken grid at 430px is just broken.

## Chassis blueprints

A different axis from composition: composition fixes the GRID, chassis fixes how each content
BLOCK is physically bounded inside it — card, border, shadow, or nothing. Two anchors can share a
grid and still read as different sites if one bounds its cards and the other lets content sit bare
on the ground. Each chassis position is named by the blueprint id below.

### `CHS-BARE`
- No border, no fill distinct from the section ground, no shadow. Content sits directly on
  `--c-bg`; only spacing (`--sp-*`) separates one block from the next.
- A hover state may add `elevation: accent-glow`'s own token, never a border invented for chassis.

### `CHS-CARDED`
- Every content block is a rectangle filled `--c-bg-alt`, sharp corners or the shared
  `--radius` token, no shadow at rest or on hover.
- The fill is the whole boundary: no border is drawn in addition to it, or the block reads as
  `CHS-BORDERED` wearing a fill.

### `CHS-SOFT-CARDED`
- Filled `--c-bg-alt` at rest, exactly like `CHS-CARDED`, but the resting state adds no shadow —
  `elevation: soft-shadow`'s `--elev-hover` value fires only on `:hover`/`:focus-within`.
- The surface step (`--c-bg` → `--c-bg-alt`) is what reads at rest; the shadow is reserved for the
  moment of interaction, never present on the page as first painted.

### `CHS-BORDERED`
- A `1px` `--c-border` frame, `--c-bg` fill (not `--c-bg-alt`) — the border is the whole chrome, so
  no shadow and no fill step accompany it.
- Never combined with `CHS-CARDED`'s fill step: a bordered block on `--c-bg-alt` is two chassis
  positions worn at once, and the page stops declaring which one it means.

### `CHS-RULE-DIVIDED`
- No block boundary at all — content runs edge to edge on the section ground — and adjacent
  blocks are separated by a hairline rule instead of a card.
- The rule is the only chrome a block gets. A card, a border or a shadow anywhere in this
  chassis is a second position bleeding into this one.

### `CHS-HARD-SHADOW`
- A shadow with zero blur and a visible offset — `2px 2px 0 var(--c-text)` or equivalent — cast at
  rest, not only on hover, so blocks read as cut paper stacked on the page.
- Distinct from `CHS-SOFT-CARDED`'s diffuse, hover-only shadow: this one is sharp-edged and always
  on, the opposite resting state on purpose.

### `CHS-STRICT-GRID`
- No card, no border, no shadow — the same bare surface as `CHS-BARE` — but every block's edges
  land exactly on `LP-STRICT-GRID`'s column lines, with a single fixed gutter (`--sp-m`) as the
  only separation. Chassis and composition agree on the same grid on purpose here; that agreement,
  not a fill or a frame, is this position's whole chrome.
- A block whose edge falls between column lines is not this chassis, whatever else is true of it.

### `CHS-LAYERED`
- Blocks overlap by a fixed offset (`--sp-l`) with `z-index` establishing stacking order, and the
  block on top casts `elevation: soft-shadow`'s rest value onto the one behind it.
- Mobile collapses the stack to one column, offset removed — `LP-BROKEN-GRID`'s own mobile rule,
  reused here because an overlap that cannot collapse is not responsive, it is broken.

## The close is a designed moment

A closing section that exists is not a call to action. The template gallery shipped eight of them —
four `COMP-LEAD-FORM` bands and four `COMP-FAQ` + `COMP-CONTACT-DIRECT` pairs — and the reader's
report was *"no veo los call to action en las home"*. He was right and the sections were all there.
**A form is a form. An accordion is help. Three phone numbers under a heading are a footer that has
not admitted it yet** — and one of those closes carried no control at all, so the page ended by
listing ways to leave it.

**Three obligations, and they are the floor at every anchor:**

1. **Its own ground.** A close painted the same as the section above it is furniture.
2. **Exactly one control carrying weight.** This is where the accent budget
   (`web-templates/references/design-system.md` § "The accent has a BUDGET") makes its biggest
   single spend.
3. **An edge** — something that says the page is ending and is asking for something.

**Discharge them in the anchor's own language, never with one recipe four times.** `PERS-DIRECT`
closing loud and `PERS-EDITORIAL` closing quiet *is* the axis system working; both closing
invisibly is the defect. Spend only tokens the anchor already owns — its elevation, its ground, its
scale — so the close is the personality at full volume rather than a band bolted onto it:

| Anchor | Close | Because |
|---|---|---|
| `PERS-EDITORIAL` | the back cover — the ground inverts, `h2` steps up to the `h1` size, one high-contrast bar | `elevation: none` owns no fill and no shadow, so the only ending available is turning the paper over |
| `PERS-DIRECT` | a full-width field of the accent, control inverted to the page's ink | `ground: ink` + `elevation: accent-glow`, on the anchor whose brief is being unmistakable |
| `PERS-MATTER` | a plaque — the page's own paper inside a hairline frame, set into the alt ground | `elevation: hairline` says the border is all the chrome this anchor gets |
| `PERS-INSTITUTIONAL` | a bounded panel with the anchor's resting shadow, centred | `soft-shadow` + `LP-CENTERED` — the anchor's own gesture at page scale |

**A band that changes `--c-bg` must also RESOLVE the derived chain.** Custom properties substitute
at computed-value time on the element that declares them, so re-declaring the ground on the band
alone leaves `--c-text-muted`, `--c-border` and `--c-surface-inverse` carrying the values they
resolved to on the ancestor — a muted grey mixed toward white, painted on near-black. It renders,
it reads correctly in DevTools, and it is wrong. Name the band in the same selector list the chain
is declared on.

**Measure the muted tone on the field, not just the type.** On a saturated or inverted ground a
muted tone stops being quieter and starts being unreadable: the gallery's accent field measured
`#663216 on #FF6A1A` — **3.61:1**, live in a render that had already been looked at. **A field gets
one ink**, the same rule a photographic hero already follows, and hierarchy comes from size and
weight instead.

## Grid track counts

**A closed set gets a FIXED column count, decided by whoever knows how many items there are.**
`auto-fill` and `auto-fit` both hand that decision to the browser, and neither should have it.

The two look identical in a stylesheet and differ in one thing: `auto-fill` creates every column
that fits the container **whether or not there is an element for it**, and `auto-fit` collapses
the empty ones so the elements that exist share the width. Three team photos in a canvas that
fits four render, under `auto-fill`, as three cards squeezed against the left edge and a quarter
of the section empty — which is the defect a reader circles in red and calls *"falta algo aquí"*, because a reserved column looks exactly like a missing card.

It is the same shape as every other misalignment in this framework: **a container sizing itself
against the space available instead of against its own siblings.** Chips that wrap, an `auto`
column in an independent grid, an item with `min-width:auto` eating the gutter — and this one.

**`auto-fit` fixes the empty track and leaves the ragged row.** It collapses the columns nobody
fills, so the hole goes — but the BROWSER still picks how many tracks fit the width, so a set of
six renders 5+1 on a 2560 screen and 3+3 on a laptop, and nobody chose either. A last row with one
orphan in it reads as broken in exactly the way the empty column did.

**So the generator decides**, because it is the only place that knows the count. Two rules and a
cap: never more columns than items; among the rest take the WIDEST layout whose last row is at
least half full; never more than three, because a row can be full and still be wrong — six cards
across a 1560px canvas are 250px wide with a 190px photograph, which is a contact sheet and not a
listing. Six items give 3+3, five give 3+2, four give 2+2, seven drop to two columns because three
would leave a single orphan.

The half-full floor is the whole rule. Scoring only on *last row exact* makes FIVE items render as
five stacked rows of one, since 1 is the only count that divides 5 — and an orphan is ugly where a
single column of five cards is worse.

**And the canvas needs a ceiling.** `--content-width` capped at `100vw` is a band that grows
forever: 1740px at 2560, 2611px at 3840. Every grid, every measure and every photograph stretches
with it into sizes nobody designed. The cap belongs in the token, not in each grid.

`auto-fill` earns its place only where the empty track is the point: a calendar month, a seat
map, a contact sheet whose grid must not reflow as items are filtered out. None of those are
section layouts, so in a page they are the exception that has to argue for itself.
(verifier: `RT_MOCKUP_GRID_AUTOFILL` FAILs any `repeat(auto-fill` in a mockup asset that carries no `auto-fill:` justification comment beside it.)

## Disclosure lists (FAQ, accordion, spec tabs)

**The first row is open. The rest are not.** One rule, and it holds for every disclosure list on
the site — `COMP-FAQ`, `COMP-ACCORDION`, any spec block built the same way.

All closed reads as a wall of headings: the reader has to work to find out whether anything
inside is worth opening, and most decide it is not. All open is not an accordion at all — it is a
long page pretending to be a short one, and it throws away the one thing the control does. One
open row shows the SHAPE of an answer — how long it runs, what tone it takes — so the reader can
judge the other six without clicking any of them.

Which row is open is not a choice either: it is the FIRST. A list that opens its third row is
telling the reader the first two were filler, and if that is true the fix is to delete them.

**Open by default carries no styling of its own.** `<details open>` simply shows its panel, and
the row must read the same whether the reader opened it or it arrived that way. A first row
styled differently says *this one is special* when what it means is *start here*.

**Two columns on a wide screen, and two is a measure decision.** Splitting text into thirds of a
1560px canvas leaves about 35 characters a line, which cuts every answer into ribbons; two columns
land near 60 and read. The list rises to `108ch` because the usual `72ch` limit is the width of ONE
reading column, not of the block that holds two.

`align-items:start` is not cosmetic here. Without it, opening one row stretches its neighbour to
the same height and a gap appears beside the open answer — the defect a grid accordion ships with,
and the reason most people never put one in two columns. And the top rule moves from the list to
each row: a single rule drawn across two columns belongs to neither.

Native `<details>/<summary>`, never a div with state: no script, works with JS off, and the
platform already handles keyboard and screen readers. Detail:
`html-mockup/references/mockup-guide.md` § "Section blueprints".
(verifier: `RT_MOCKUP_DISCLOSURE_STATE` FAILs a mockup whose disclosure block is not `<details>` or does not open exactly its first row.)

## Responsive rules
- Header as a column on mobile: top row (logo · menu · cart, aligned) + a full-width CTA row below.
- Two-column product grids need equal-height cards (see `motion.md`).
- Never let a nested container force `content_width:full`/100% and push siblings to a new row.
- Test at 320, ~430 (phone), 768 (tablet), 1024, 1280 (desktop), 1440, 1920 **and 2560**. Verify
  the header stays one row on desktop. **2560 is not optional and it is not a luxury**: stopping at
  1280 is why a fixed `--content-width` beside a viewport-edge bleed shipped, and stopping at 1920
  is why it shipped twice. Every defect in this file's measured notes is invisible at 1280 and
  obvious at 2560. 1024 belongs on the list too — it is the first width where the desktop grid is
  on while the viewport is still narrower than the band, which is where an uneven `fr` rail falls
  under the width of the word it has to carry (fix: `minmax(min-content, <n>fr)`, not a bare `fr`).
