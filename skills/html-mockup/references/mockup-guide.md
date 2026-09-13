# Mockup Guide — HTML shell + conventions

How to render a resolved template into a self-contained, responsive HTML mockup for Artifact
publishing. The mockup is the visual contract; keep it faithful to the tokens so the native build
can reproduce it. This file is not boilerplate: the one-Artifact rule below is binding.

## Base shell

The Artifact host wraps the file in `<!doctype><head></head><body>`. Write page content only, with
one inline `<style>`. No external anything.

```html
<style>
  :root{
    /* ══ AXIS POSITIONS — the only lines you replace per project ══
       Copy the row for each RESOLVED position out of
       web-templates/references/design-system.md § "Perceptual axes — token values". Never type a
       number here and never re-derive one: these five lines are the whole visual identity, and a
       hand-picked value is how every site ends up looking the same. The values below are that
       file's `classic` scale row and `standard` density row, present only so the chain underneath
       is runnable as written. */
    --type-ratio: 1.333;  --display-lh: 1.10;  --fs-h1-max: 64;      /* scale: classic */
    --sp-scale: 1.0;                                                 /* density: standard */
    --c-bg:#ffffff; --c-bg-alt:#f4f2ee; --c-text:#1a1a1a;            /* ground */
    --elev-rest:none; --elev-hover:none;                             /* elevation: none */
    /* composition: LP-CENTERED */

    --font-primary: system-ui, sans-serif;
    --font-secondary: var(--font-primary);

    /* ── Type scale, transcribed VERBATIM from design-system.md § Scale. Every heading step hangs
          off --type-ratio and --fs-h1-max; not one heading size is written by hand, and each
          step's preferred term interpolates ITS OWN floor into ITS OWN cap across 430 → 1280 so
          the cap engages on a laptop. What used to sit on this line — `clamp(2rem,5vw,3.5rem)` —
          IS the defect the axes replaced: a 56px h1 cap on every client site, byte-identical at
          all four scale positions. --fs-h1-max is UNITLESS on purpose: calc() cannot divide a
          length by a length, so the coefficient multiplying --fluid must arrive without a unit,
          and --fs-base is the single bridge back to a length. ── */
    --fs-base: 16;
    --fluid: clamp(0px, calc((100vw - 430px) / 850), 1px);
    --n-h1: calc(var(--fs-base) * var(--type-ratio) * var(--type-ratio) * var(--type-ratio));
    --n-h2: calc(var(--fs-base) * var(--type-ratio) * var(--type-ratio));
    --n-h3: calc(var(--fs-base) * var(--type-ratio));
    --n-h1-cap: var(--fs-h1-max);
    --n-h2-cap: calc(var(--fs-h1-max) / var(--type-ratio));
    --n-h3-cap: calc(var(--fs-h1-max) / var(--type-ratio) / var(--type-ratio));
    --fs-h1: clamp(calc(var(--n-h1) / var(--fs-base) * 1rem),
                   calc(var(--n-h1) / var(--fs-base) * 1rem + (var(--n-h1-cap) - var(--n-h1)) * var(--fluid)),
                   calc(var(--n-h1-cap) * 1px));
    --fs-h2: clamp(calc(var(--n-h2) / var(--fs-base) * 1rem),
                   calc(var(--n-h2) / var(--fs-base) * 1rem + (var(--n-h2-cap) - var(--n-h2)) * var(--fluid)),
                   calc(var(--n-h2-cap) * 1px));
    --fs-h3: clamp(calc(var(--n-h3) / var(--fs-base) * 1rem),
                   calc(var(--n-h3) / var(--fs-base) * 1rem + (var(--n-h3-cap) - var(--n-h3)) * var(--fluid)),
                   calc(var(--n-h3-cap) * 1px));
    --fs-body: clamp(1rem,1.2vw,1.25rem);   /* body is NOT an axis — a plain vw term is correct */
    --fs-small:.875rem; --fs-eyebrow:.75rem; --fs-price: clamp(1.1rem,1.6vw,1.35rem);
    --fs-price-old:.95rem; --fs-button:1rem; --fs-nav:.95rem;

    --c-primary:#1a1a1a; --c-secondary:#444; --c-accent:#c8642d;
    --c-text-muted:#6b6b6b; --c-border:#e5e1d8;
    --c-success:#2e7d32; --c-error:#c62828; --c-sale:#c8322d;

    /* ── Spacing, verbatim from design-system.md § Density: ONE scale multiplied whole by the
          density position, and --sp-section carrying --sp-scale on BOTH ends of the
          interpolation — which is what makes the density axis visible at 430, 768 AND 1280
          instead of only at the extremes. Fixed rems here (`--sp-l:3rem`) flatten it. ── */
    --sp-xs: calc(0.5rem * var(--sp-scale));  --sp-s:  calc(1rem   * var(--sp-scale));
    --sp-m:  calc(1.5rem * var(--sp-scale));  --sp-l:  calc(3rem   * var(--sp-scale));
    --sp-xl: calc(5rem   * var(--sp-scale));  --sp-xxl:calc(7.5rem * var(--sp-scale));
    --n-sec:     calc(2 * var(--fs-base) * var(--sp-scale));
    --n-sec-cap: calc(7 * var(--fs-base) * var(--sp-scale));
    --sp-section: clamp(calc(var(--n-sec) / var(--fs-base) * 1rem),
                        calc(var(--n-sec) / var(--fs-base) * 1rem + (var(--n-sec-cap) - var(--n-sec)) * var(--fluid)),
                        calc(var(--n-sec-cap) * 1px));

    --container-max:1280px;
    /* A PROPORTION OF THE VIEWPORT, and neither a flat `1140px` nor a capped band — design-system.md
       § Contenedores has the measurements and the derivation. A blueprint that bleeds to `full-end`
       puts the viewport edge on one side of the content and an UNBOUNDED gutter on the other, so a
       capped band drifts the whole composition right as the screen grows. A cap only changes how
       fast: 150/390/710px of dead margin on a flat 1140, then 15.3%/25.0%/37.5% of total margin at
       1440/2000/2560 on the capped one. `85vw` holds the margin at 7.5% per side at every width
       above the 1341px knee, and the 1140px floor keeps everything at or below 1280 unmoved. The
       cap protected line length; cap the MEASURE instead — `.lede{max-width:66ch}` — because at
       2560 the lede was the only run on the page that outgrew it (103.1ch; everything else 78.2ch
       at 1440 and at 2560 alike). */
    --content-width:clamp(1140px, 85vw, 100vw);
    --pad-x-mobile:20px; --pad-x-tablet:32px; --pad-x-desktop:5%;
    --radius-card:12px; --radius-button:8px; --radius-image:8px; --radius-input:8px; --radius-container:16px;
    --ease:cubic-bezier(.22,1,.36,1);
  }
  @media (prefers-color-scheme: dark){
    :root{ --c-bg:#141414; --c-bg-alt:#1e1e1e; --c-text:#f2f2f2; --c-text-muted:#a8a8a8; --c-border:#333; }
  }
  *{box-sizing:border-box} body,figure,h1,h2,h3,p{margin:0}
  /* `overflow-wrap:anywhere`, not `break-word`: only `anywhere` shrinks a box's intrinsic
     min-content size, so a heading sized `fit-content` actually breaks its longest word instead of
     staying wider than its column. Measured on a proof mockup, `break-word` left 58px of
     horizontal scroll at the 320px reflow width (WCAG 1.4.10) untouched.
     SCOPED UNDER 1024, matching `_build-gallery.php:7145-7150`: unscoped it also breaks words at
     widths where they FIT, and the proof pair shipped `subcontratamo / s` at 1280 that way. */
  body{font-family:var(--font-secondary);color:var(--c-text);background:var(--c-bg);
       font-size:var(--fs-body);line-height:1.6}
  @media(max-width:1023px){ body{overflow-wrap:anywhere} }
  /* AND DISPLAY TYPE IS EXEMPT EVEN INSIDE THAT SCOPE — the half the scope alone does not fix.
     Under 1024 the headline is at its LARGEST relative to its track, so `anywhere` chops it there:
     see § "An overflow check cannot see a chopped word". The pair below travels together — the
     exemption stops the chop, the guard stops the overflow the exemption would otherwise cause. */
  h1,h2,h3{overflow-wrap:normal;word-break:normal}
  /* h1/h2 take the display leading the scale axis fixes; h3 stays 1.25 and body 1.6. A fixed 1.15
     for every heading was the other half of the defect above. Weights per design-system.md. */
  h1,h2,h3{font-family:var(--font-primary);text-wrap:balance}
  h1,h2{line-height:var(--display-lh);font-weight:700}
  /* `Ncqi` is the guard, and N is DERIVED from the longest word — never picked. Each block that
     carries a heading declares `container-type:inline-size`, or `cqi` silently falls back to the
     viewport and measures the wrong box. */
  h1{font-size:min(var(--fs-h1),17cqi)} h2{font-size:min(var(--fs-h2),12cqi)}
  h3{font-size:var(--fs-h3);line-height:1.25;font-weight:600}
  .wrap{max-width:var(--content-width);margin-inline:auto;padding-inline:var(--pad-x-mobile)}
  /* Fluid, density-scaled, and no breakpoint by hand — that is the point of --sp-section. */
  section{padding-block:var(--sp-section)}
  .btn{display:inline-block;padding:.875rem 1.75rem;border-radius:var(--radius-button);
       font-size:var(--fs-button);font-weight:600;text-decoration:none;transition:transform .35s var(--ease),background .2s}
  .btn-primary{background:var(--c-primary);color:#fff;border:1.5px solid var(--c-primary)}
  .btn-outline{background:transparent;color:var(--c-text);border:1.5px solid var(--c-primary)}
  .btn:hover{transform:translateY(-3px)}
  /* `width` MUST be pinned alongside `aspect-ratio`. With a ratio and no width, an auto width is
     computed FROM the ratio and the available height — measured at 660px inside a 390px column,
     a 250px page overflow. Pinning width makes the ratio drive height only.
     `min-width:0` is the OTHER half, and `width:100%;max-width:100%` does not cover it: on a grid
     or flex item, `aspect-ratio` transfers an automatic minimum size through the ratio, and
     `min-width:auto` outranks `max-width:100%`, so the box refuses to shrink below what the ratio
     asks for. Measured at 1280 with a 28px root: 638.3px inside a 613.3px column, 23px of page
     overflow — 170px at a 32px root. Both lines or neither. */
  .ph{background:var(--c-bg-alt);border:1px dashed var(--c-border);border-radius:var(--radius-image);
      display:grid;place-items:center;color:var(--c-text-muted);font-size:var(--fs-small);
      width:100%;max-width:100%;min-width:0;aspect-ratio:4/3}
  .grid{display:grid;gap:var(--sp-m)}
  @media(min-width:768px){ .wrap{padding-inline:var(--pad-x-tablet)} }
  @media(min-width:1024px){ .wrap{padding-inline:var(--pad-x-desktop)} }
</style>
```

