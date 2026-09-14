---
name: web-templates
description: "Trigger: plantilla, wireframe, arquitectura de home, elegir plantilla, recomendar plantilla, ecommerce template, corporate template, site architecture, template. Choose and resolve a page architecture (which sections exist, order, hierarchy) before any visual or builder work. Site-type aware (ecommerce | corporate)."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.3"
---

# Web Templates (architecture)

Decide the ARCHITECTURE of a page — which sections exist, in what order, with what
hierarchy — before deciding how it looks (`ux-design-system`) or building it
(`elementor-core` / `divi-core`). Holds the archetypes, the recommender, and the toggles.
Not one design in many colors: many architectures + a layer that picks and tunes one.

## Activation Contract
Runs before `ux-design-system`. Prerequisite is conditional:
- **Existing site**: run after `project-context`.
- **New site (greenfield)**: run FIRST — no WordPress and no `project-context` yet; that comes
  later, at the build gate.

Builder-agnostic. Produces a resolved architecture spec. Modifies nothing on the site.

## The 3 layers
- **CAPA 1 — Archetypes** (`references/templates/<type>/TPL-*.md`): proven skeletons, each section
  marked FIXED (DNA) or TOGGLE. 23 homes: 9 ecommerce, 14 corporate.
- **CAPA 2 — Recommender** (`references/recommender.md`): analyze the brand, request
  references, recommend a `TPL-*`, confirm.
- **CAPA 3 — Toggles** (`references/toggles.md`): fine-tuning limited to what the chosen
  template allows.

## Hard Rules
- Decide architecture only. No visual code, no builder data, no deploy here.
- Pick a base archetype, then adjust via toggles. Never assemble sections ad-hoc.
- A request that contradicts the archetype's DNA → recommend switching archetype, never
  deform the current one (no "add storytelling to the Catalog template").
- ONE shared token STRUCTURE across every template (`references/design-system.md`): same names,
  same scale steps. What a template may pin is its POSITION on the five axes — the sector
  verticals `TPL-C-06..12` ship a brand block of their own; `TPL-C-13` has none yet. Names never change.
- Ask the client for 2–4 references BEFORE recommending. Confirm the recommended template
  with the user before continuing.
- Mobile-first: every section carries mobile / tablet / desktop behavior.
- Two archetypes of one family share at most HALF their sections, and every wireframe row
  carries a `COMP-*` id: a section written as prose is invisible to `RT_TPL_TOO_SIMILAR`.

## Execution Steps
1. Determine **site type**: `ecommerce` | `corporate`. Ask (AskUserQuestion) if unknown.
2. Read `references/recommender.md` → analyze, request references, map signals → archetype,
   present the recommendation + rationale, get confirmation. In corporate, ask what the business
   PUBLISHES before its objective: family B (§3b) wins whenever one of its seven applies.
3. Read `references/toggles.md` → run only the toggles the chosen template admits, precharged
   with defaults derived from the references.
4. Read the chosen `TPL-*.md` → resolve the home section inventory (order + fixed/toggle state
   + toggle answers).
5. **Resolve the page set** (recommender §6): propose the inner pages, assign an archetype from
   `references/templates/pages/` to each, run each page's toggles. Three are not optional and are
   not asked: legal ×4, 404, and thanks when there is a form.
6. Hand the resolved specs (home + each page) + `references/design-system.md` tokens to
   `ux-design-system`, then to `html-mockup` — one section inventory per page, rendered as ONE
   Artifact with in-page navigation (never one mockup or one Artifact per page).

## Output Contract
A resolved architecture spec: template id, site type, ordered section list with each
section's state resolved (kept/removed/swapped), the toggle answers, a pointer to the shared
tokens, and per-breakpoint notes. No visual or builder-specific code.

## References
- `references/design-system.md` — shared tokens (type, color, spacing, buttons, containers, radii).
- `references/recommender.md` — CAPA 2: analysis, reference intake, signal→template map, page set.
- `references/toggles.md` — CAPA 3: modular toggle catalog.
- `references/plantillas/_indice.md` — la biblioteca de plantillas reales, y el punto de partida de un proyecto.
- `references/templates/ecommerce/` — TPL-E-01..09. See its `_README.md`.
- `references/templates/corporate/` — TPL-C-01..14, two families. See its `_README.md`.
- `references/templates/pages/` — inner-page archetypes. See `pages/_README.md`.
