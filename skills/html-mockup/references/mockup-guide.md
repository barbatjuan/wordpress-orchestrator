# Mockup Guide — derivar la maqueta del lienzo

How a client's **maqueta** is derived from its **lienzo**. The lienzo is the client's Claude Design
canvas, drawn from the Plantilla's lienzo; the maqueta is one responsive `index.html` the client
approves, the veredicto judges and the native build reproduces. Nothing here is generated: the
maqueta is DERIVED, by hand, under the contract below. Vocabulary:
`web-templates/references/glosario.md`.

The library maquetas under `web-templates/references/plantillas/<slug>/maqueta/` are the practice
this contract describes. When this file and a library maqueta disagree, read the maqueta, then fix
whichever is wrong — never silently follow the older one.

The sections at the end marked **Legacy** describe the old generator, chassis and anchors. They are
not used for client work and stay only until the removal step.

## Contrato de derivación (binding)

### Inputs

| Input | Where | What it decides |
|---|---|---|
| The client's lienzo | Claude Design, one desktop artboard (1440) and one `-movil` artboard (390) per content page | Composition, copy, photographs. Design authority: every change starts here |
| The Plantilla's maqueta | `plantillas/<slug>/maqueta/index.html` | The derivation reference: routing, burger menu, derived system pages, the two breakpoints. Start from its structure, never from a blank file and never from the legacy generator |
| The Plantilla's ficha | `plantillas/<slug>/ficha.md` | ADN, `paginas:`, `fuentes:`, the Mapeo nativo whose first column is the maqueta's section ids, and the techo nativo |
| The page set | `web-templates/references/paginas-obligatorias.md` | Every page the site carries, and which are artboards and which are derived |
| The design spec | `ux-design-system` | Palette roles with measured ratios, type pair, and how each token lands in Elementor's global Site Settings |

Ruta a medida has no Plantilla maqueta to start from: take the routing, burger and system-page
structure from any library maqueta of the same type, and nothing of its look.

### Rules

1. **One file, nothing external.** `index.html` with inline `<style>` and inline `<script>`. No
   CDN, no remote image, no iframe, no remote font — the Artifact CSP blocks them and the reviewer
   would judge a broken page. An ordinary outbound `<a href>` in legal copy is a link, not a
   resource, and is allowed.
2. **Hash routing, one `<title>` per route.** Each page is a `<div class="page" id="<pagina>"
   data-title="…">` holding its `<section id="…">` blocks; navigation is `href="#<pagina>"`; the
   router shows one page and writes `document.title` from its `data-title` on every switch,
   because a document has one `<title>` element and each route still needs its own. A fragment that
   names a point inside a page opens that page and scrolls to it; a fragment that names nothing
   opens the 404 page, never the home. Header, burger menu and footer live OUTSIDE the pages, so no
   switch can remove them.
3. **The full page set.** Every entry of the ficha's `paginas:` plus the obligatorias for the type.
   Artboard pages are derived from their artboards; system pages (legales, 404, gracias, carro, pago,
   pedido recibido, mi cuenta) are derived from the Plantilla's system — header, footer, type,
   colours, form and table styles. Every «ver ficha» lands on a real detail page; no `#` or empty
   href survives.
4. **Two breakpoints, 1024 and 767, and no third.** Desktop CSS comes from the 1440 artboard;
   `@media (max-width:1024px)` and `@media (max-width:767px)` are the only queries. Elementor
   expresses exactly those two natively (`es-builder.php` accepts the `_tablet` and `_mobile`
   suffixes and nothing else), so any other width — a `min-width` query, a 900, a
   `prefers-color-scheme` block — is custom CSS authored two steps before `qa-review` counts it.
   The mobile layout, burger included, comes from the 390 artboard; 1024 is designed while deriving.
5. **Fonts embedded as `data:` woff2.** The `@font-face` block is emitted by `nm_font_faces()` in
   `../assets/fonts/_fonts.php` and sits between `/* NM-FONTS:BEGIN */` and `/* NM-FONTS:END */`.
   Only families the house registry holds (`../assets/fonts/_fonts.md`); the first family of every
   stack is the one embedded. A lienzo may load Google Fonts; a maqueta never does.
6. **Images as `../img/<slug>.webp`.** A photograph is an `<img>` with a real `alt`, never a CSS
   background. The filename is the WordPress attachment slug and is never renamed; every image has a
   manifest row (rol, origen, sesión, licencia, alt). The client's photographs replace the
   Plantilla's role by role, keeping the framing the manifest records.