## Typefaces — name it AND embed it

A mockup that names a family it does not carry renders the fallback, and everyone who looks at it
reviews the fallback while believing they reviewed the design. That is not hypothetical: every
mockup in this skill named real families and shipped none of them, so the EDITORIAL anchor was
judged as Georgia and DIRECT as Arial Black for as long as they existed. **No craft layer rescues
the wrong typeface.**

The bytes live in `assets/fonts/` — one `latin` woff2 per family, each with the `OFL.txt` its
licence requires beside it. `_fonts.md` is the manifest: family, file, axes, licence, copyright,
sha256 and source URL per row, and the reasoning behind each. Read it before adding a family.

- `_fonts.php` holds the registry and emits the `@font-face` block.
- `_embed-fonts.php` writes that block into the two proof mockups, between `NM-FONTS:BEGIN` and
  `NM-FONTS:END`. Re-runnable; `--check` reports staleness without writing.
- `../gallery/_build-gallery.php` calls the same registry when it generates its page and the
  chassis — the chassis needs no separate embedding step.

Rules that are checked rather than trusted (`RT_MOCKUP_FONT_NOT_EMBEDDED`):

- **Embed as a `data:` URI, never a URL.** The Artifact CSP blocks external requests; a `data:`
  URI makes no request, so there is nothing to block. The older instruction here — "never
  `@font-face` at a URL" — was right about `url(https://…)` and was misread as forbidding
  `@font-face` at all.
