---
name: html-mockup
description: "Trigger: maqueta, mockup, HTML preview, prototipo, visual approval, aprobar diseño, static mockup. Materialize the resolved architecture + tokens into a static responsive HTML/CSS page set for client approval BEFORE the native builder build. Builder-agnostic, published as ONE Artifact with in-page navigation."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.4"
---

# HTML Mockup (approval preview)

Turn the resolved architecture (`web-templates`) + visual spec (`ux-design-system`) into static,
responsive HTML/CSS the client can approve before paying for the fragile native build.

## Activation Contract
Run after `ux-design-system` (tokens + patterns decided), BEFORE `elementor-core` / `divi-core`.
Its approved output is the visual contract `qa-review` checks the build against.

## Hard Rules
- The mockup is the approval artifact and visual contract — **never** a source to import: the
  build is reproduced NATIVELY from the same spec, and pasted HTML is one dead block, not widgets.
- **ONE Artifact for the whole page set.** One `<div class="page">` per page, switched in-page;
  header, announcement bar and footer OUTSIDE them so they survive every switch. Never split
  pages across Artifacts nor link them with `target="_top"` — cross-artifact nav is dead in the
  sandbox. Detail: `references/mockup-guide.md` § "Multi-page preview".
- Mobile-first at the framework breakpoints (`<768 / 768–1024 / >1024`); no horizontal scroll, and
  wide blocks scroll inside their own container.
- Theme-aware, unless the file pins one ground on purpose.
- **The DOM is a blueprint, not scaffolding.** Every wrapper `<div>` here becomes a container in
  the native build. Keep it flat: `section > grid|row > element`. Detail:
  `references/mockup-guide.md` § "Container hygiene".
- Declare the tokens as CSS variables in `:root` ONCE — the same tokens the native build uses.
- Self-contained: inline `<style>`, no CDNs or remote images (Artifact CSP). Fonts EMBEDDED as
  `data:` woff2 — naming one renders the fallback. Placeholders only.
- Mirror `ux-design-system`: one accent for CTAs, calm motion (`cubic-bezier(.22,1,.36,1)`,
  ~.35–.7s), two button families, one card recipe.
- **Do not proceed to builder-core until the user approves.** Capture changes, iterate, republish.

## Execution Steps
1. Two decisions, not one. **Run the generator** (`assets/gallery/_build-gallery.php`), never copy —
   pass `--chassis-out=` with this project's own dir so a sibling cannot overwrite it. It writes both
   chassis; use the one matching SITE TYPE, never corporate from the ecommerce one.
   Then **re-point `AXIS POSITIONS`** at the resolved anchor: five token lines + `Anchor:`, together
   (`RT_MOCKUP_AXES_MISMATCH` gates it). Detail: `references/mockup-guide.md`.
2. Build ONE responsive file: semantic `header/main/section/footer`, one `.page` per page, only
   the sections resolved as kept/on. **Ecommerce only**: € prices, cart badge.
3. Publish as ONE **Artifact** (title `<brand> — maqueta`, favicon emoji, one-line description).
   Share the URL as a structural preview needing the user's visual confirmation.
4. Collect approval or a per-page change list. Iterate the same file → republish to the same URL.
5. On approval: freeze it as the visual contract and hand inventories + tokens + the resolved axis
   positions its `:root` declares to `elementor-core` / `divi-core`; the build must match it.

## Output Contract
Return the Artifact URL, the per-page section inventory and the toggle states baked in. On
iteration, report what changed.

## References
- `assets/gallery/_build-gallery.php` — the generator; one run writes
  `assets/chassis/corporate.html` (`PERS-INSTITUTIONAL`) and `assets/chassis/ecommerce.html`
  (`PERS-MATTER`). Generated, never hand-edited.
- `references/mockup-guide.md` — governing detail: HTML shell, token block, typefaces +
  `assets/fonts/`, multi-page contract, section blueprints, placeholders, responsive rules.
- `assets/_axis-proof-content.md` — one copy set, rendered by both proof files below: the
  constant that makes the axis comparison honest.
- `assets/proof-editorial-mockup.html` — `PERS-EDITORIAL` over that copy: paper ground, editorial
  scale, `LP-ASYMMETRIC`, no elevation.
- `assets/proof-direct-mockup.html` — `PERS-DIRECT` over the SAME copy: ink ground, monumental
  scale, `LP-BROKEN-GRID`, accent glow. Same strings (`RT_PROOF_COPY_DIFFERS` gates it); every
  difference is the anchor, listed in each file's head.
- `assets/gallery/` — internal gallery, one strip per `TPL-* × PERS-*`, generated from
  `assets/gallery/img/`. Regenerate via `_build-gallery.php`; never hand-edit its `index.html`.