7. **Tokens once, in `:root`, mapped to global Site Settings.** Colours and type families are
   declared once and map 1:1 onto Elementor's global colours and global fonts. A value with no
   global slot is a design change, written in the ficha's Mapeo nativo Nota column — never a CSS
   exception.
8. **Every section is native.** Each `<section id>` is a row in the ficha's Mapeo nativo. A section
   that cannot be mapped to native elements is redesigned in the lienzo, not kept.
   `backdrop-filter` is one measured example of a property with no native control.
9. **es-ES and real copy.** Prices as `68,00 €`, symbol last; peninsular Spanish; no placeholder
   phone number or lorem ipsum. A fictional brand carries fictional numbers that look real.
10. **Derived, not patched.** A change made only in the maqueta is lost at the next derivation and
    stales the veredicto. Change the lienzo, then re-derive.

### CSS lessons that travel into every derivation

Paid for by the legacy chassis and still true for a derived file:

- `overflow-wrap:anywhere` only under the 1024 breakpoint, never on display type, logos, menu,
  buttons or prices; see § "An overflow check cannot see a chopped word" for the guard.
- An element with `aspect-ratio` pins `width:100%;max-width:100%;min-width:0` — both halves, or a
  grid item refuses to shrink below the ratio and overflows the page.
- The content width is a proportion of the viewport (`clamp(1140px, 85vw, 100vw)`), and the
  reading measure is capped on the text block (`max-width:66ch`), not on the band.
- A grid never carries more tracks than children; a disclosure list is `<details>` with exactly the
  first row open (layout-patterns § "Disclosure lists").

## Medir la geometría antes de mirarla

`../assets/herramientas/medir-geometria.mjs` conduce Chrome headless por CDP sobre una maqueta o un
artboard y devuelve cinco números por página: el carril izquierdo y cuántos elementos aterrizan en
él, el margen expresado como FRACCIÓN del ancho a tres anchos (base, ×1,33, ×1,78) para ver si la
curva aguanta, la medida de lectura en caracteres, todo lo que llega al cristal sin ser media, y el
desbordamiento. Sale con 1 si hay tinta o un control tocando el cristal, o si algo desborda.

El margen se mide como fracción y no en píxeles a propósito: 64px es un margen distinto a 1440 que a
768, y el defecto que buscamos —«no hay casi márgenes»— es una fracción, no una distancia. El
estándar de la casa es `clamp(1140px, 85vw, 100vw)`, o sea 7,5% por lado por encima de la rodilla.

**Es un valor por defecto, no una ley, y la plantilla manda.** El margen es una decisión de
composición: `tecnologico` respira menos que `editorial`, y un `brutalista` que dejara 7,5% dejaría
de serlo. Once plantillas con el mismo margen son once plantillas que un juez de reconocimiento
agrupa como hechas por la misma mano, que es justo lo que la biblioteca existe para evitar. Así que
una plantilla puede llevar el suyo con dos condiciones: que su **ficha lo declare como fracción y
diga por qué**, y que la maqueta lo exprese como fracción, no en píxeles, para que aguante entre los
dos puntos de ruptura. Quien decide si un margen estrecho es enfoque o descuido es el barrido, que
mide la fracción a tres anchos y avisa de lo que llega al cristal: un margen declarado que el barrido
no señala es una decisión; uno que no está en la ficha es una deriva.

Dos advertencias pagadas:

- **Mide la TINTA, no la caja.** Un `<p>` con relleno horizontal tiene caja de ancho completo y
  texto metido hacia dentro; informar de la caja llama amputación a un párrafo correctamente
  sangrado. La herramienta recorre los nodos de texto y toma un `Range` por nodo.
- **Una herramienta nueva está equivocada hasta que un caso de control diga lo contrario.** De los
  cinco defectos que ésta reportó la primera vez, tres eran bugs suyos. Y cuando la medición
  discrepa del código, la medición es una hipótesis sobre el RENDER: se resuelve MIRANDO la página,
  nunca ajustando la herramienta hasta que coincida con la fuente.

El contraste se mide con `../assets/herramientas/color.php --maqueta <archivo-html>`, que re-mide
cada par del `:root` contra 4,5:1 en texto y 3:1 en interfaz. Texto sobre fotografía:
`../assets/herramientas/scrim.php --peor-pixel`.

Hoy no invoca estas herramientas ninguna regla de auditoría sobre una maqueta de cliente: las
ejecuta quien deriva la maqueta, antes de gastar un barrido. Dicho así y no disfrazado de puerta.

## Defectos de derivación