- **First in the stack is the design; the rest are the safety net.** `font-family: A, B, C` has to
  embed A. B and C are honest fallbacks and need nothing.
- **Only the `latin` subset.** It covers `U+0000-00FF`, which is all of Spanish. Fetch it with a
  current-browser user agent or Google serves TTF at roughly four times the size.
- **Declare the weight range the font actually holds.** A wider range silently suppresses the
  browser's synthetic bold, hiding a heading that asks for a weight nobody drew. Watch for this:
  synthetic bold does not change advance width, so measuring width detects none of it.

## Placeholder recipe
- Images: `<div class="ph" style="aspect-ratio:16/9">imagen</div>` — never a real client photo.
- Copy: short neutral placeholders ("Título de sección", "Categoría", "0,00 €" — euros, house
  rule). Mark clearly it's a preview.
- Product/category cards (ecommerce): `.ph` for the image + placeholder name + price token.
- Corporate: no prices unless the template resolved `COMP-PRICING`; no cart, ever.

## Section blueprints (mobile-first)
Build only the sections the template resolved as kept/on, in the template's order. Each mirrors the
per-breakpoint notes of the `TPL-*.md`.

### Shared (both site types)
- **Hero**: full-bleed block using `--c-bg-alt`; height per `TGL-HERO-HEIGHT` (`min-height:45vh`
  mobile → target vh desktop via `@media`); H1 + CTA. Slider vs fixed per `TGL-HERO-TYPE` (a static
  first-slide is fine for the mockup; note "slider" in a caption). **Never a capture form here** —
  house rule; the lead form belongs to the closing conversion band. On a split hero the second
  column is an image `.ph`, not a form card.
