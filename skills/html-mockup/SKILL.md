---
name: html-mockup
description: "Trigger: maqueta, mockup, HTML preview, prototipo, derivar maqueta, lienzo, visual approval, aprobar diseño, static mockup. Derive the client's maqueta — one self-contained responsive index.html with hash routing — from the approved lienzo, for the veredicto and client approval BEFORE the native build. Builder-agnostic, published as ONE Artifact."
license: Apache-2.0
metadata:
  author: "juan"
  version: "2.0"
---

# HTML Mockup (maqueta derivada del lienzo)

Derive the client's **maqueta** by hand from the client's **lienzo** (drawn in Claude Design from
the Plantilla's lienzo), under `references/mockup-guide.md` § "Contrato de derivación".

## Activation Contract
After Claude Design (the design skill), BEFORE `blind-judges`, client approval and builder-core.
Its approved, judged output is the visual contract `qa-review` checks the build against.

## Hard Rules
- **Derived, never generated or patched.** Start from the Plantilla's `maqueta/` structure; a change
  starts in the lienzo and is re-derived. Never run the legacy generator for a client.
  (no verifier: where a maqueta came from is not recorded in any file; the reviewer compares it with the lienzo)
- **Approval artifact, never an import.** The build is native, from the ficha's Mapeo nativo.
  (no verifier: pasted HTML shows up only in the build, where the native ceiling count catches it)
- **ONE self-contained `index.html`, ONE Artifact.** Pages switched by hash routing, each route
  writing its own `<title>`; header, burger menu and footer OUTSIDE the pages. Never one Artifact per
  page, never `target="_top"`.
  (no verifier: nothing inspects a published Artifact's routing; a split set shows up as a dead click)
- **The full page set** from `web-templates/references/paginas-obligatorias.md`; every «ver ficha»
  lands on a real page.
  (no verifier: no rule reads a client maqueta's pages; the veredicto sweep visits every page)
- **Two breakpoints, `max-width:1024px` and `max-width:767px`, and no third.** Elementor expresses
  exactly those natively; any other width is custom CSS written before QA counts it.
  (no verifier: no rule reads a client maqueta's media queries; the reviewer reads the style block)
- **No remote URL.** Fonts as `data:` woff2 from `assets/fonts/_fonts.php` between the `NM-FONTS`
  markers; images as `../img/<slug>.webp`, the slug being the WordPress attachment slug.
  (no verifier: font embedding is gated only on this skill's own assets; on a client maqueta the reviewer checks it)
- **Tokens once in `:root`, mapped to Elementor global colours and fonts.** A value with no global
  slot is a design change, never custom CSS.
  (no verifier: the mapping is read against the ux-design-system spec by the reviewer)
- **The DOM is a blueprint**: `section > grid|row > element` (guide § "Container hygiene").
  (verifier: `qa-review` house-rule row 11 re-runs the container audit on what the native build landed)
- **Measure before asking for eyes.** `assets/herramientas/medir-geometria.mjs` and
  `assets/herramientas/color.php`, then `references/defectos-de-derivacion.md`, before any sweep.
  (no verifier: no rule runs these tools on a client maqueta; they belong to whoever derives it)
- **No client approval without a veredicto**, and no builder-core without approval.
  (no verifier: no rule reads a client maqueta's veredicto yet; the orchestrator's build gate asks for it)

## Execution Steps
1. **Gather** the client's lienzo (1440 and 390 artboards), the Plantilla's `maqueta/` and ficha,
   the page set and the `ux-design-system` spec.
2. **Derive ONE file**: section ids = the ficha's Mapeo nativo rows, burger from the 390 artboard.
3. **Measure** geometry, contrast (`color.php --maqueta`), then the derivation defects. A design
   defect is fixed in the lienzo.
4. **Publish ONE Artifact** (`<marca> — maqueta`), images inlined in a scratch copy (guide
   § "Multi-page preview").
5. **Veredicto**: hand the URL and file to `blind-judges` + `visual-verification`. A self-judged
   or partial result is reported as such.
6. **Client approval** or a per-page change list; change the lienzo, re-derive, republish to the
   same URL, re-judge.
7. **On approval** freeze it and hand over section ids, Mapeo nativo, Site Settings tokens,
   photographs by slug and the sealed veredicto to `elementor-core` / `divi-core`.

## Output Contract
The Artifact URL, section ids per page, the measurements and fixes, the veredicto state, and on
iteration what changed.

## References
- `references/mockup-guide.md` — derivation contract, publishing, handoff, tools.
- `references/defectos-de-derivacion.md` — what goes wrong turning artboards into a maqueta.
- `assets/fonts/` and `assets/herramientas/` — embedded faces; measuring and sealing tools.
- Legacy, not used for clients, removal pending: `assets/gallery/_build-gallery.php`,
  `assets/gallery/`, `assets/chassis/`, `assets/_axis-proof-content.md`,
  `assets/proof-editorial-mockup.html`, `assets/proof-direct-mockup.html`.