Antes de pedir un barrido o un juez sobre una maqueta derivada de un canvas, recórrela contra
`defectos-de-derivacion.md`. Es la lista de lo que sale mal al convertir artboards en una página
responsive: controles que un `appearance:none` deja en cero, descendentes que caen dentro de la
banda siguiente, cabeceras pegajosas translúcidas, rejillas entintadas que pintan su propio
relleno, bandas de filtro que se comen media pantalla de móvil, y teléfonos de relleno. Ninguno
dispara una regla, ninguno desborda, ninguno falla contraste — por eso están escritos.

## Multi-page preview: ONE Artifact, in-page switching (binding)

The whole page set ships as **one** HTML file published as **one** Artifact, pages switched by the
hash router of rule 2. **Never** one maqueta per page, never one Artifact per page, never
`target="_top"` links between Artifacts — cross-artifact navigation is dead in the sandbox (dead
clicks, "logo doesn't return home"). Header, burger menu and footer stay OUTSIDE the page containers;
the logo goes to `#inicio` (or `#portada`); on ecommerce the cart icon and every product card route
inside the same file.

**Publishing.** Title `<marca> — maqueta`, a favicon emoji, one-line description; iterations
republish to the same URL. The Artifact is a single page, so its images must travel inside it: until
a packaging tool exists, publish a scratch copy with each `../img/<slug>.webp` inlined as a `data:`
URI, and never edit the source file to publish it.

## Veredicto before client approval (binding)

A maqueta goes to the client only with a veredicto: `blind-judges` (judge B on this maqueta, judge A
against the recent deliveries and the library) and `visual-verification` (every page at 430, 768 and
1280), written in `web-templates/references/veredicto-formato.md`. A self-judged run is declared as
such, a partial sweep is PARCIAL, and neither is presented to the client as a pass. A change after
the veredicto re-derives, republishes and re-judges.

## Typefaces — name it AND embed it

A maqueta that names a family it does not carry renders the fallback, and everyone who looks at it
reviews the fallback while believing they reviewed the design. That is not hypothetical: every
legacy mockup named real families and shipped none of them, so the editorial anchor was judged as
Georgia and the direct one as Arial Black for as long as they existed. **No craft layer rescues the
wrong typeface.**

The bytes live in `assets/fonts/` — one `latin` woff2 per family, each with the `OFL.txt` its
licence requires beside it. `_fonts.md` is the manifest: family, file, axes, licence, copyright,
sha256 and source URL per row, and the reasoning behind each. Read it before adding a family.

- `_fonts.php` holds the registry and emits the `@font-face` block (`nm_font_faces()`).
- `_embed-fonts.php` writes that block between the `NM-FONTS` markers of the two legacy proof files;
  `--check` reports staleness without writing.

Rules that are checked rather than trusted (`RT_MOCKUP_FONT_NOT_EMBEDDED`, on the assets it walks):

- **Embed as a `data:` URI, never a URL.** A `data:` URI makes no request, so there is nothing for
  the Artifact CSP to block.
- **First in the stack is the design; the rest are the safety net.** `font-family: A, B, C` has to
  embed A. B and C are honest fallbacks and need nothing.
- **Only the `latin` subset.** It covers `U+0000-00FF`, which is all of Spanish. Fetch it with a
  current-browser user agent or Google serves TTF at roughly four times the size.
- **Declare the weight range the font actually holds.** A wider range silently suppresses the
  browser's synthetic bold, hiding a heading that asks for a weight nobody drew.

## Responsive rules
- Never let `body` scroll horizontally. Carousels scroll inside their own `overflow-x:auto`.
- Relative units and `max-width:100%`. Only the two breakpoints of rule 4.
- At 767 and below the header carries exactly one burger control with `aria-expanded`, and the
  desktop link list is hidden — the pre-library catalogue measured zero burger navs, which is what
  happens when mobile is reflowed instead of drawn.

## Container hygiene — the mockup's DOM is a blueprint

The native build reproduces this file section by section, so every wrapper `<div>` here becomes an
Elementor container there. A maqueta nested five levels deep teaches the build to nest five
containers, and each level is then paid three times on the live site: a wrapper in the DOM, a block
of generated CSS, and one more click between a human and the widget they opened the editor to
change. The three rules below are builder-agnostic, and **this is where they start** — fixing them
downstream in `elementor-core` means fixing them after the client already approved the shape.

1. **The section IS the row.** A two-column band is `<section>` with `display:flex` and the two
   halves as direct children — not a section wrapping a row `<div>` wrapping the halves. Stack at
   767 and below with `flex-direction:column`.
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
On approval, freeze the maqueta and hand over, per page: the section ids in order — each a row of
the ficha's Mapeo nativo — the tokens and their Site Settings slots, the photographs by slug, and
the sealed veredicto. `elementor-core` / `divi-core` reproduce it NATIVELY from the Mapeo nativo;
`qa-review` compares the build against this maqueta and counts HTML widgets and custom CSS against
the ficha's techo nativo.