- **Editorial / Story**: 1 col mobile → 2 cols (`grid-template-columns:1fr 1fr`) desktop, alternating.
- **Benefits / Features** (`COMP-FEATURES`): 2×2 mobile → row of 3–4 desktop.
- **Testimonials**: 1 per view (snap row) mobile → 3 cols desktop.
- **Banner CTA** (`COMP-CTA`): full-width `--c-bg-alt` band, centered H2 + one accent button.
- **Header/Footer**: sticky header with logo + nav placeholder. The **logo always links to
  home**. Footer columns collapse to stacked on mobile. Reuse the exact same header/footer across
  every page of the set. Ecommerce ends the header with a **cart icon + count badge** (never a text
  label); corporate ends it with the primary CTA button (contacto / reserva) instead.

### Ecommerce-only
- **Category grid**: `.grid` 1 col mobile → `repeat(3–4,1fr)` desktop.
- **Product grid/carousel**: `.grid` 2 cols mobile → 4 desktop (grid); for carousel, a horizontal
  `overflow-x:auto` row with `scroll-snap`.
- **Newsletter**: stacked mobile → inline (`display:flex`) desktop.
- Prices render in **€** (house rule) — ecommerce path only.

### Corporate-only
- **Services** (`COMP-SERVICES`): card grid, 1 col mobile → 2 tablet → 3 desktop; each card = icon
  `.ph` (`aspect-ratio:1`, ~48px) + H3 + 2 lines + "Ver más" ghost link. One card recipe site-wide.
- **Lead form** (`COMP-LEAD-FORM`): stacked fields full-width mobile → 2-col field grid with the
  submit spanning both desktop; labels above inputs, `--radius-input`, one solid accent submit.
  Inert in the mockup — never wire a real endpoint. It renders in the **closing conversion band**,
  never in the hero.
- **Service detail** (`TPL-SERVICE-01`): one page per corporate service/area. H1 = the service as
  the client searches it. "What we solve" is a 1-col mobile → 2-col desktop grid of the card recipe
  without icons; scope reuses the 4-up card row; FAQ is on by default here. It ends with a **sibling
  cross-link block** — compact cards to the other services plus "see all" — which is FIXED, not a
  toggle: without links between siblings each service hangs off the home alone. Reuse the site-wide
  card recipe; do not invent a second one for this page.
- **Process / Steps** (`COMP-PROCESS`): numbered vertical list with a left rule mobile → row of
  3–4 columns desktop; big muted step number + H3 + one line.
- **Cases / Projects** (`COMP-CASES`): 1 col mobile → 2 desktop; `.ph` 16/9 + client placeholder +
  one-line result. Card opens the case page only if the page set resolved one.
- **Logos** (`COMP-LOGOS`): row of neutral `.ph` boxes (`aspect-ratio:5/2`, grayscale), 2 cols
  mobile → 5–6 desktop, low contrast so they never compete with the accent.
- **Stats** (`COMP-STATS`): 2×2 mobile → row of 3–4 desktop; big number (`--fs-h2`) + short label
  in `--c-text-muted`. Numbers are placeholders until the client confirms them.
- **Team** (`COMP-TEAM`): 2 cols mobile → 3–4 desktop; `.ph` `aspect-ratio:1` (or 3/4) + name + role.
- **Portfolio grid** (`COMP-PORTFOLIO-GRID`): the visual centerpiece of TPL-C-03 — 1 col mobile →
  2–3 desktop masonry-ish grid of `.ph` blocks, minimal chrome, caption on hover only.
- **Pricing** (`COMP-PRICING`): stacked cards mobile → 3 cols desktop, middle plan highlighted with
  the accent border (one accent only); feature list as plain `ul`.
- **FAQ** (`COMP-FAQ`): native `<details>/<summary>` accordion, full width, `--c-border` divider
  per row. No JS needed. **The FIRST row carries `open` and no other one does** — all closed is a
  wall of headings, all open is not an accordion. Same recipe and same rule for `COMP-ACCORDION`:
  they are one control doing one job, and the day they had four implementations they had three
  behaviours. Why, in full: `ux-design-system/references/layout-patterns.md` § "Disclosure lists".
- **Booking** (`COMP-BOOKING`): stacked service + date/time selects mobile → inline row desktop,
  ending in the solid accent CTA. Inert placeholder, no real calendar.
- **Map / NAP** (`COMP-MAP-NAP`): map is a `.ph` block (`aspect-ratio:16/9`) — never an embedded
  iframe (Artifact CSP blocks it). Beside it (stacked mobile → 2 cols desktop) the NAP block:
  name, address, phone, hours as a small `dl`.

## Responsive rules
- Never let `body` scroll horizontally. Carousels scroll inside their own `overflow-x:auto`.
- Use relative units and `max-width:100%`. Test the three breakpoints before publishing.