## Herramientas

Dual-mode PHP files: each is a library another file can `require`, and its own CLI when run
directly. Shared exit contract: `0` pass, `1` a measured failure, `2` usage or environment — never
`0` for "could not measure".

| File | CLI | What it is for |
|---|---|---|
| `../assets/herramientas/color.php` | `--contraste <#hex> <#hex>`, `--maqueta <archivo-html>` | Contrast maths (`srgb_lum`, `contrast`, `ratio_str`, `css_mix`, `ink_tint`, `ink_ends`, `ink_curve`) and the `:root` pair re-measurement (`color_root_tokens`, `color_root_pairs`) |
| `../assets/herramientas/scrim.php` | `--peor-pixel <img.webp> <x> <y> <w> <h>` | Worst text-over-photo pixel (`worst_pixel`, `ink_mean`) |
| `../assets/herramientas/huella.php` | `--plantilla <slug>`, `--biblioteca`, `--comprobar <slug> <sha256>` | The LF-normalised fingerprint of a Plantilla and of the library (`huella_plantilla`, `huella_biblioteca`), and of an arbitrary folder (`huella_directorio_manifest`, `huella_directorio`) for a client delivery that is not `skills/`-shaped |
| `../assets/herramientas/veredicto.php` | `--sellar <slug>`, `--comprobar <slug>`, `--biblioteca`, `--sellar-ruta <carpeta>`, `--comprobar-ruta <carpeta>` | Seals a veredicto with its fingerprint and checks the seal — a Plantilla by slug (`veredicto_sellar`, `veredicto_comprobar`), or a client delivery folder by path (`veredicto_sellar_ruta`, `veredicto_comprobar_ruta`) |
| `../assets/herramientas/empaquetar.php` | `--plantilla <slug> --out <archivo>`, `--maqueta <archivo> --out <archivo>` | Packages a maqueta into one self-contained HTML file for Artifact publishing — every `../img/<file>` becomes a `data:` URI carrying that file's own type (`empaquetar_maqueta_plantilla`, `empaquetar_html`, `empaquetar_remotos`, `empaquetar_tipo`, `empaquetar_archivo`) |
| `../assets/herramientas/medir-geometria.mjs` | `<url> [--ancho 1440] [--json]` | Rail, margin fraction, reading measure, what touches the glass, overflow |
| `../assets/herramientas/barrido.mjs` | `<url-de-una-maqueta> [--json] [--anchos 430,768,1280]` | The `veredicto.md` Barrido sweep over a hash-routed maqueta: every `.page` at every width, four measurements — overflow, multi-track grids at 1280, phantom tracks and orphans, one menu toggle at 430. Before trusting a change to it, run it on the control page `tests/fixtures/barrido-control.html` at the repository root (a test fixture, so it never ships with the skill): fifteen pages, each broken on exactly one measurement or built to be a false positive the tool once raised (an action button beside the burger, a heterogeneous control row, a grid item spanning two tracks); each must fail on its own measurement only, and the clean ones pass |

## What the gallery cost to learn

Every line here was paid for by a defect that shipped and had to be found by looking at a render.
The measurement is kept beside the rule, because a rule without one is an opinion that the next
reader is free to weigh against their own taste — and losing that argument is how most of these
came back a second time. The gates named here police the legacy assets; on a client maqueta each
rule survives only as long as somebody reads it.

### An invalid `var()` does not degrade — it deletes the whole declaration

`padding:var(--sp-2xl) clamp(2.5rem,6vw,6rem)` rendered with **no padding at all**, not a small
one. The token is `--sp-xxl`; `--sp-2xl` never existed, and an invalid substitution invalidates the
declaration at computed-value time, so the property takes its initial value and the perfectly good
`clamp()` in the other half goes with it. No warning, no console error, valid CSS.

### An overflow check cannot see a chopped word

**A mid-word chop produces exactly ZERO overflow, because the chop is what prevents it.** So a
sweep that measures `scrollWidth - clientWidth` at every width reports a perfectly clean page whose
headline reads `PIEZA / S QUE / NO SE / REPIT / EN`. Measured on a bespoke mockup at 320: an `h1`
was chopped on all SEVEN pages while the overflow sweep returned 0 everywhere, and the reader found
it in the render before the sweep did. **Two gates, not one** — the second is a `Range` per WORD,
counting `getClientRects()`; more than one rectangle means that word was split. `qa-review`
house-rules row 32(e) carries it.

The cause is `overflow-wrap:anywhere`, which is genuinely required under 1024 and which display
type cannot survive. **Scoping it under 1024 is not enough**: under 1024 the headline is at its
LARGEST relative to its track, so the scope leaves the chop exactly where it hurts. Display type is
exempted, and then given a guard so it never NEEDS to break.

**The guard is a container unit, and this is where the first two attempts died.**

- `overflow-wrap:normal` alone trades a chopped word for an overflowing one.
- `min(var(--fs-h1), 13vw)` still overflowed, because **a viewport unit measures the SCREEN and a
  heading lives in a GRID TRACK.**
- `min(var(--fs-h1), Ncqi)` works, and every block that carries a heading must declare
  `container-type:inline-size`. Miss one and `cqi` silently falls back to the small viewport.

**N IS DERIVED, NEVER PICKED.** A face advances a measurable em per character (Archivo Expanded 700:
0.757em). A word of N characters fits a track of width W at `W / (N × advance)`, so the coefficient
is `100 / (N × advance)` percent of the container. Measure the advance for the face actually in use.

**What it costs, and say it out loud to the client.** A two-column hero and a monumental headline at
every width are mutually exclusive; the alternatives are a full-width title row or an image behind
the copy. **`hyphens:auto` is not the escape hatch** — the headless renderer carries no Spanish
dictionary. The corollary is a copy rule that belongs in the brief: large display needs SHORT WORDS.

*Gate:* `qa-review` house-rules row 32(e), measured in a browser at every width.

### `ch` measures the font of the element it is written on

`max-width:34ch` on a hero copy block resolved against the inherited **body** face at 1rem — about
270px — and put an 88px display headline in five one-word lines. Cap display blocks in `rem`.

### A masonry is `columns`, never a grid

A grid places items in rows, and a row is exactly what a masonry does not have. `columns` +
`break-inside:avoid` has no rows to wait for.

### A full-bleed split needs more inside padding than a contained section

There is no page margin doing the work on the bleeding side, so the same `clamp()` that reads as
generous inside a container reads as a collision beside a photograph.

### Measure the render; do not reason about it

Three hypotheses about a runaway heading were wrong before a measurement found the class collision
in one attempt. Copy the page to `probe.html`, append a `<script>` that writes
`getBoundingClientRect()` and `getComputedStyle()` into `document.title`, and read it back with
`chrome --headless=new --dump-dom`. Chrome clamps `--window-size` at about 500px, so narrow
viewports go through `medir-geometria.mjs` instead.

## Legacy — generator, chassis and anchor (not used for client work; removal pending)

Kept only so the legacy assets under `assets/gallery/`, `assets/chassis/` and the two `proof-*`
files stay explained until they are deleted. Nothing below is a step for a client project.

- **The generator.** `../assets/gallery/_build-gallery.php` writes the gallery `index.html` and the
  two chassis (`chassis/corporate.html`, `chassis/ecommerce.html`) from its own CSS tables, with
  `--chassis-out=<dir>` for a separate destination. Its outputs are untracked and its audit rows
  (`RT_GALLERY_NOT_BUILT`, `RT_CHASSIS_NOT_BUILT`, `RT_GALLERY_STALE`) demand a build until the
  removal step retires them.
- **The anchor block.** Each legacy asset declares an `AXIS POSITIONS` block in `:root` — five token
  lines copied from `web-templates/references/design-system.md` plus `Anchor:` and a
  `composition: LP-*` marker — and `RT_MOCKUP_AXES_MISMATCH` compares them with the style catalogue.
  A client maqueta takes its look from the Plantilla, not from re-pointing this block.
- **The two proof files** render one copy set (`../assets/_axis-proof-content.md`) at two anchors so
  the axis difference can be seen with the copy held constant; `RT_PROOF_NOT_DISTINCT` and
  `RT_PROOF_COPY_DIFFERS` gate them.

### Section blueprints

The legacy blueprints were per-`COMP-*` recipes (hero, services, FAQ, pricing…) with placeholder
boxes (`.ph`) instead of photographs. A client maqueta derives its sections from the lienzo instead.
Two of their rules outlived them and apply to every maqueta: **never a capture form in the hero** —
the lead form belongs to the closing conversion band — and **a disclosure list is native
`<details>/<summary>` with exactly the first row open**, the rule `RT_MOCKUP_DISCLOSURE_STATE` gates
on the assets it walks.

### The section chassis

The chassis offered three wrappers (`contained`, `row`, `bleed`). Two of its lessons hold for any
derived file: **what bleeds is the colour, not the copy** — a band's text children take the content
width back — and **a band needs its own vertical rhythm**, one column and a gap, or its heading sits
on the first row of photographs.