## Multi-page preview: ONE Artifact, in-page switching (binding)
The whole page set ships as **one** HTML file published as **one** Artifact, with each page as a
`<div class="page">` section switched by in-page JS (`show(id)` toggling `.active`). **Never** one
mockup per page, never one Artifact per page, never `target="_top"` links between Artifacts —
cross-artifact navigation is dead in the sandbox (dead clicks, "logo doesn't return home").
Keep the **header, announcement bar and footer as global elements OUTSIDE the `.page`
containers** so they never disappear when the active page changes. The logo switches to `home`;
nav items switch pages; on ecommerce the cart icon switches pages and product cards open the PDP.
The per-page split survives only as the **handoff spec** — one section inventory per page for the
native build — never as separate mockup files or URLs.

## The section chassis — three wrappers, and the default is the old one

Every section of every template used to be the same four nodes — `section > .canvas > .head.stack >
list > .pnote` — and that constant is why two archetypes with different section INVENTORIES could
still read as one page with a different palette. `RT_TPL_TOO_SIMILAR` measures the inventory, which
is the half a document can declare; nothing measured the wrapper, so nothing stopped it from being
identical everywhere. It took a reader looking at a finished archetype to say "sigue todo igual".

There are three wrappers now. `contained` is byte-for-byte the old one, so nothing that does not ask
for another moves:

| Shape | What the section becomes | When |
|---|---|---|
| `contained` | band holding a centred `.canvas` | the default; a list, a grid, a form |
| `row` | **the section IS the row** — direct children are the columns, no canvas | copy beside media; one node fewer |
| `bleed` | the section IS the band and spans the glass, children keep their own padding | colour and photography edge to edge |

Five rules travel with them, every one learned by rendering rather than by reading CSS:

- **A wrapper that is not `.canvas` must switch off `.canvas`'s placements.** The three compositions
  place canvas children by NAMED grid lines (`.head` at `c 1 / c 8`, the note in its own column) and
  those names exist only on the canvas grid. Inside a row or a band the browser invents implicit
  columns instead — measured, a heading and its note came out in two columns one word wide.
- **What bleeds is the colour, not the copy.** A band has `padding-inline:0`; any direct text child
  has to take the content width back, or it starts at x=0 on the glass. `RT_MOCKUP_BLEED_NOT_MEDIA`
  admits the band class `bleedband` for exactly this shape and FAILs if it is ever found on anything
  other than a `<section>` — an item inside a grid claiming the gutter is the defect that row exists
  for, and a band is not an item.
- **A band needs vertical rhythm too, and it is easy to forget.** `row` carried a `gap` from the
  first day; `bleed` only switched off the side padding, so its children stacked on whatever margins
  they brought from home. It went unnoticed while every band held panels with boxes of their own —
  the first band with a heading as a DIRECT child rendered the title sitting on top of the first row
  of photographs. Give the band one column and a gap: it decides its children's width, not their
  breathing.
- **"Bleed" does not mean the same thing for every kind of content.** A map or a grid of frames with
  no captions reaches the glass, because there is not one letter on it. A grid of work with a name
  and a year under each frame does not: measured, the first caption started at x=0, glued to the
  edge. That grid still escapes `--content-width`, it just stops at the page gutter — and its frames
  still come out ~13% larger than contained. Ask what the content carries before choosing the edge.
- **Composition placements belong INSIDE the canvas selector, not beside it.** A rule written
  `.grid-sec .head{grid-column:c 1/c 9}` also reaches sections that swapped the canvas for another
  wrapper, where the line named `c` does not exist. Measured: in the `broken-grid` composition it
  broke the FOUR `direct` variants of the four archetypes that had just gained a band — a heading
  flush against the right edge and a map **two pixels wide**. The other four anchors were fine, so
  looking at one strip would never have found it; a sweep of all twenty-four bands did.
  The temptation is to raise the reset's specificity until it wins. That is the third tie this
  system has had between two rules, and the answer is never to shout louder — it is to say where
  each rule lives. A placement that speaks the canvas's line names is written inside the canvas.

## Container hygiene — the mockup's DOM is a blueprint

The native build reproduces this file section by section, so every wrapper `<div>` here becomes an
Elementor container there. A mockup nested five levels deep teaches the build to nest five
containers, and each level is then paid three times on the live site: a wrapper in the DOM, a block
of generated CSS, and one more click between a human and the widget they opened the editor to
change. The three rules below are builder-agnostic, and **this is where they start** — fixing them
downstream in `elementor-core` means fixing them after the client already approved the shape.

1. **The section IS the row.** A two-column band is `<section>` with `display:flex` and the two
   halves as direct children — not a section wrapping a row `<div>` wrapping the halves. Stack at
   `<768` with `flex-direction:column`.
2. **A width does not justify a `<div>`.** Wrapping an element to make it 58% wide buys a node for
   nothing; put the width on the element. In the native build this becomes `_element_custom_width`
   (`es_wide()`), and a wrapper here becomes a whole container there.
3. **A photo is an `<img>`, not a `background-image`.** Use `<img>` with `object-fit:cover` and a
   real `alt`. A CSS background needs an otherwise-empty element to live in, is invisible to
   screen readers and to Google Images, and maps to the exact container `es_photo()` exists to
   avoid.

Target depth `section > grid|row > element`. Past three levels, have a reason.
Mirror of `elementor-core/references/gotchas.md` → "Container hygiene", which carries the
measured before/after from the build where these were found.

## Handoff
On approval, list the sections present per page (in order) + the token values used. `elementor-core` /
`divi-core` reproduce this NATIVELY; `qa-review` compares the native output against this mockup.

## What the gallery cost to learn

Every line here was paid for by a defect that shipped and had to be found by looking at a render.
The measurement is kept beside the rule, because a rule without one is an opinion that the next
reader is free to weigh against their own taste — and losing that argument is how most of these
came back a second time.

**Each item says whether a gate enforces it.** That distinction is the point of the section. This
file already carried a correct paragraph about invalid `var()` substitution weeks before a
`--sp-2xl` typo silently deleted a whole `padding` declaration, so writing the explanation is
demonstrably not the same as installing the check. Where the column says *no gate*, the rule
survives only as long as somebody reads it.

### A brand changes the skin. Only an archetype changes the skeleton.

The first branded template shipped with its own ground, accent, typeface and seven photographs of
its own, and read as *"exactamente igual a las otras, igual 100% pero cambiando colores"* — because
it was: same hero shape, same three-card grid, same booking block in third position, same close.
`RT_TPL_TOO_SIMILAR` had judged that distance all along (two archetypes of a family may share at
most half their combined inventory) but it reads `TPL-*.md` docs, and a brand sitting on somebody
else's archetype declares no wireframe. **A new business needs a new archetype doc**, or the
catalogue is one template with a colour picker.

*Gate:* `_build-gallery.php` fails if two catalogue entries share an archetype once either is
branded, and if a branded entry's archetype has no doc. The house axis-proof strips are exempt by
`brand === ''`, a property of the data rather than a list of ids.

### An invalid `var()` does not degrade — it deletes the whole declaration

`padding:var(--sp-2xl) clamp(2.5rem,6vw,6rem)` rendered with **no padding at all**, not a small
one. The token is `--sp-xxl`; `--sp-2xl` never existed, and an invalid substitution invalidates the
declaration at computed-value time, so the property takes its initial value and the perfectly good
`clamp()` in the other half goes with it. No warning, no console error, valid CSS. The same trap
had already been documented on `letter-spacing`, where an invalid value falls back to `normal` and
leaves an optical ramp silently unapplied.

*Gate:* every `var(--token)` in the emitted stylesheet must name a token the stylesheet declares.
`var(--x, fallback)` is exempt — naming a fallback is a statement, a typo is not.

### Two archetypes may not wear the same class name

A closing band shipped as `<section class="sec hours closing">`, and `.hours` was already another
archetype's NAP definition list: `display:grid`, `gap:.2rem`, `font-size:small`. Those three
declarations landed on a whole section, whose `.canvas` became a grid item instead of a grid, and
the heading ran off the left edge of the viewport. Two archetypes sharing a class is valid CSS that
renders, which is the profile of every defect on this list.

*Gate:* the classes an archetype introduces are listed and must be undefined in every byte of CSS
emitted before its block. The list is hand-kept — a new class added without a line there is
unchecked.

### An overflow check cannot see a chopped word

**A mid-word chop produces exactly ZERO overflow, because the chop is what prevents it.** So a
sweep that measures `scrollWidth - clientWidth` at every width reports a perfectly clean page whose
headline reads `PIEZA / S QUE / NO SE / REPIT / EN`. Measured on BSP-VITRINA at 320: an `h1` was
chopped on all SEVEN pages while the overflow sweep returned 0 everywhere, and the reader found it
in the render before the sweep did. **Two gates, not one** — the second is a `Range` per WORD,
counting `getClientRects()`; more than one rectangle means that word was split. `qa-review`
house-rules row 32(e) carries it.

The cause is `overflow-wrap:anywhere`, which is genuinely required under 1024 and which display
type cannot survive. **Scoping it under 1024 is not enough**, and that is the non-obvious half:
under 1024 the headline is at its LARGEST relative to its track, so the scope leaves the chop
exactly where it hurts. Display type is exempted, and then given a guard so it never NEEDS to break.

**The guard is a container unit, and this is where the first two attempts died.**

- `overflow-wrap:normal` alone trades a chopped word for an overflowing one.
- `min(var(--fs-h1), 13vw)` still overflowed — `nosotros:+51`, `contacto:+11` — because **a
  viewport unit measures the SCREEN and a heading lives in a GRID TRACK.** A title in 5 of 12
  columns overflowed at 768 while the viewport had room to spare.
- `min(var(--fs-h1), Ncqi)` works, and every block that carries a heading must declare
  `container-type:inline-size`. Miss one and `cqi` silently falls back to the small viewport: the
  close band was missed on the first pass and came back `home:+40`, because its `h2` measured
  325px of min-content inside a 265px track and, unable to break, grew the `1fr` track to 325 and
  took the page with it. Inline-size containment also stops a heading's min-content from sizing
  its own track, which is the second thing it buys.

**N IS DERIVED, NEVER PICKED.** Archivo Expanded 700 advances **0.757em per character**, measured
in the browser (`PIEZAS QUE NO SE REPITEN` = 1090.1px at 60px over 24 characters). A word of N
characters fits a track of width W at `W / (N × 0.757)`, so the coefficient is `100 / (N × 0.757)`
percent of the container: **17cqi carries a 7-character word, 12cqi a 10-character one.** Measure
the advance for the face actually in use — a condensed face gives a larger coefficient, a wide one
a smaller.

**What it costs, and say it out loud to the client.** A 6-of-12 track is 491px at 1280, so a
`scale: monumental` headline lands at ~83px there and reaches the axis's full 120px only from
~1920, where the track finally clears the 636px that 120px type needs. Measured across the range:
74px at 1024 · 83 at 1280 · 89 at 1440 · 120 at 1920 and 2560. **Keeping a two-column hero and
reaching the axis cap at every width are mutually exclusive**; the alternatives are a full-width
title row, or a hero whose image sits behind the copy instead of beside it. That is a client
decision, not a silent one.

**`hyphens:auto` is not the escape hatch** — `_build-gallery.php:7692` already recorded that the
headless renderer carries no Spanish dictionary, and it fails in the in-app browser too: with
`lang="es"` set and `hyphens` computing `auto`, the break of "relacionada" rendered 92.9px, exactly
the natural width of "relaciona" and not the 99.8px it would be with a hyphen drawn. Where a run's
length is client data and cannot be guaranteed — a product name in a card — give the component its
own container and its own `clamp()`, and keep `overflow-wrap:break-word` (which breaks only a word
that cannot fit a line alone) rather than `anywhere` as the floor.

**The corollary is a copy rule, and it belongs in the brief**: monumental display needs SHORT
WORDS. A 10-character word cannot sit in a two-column hero at this scale, so the headline changes
or the anchor does — never the guard.

*Gate:* `qa-review` house-rules row 32(e), measured in a browser at every width. Nothing static can
see it: the CSS is valid, the page does not overflow, and the defect is only in the render.

### `ch` measures the font of the element it is written on

`max-width:34ch` on a hero copy block resolved against the inherited **body** face at 1rem — about
270px — and put an 88px display headline in five one-word lines. The unit was right for a paragraph
and wrong for a block whose entire content is a headline. Cap display blocks in `rem`.

*No gate.* Nothing in the CSS text distinguishes a block that will carry display type.

### A masonry is `columns`, never a grid

Six frames placed on hand-picked column lines with hand-picked top margins left a hole under every
frame shorter than its row-mate, and the holes were the first thing anybody saw. **A grid places
items in rows, and a row is exactly what a masonry does not have.** `columns` + `break-inside:avoid`
has no rows to wait for; height variety comes from ratios cycling on `nth-child(3n+…)`.

*No gate.* Retiring the old layout also correctly retired its "exactly six items" assertion, which
was load-bearing only while six column lines were written by hand.

### The measure is composition, not an axis

`--content-width` is `clamp(1140px, 68vw, 100vw)` house-wide, which on a 2000px screen is 1360px of
live text — loud on any page built from short lines. An archetype may narrow it on `[data-arch]`,
which sits on the same element as `[data-anchor]`, so `--col` re-resolves through the shared chain.
**Scale, density, ground, blueprint and elevation stay the anchor's**: those five are printed on the
card as chips, and moving one silently turns a chip into a lie. Lowering a size you chose yourself —
a pull quote, a ribbon, a hours list — is free; lowering `--fs-h1-max` is not.

*No gate.* The distinction is about intent and cannot be read off a declaration.

### A full-bleed split needs more inside padding than a contained section

There is no page margin doing the work on the bleeding side, so the same `clamp()` that reads as
generous inside a container reads as a collision beside a photograph.

*No gate.*

### Measure the render; do not reason about it

Three hypotheses about the runaway heading were wrong before a measurement found the class
collision in one attempt. Copy the built page to `probe.html`, append a `<script>` that writes
`getBoundingClientRect()` and `getComputedStyle()` into `document.title`, and read it back:

```bash
chrome --headless=new --disable-gpu --hide-scrollbars --window-size=1440,1200 \
  --virtual-time-budget=9000 --dump-dom "file:///…/probe.html#route" | rg -o '<title>[^<]*</title>'
```

Chrome clamps `--window-size` at about 500px, so 320 and 430 both measure 500 — narrow viewports
cannot be captured this way.

## Chassis and anchor

**CHASSIS AND ANCHOR ARE TWO DECISIONS, and for a long time they were one.** Site type decides
the chassis — which pages exist, whether there is a cart, whether prices and a shipping bar
belong. The anchor decides how the whole thing looks, and it comes out of the `ux-design-system`
dialogue with the client. Nothing connects the two: a bakery and a law firm can want the same
anchor, and two law firms can want different ones.

The two starting assets each ship pointed at one anchor **only so they render** — an HTML file
has to carry values. `chassis/corporate.html` ships at `STY-INSTITUTIONAL`, `chassis/ecommerce.html`
at `STY-MATTER`. Neither is a recommendation.

**Generated, never hand-copied.** `_build-gallery.php` (`../gallery/_build-gallery.php`) writes
both from the exact same in-memory CSS tables the gallery renders — one run, two files, everything
reused verbatim except the gallery's own chrome (`.gal-top`, `.strip`, `.axis`). Generated, not
hand-edited: the next run overwrites each entire, same discipline as `index.html`. The two
hand-maintained originals this section used to describe, `corporate-mockup.html` /
`ecommerce-mockup.html`, are gone (style-catalog PR 1f) — run the generator, never copy a file.

**`--chassis-out=<dir>`, and why a real project must pass it.** Without it the generator writes the
two fixed demo files, and every project starts from one of those two paths and re-points its
`AXIS POSITIONS` block in place. That is fine for one project at a time and silently destructive
for two: the second run overwrites the first project's re-pointed chassis, and the generated header
says so in as many words. A project generating into its own directory cannot be overwritten by a
sibling. The default is unchanged, so the demo pair still exists and `RT_CHASSIS_NOT_BUILT` still
polices it — this adds a destination, it does not move one.

**Why this needed saying.** Until it did, step 1 read *pick the starting asset by site type* and
stopped there, and the `:root` comment read *Default anchor* — so every corporate project shipped
`STY-INSTITUTIONAL` and every commerce one `STY-MATTER`, not because anyone chose them but
because nobody was asked to. Measured against the other assets in the repo, those two are also the
quietest of the four: h1 caps of 48 and 64 against 88 and 120, type ratios of 1.200 and 1.333
against 1.500 and 1.618, and `--sp-scale: 1.0` on both — the only two with no density move at all.
So the default was not merely arbitrary; it was the tamest corner of the system, and every client
site started there.

**style-catalog PR 4c closed the marker-vs-value half of this, not the whole of it.** The generated
chassis now resolves each site type's `Anchor:` marker AND its `:root` tokens from the SAME lookup
(`$CHASSIS_STYLE_BY_SITE` in `_build-gallery.php`), so the two can no longer independently drift —
before this PR the marker was a hand-typed string that would have stayed `STY-INSTITUTIONAL` even
if `:root` were repointed to something else, silently. The lookup itself is still the same two
fixed defaults named above: there is no client, no project and no manifest at generation time for
this code to resolve against, so every corporate demo still starts on `STY-INSTITUTIONAL` and every
ecommerce one on `STY-MATTER` until `art-direction-ledger` (Slice 5) gives a real project's chassis
somewhere else to resolve FROM. That remaining gap is `art-direction-ledger`'s scope, not this
section's — a generated project's actual style comes from the `ux-design-system` dialogue above,
never from these two demo files.

**Re-pointing is six edits, and five out of six is the failure mode.** The block holds five token
lines plus the `composition` marker, which has no custom property of its own to ride on and is
therefore the one most easily forgotten. `RT_MOCKUP_AXES_MISMATCH` reads the labels against
`references/style-catalog/` and names each axis that disagrees with both positions, so a half-done
re-point FAILs instead of shipping as a site that is neither anchor.

That row also checks the VALUE: a `scale: contained` label beside a hand-typed `--fs-h1-max: 53`
now FAILs even though the label agrees with the anchor, because `--fs-h1-max` disagrees with
`contained`'s own row in `design-system.md` § "Perceptual axes — token values". Copy the numbers
from that table and never derive one — the `:root` comment has said so since the axes landed, and
this is the half of it that used to have no gate.

## Herramientas compartidas — extracción en curso (PR 1a)

The colour/contrast maths and the fingerprint this generator used to own alone now live under
`../assets/herramientas/` (`color.php`, `scrim.php`, `huella.php`), dual-mode: each file is a
plain PHP library `_build-gallery.php` (and, from a later PR, `framework-audit.php`) `require`s,
and each is its own CLI when run directly. `_build-gallery.php` keeps working unmodified at every
call site — only the failure signal changed, from an immediate `exit()` to a typed exception this
file catches and hands back to its own `fail()`. The full per-function inventory (`## Herramientas`,
naming every public function by name so `RT_HELPER_UNROUTABLE` never has to guess) lands once all
seven tools exist, later in this same PR chain.
