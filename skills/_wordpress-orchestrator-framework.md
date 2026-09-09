# WordPress Orchestrator — framework overview

A modular system for building premium WordPress sites. Reusable across
projects (a workshop, a clinic, a real-estate site, an ecommerce) by swapping brand config
and adding domain skills — the orchestrator and bases stay the same.

The contract lives in `agents/wordpress-orchestrator.md`. If this overview and the agent
ever disagree, **the agent wins** — fix this file.

## Principle
**The agent thinks. The skills execute.** The orchestrator agent only decides which skill
runs, in what order, with what context. It holds no CSS/HTML/PHP. Those live in skills and
their `assets/`.

## Three kinds of skill
- **Knowledge** (decide and teach; touch nothing, need no WordPress): `web-templates`
  (page architecture: which sections, order, hierarchy), `ux-design-system` (visual
  language: tokens, motion, layout patterns).
- **Read-only** (inspect and report; never write): `project-context` — "Read-only
  reconnaissance… Modifies nothing" — and `qa-review`, which verifies an already-built page
  server-side and reports PASS/FAIL with evidence. `framework-audit` is read-only too, but points
  the other way: it verifies THIS REPO rather than a site, because everything else here checks a
  built site and nothing checked the framework. Run it before merging a skill change. `visual-verification`
  is read-only as well: it judges a RENDER by eye — inside a subagent, on a capture budget —
  because contrast, overflow and Lighthouse cannot see composition. `blind-judges` is read-only
  too, and is the only check that never learns what the build intended: two judges, blind to each
  other and to the brief, decide whether a mockup looks like the last deliveries and whether it
  looks professional. Sameness passes every rule in this repo, which is why judging it needs an
  actor that was never told what was aimed at.
- **Operative** (produce output): `html-mockup` emits static HTML/CSS published as an
  Artifact and **never touches WordPress**. `elementor-core`, `divi-core`, `woocommerce`,
  `wordpress-performance`, `wordpress-seo`, `wordpress-forms`, `wordpress-legal` and
  `elementor-theme-parts` write to the live site — each one carries its own blocking build gate. `wordpress-forms` additionally SENDS a real message
  during its delivery test, which is an outward action and is confirmed separately.

Copywriting is owned by a SUBAGENT, not a skill: `agents/wordpress-copywriter.md`. Long-output
work belongs in a fresh window, and it must be reached by explicit delegation — a skill firing on
"texto" during a deploy would rewrite a live site's content over a common noun. It returns copy
plus a FACTS NEEDED list, and never invents a credential, a number or a testimonial.

## Flow
The first question is **new site or existing site?** It decides whether WordPress gets
inspected at all; do not run `project-context` reflexively.

**New site (greenfield)** — nothing is written to WordPress until the gate:
`web-templates` → `ux-design-system` → `html-mockup` → `blind-judges` (client approves) → **BUILD GATE** →
`project-context` (now, to confirm connector / builder / theme on the real target) →
`elementor-theme-parts` (header/footer FIRST, so pages inherit one that already exists) →
`elementor-core` | `divi-core` → `woocommerce` (if commerce) → `wordpress-performance` /
`wordpress-seo` → `wordpress-legal` → `wordpress-forms` → `qa-review` + `visual-verification` → hand off.

**Existing site** — inspect first, so routing is based on facts, never assumption:
`project-context` → `web-templates` → `ux-design-system` → `html-mockup` → `blind-judges` (approve) →
**BUILD GATE** → `elementor-theme-parts` (header/footer FIRST) → `elementor-core` |
`divi-core` → `woocommerce` (if commerce) → `wordpress-performance` / `wordpress-seo` →
`wordpress-legal` → `wordpress-forms` → `qa-review` + `visual-verification` → hand off.

Either way the design phase (`web-templates` → `ux-design-system` → `html-mockup`) is
builder-agnostic and needs no WordPress and no connector.

## The build gate (hard stop)
Once the mockup is approved and BEFORE any skill writes to WordPress, the orchestrator
STOPS and asks the user for an explicit **yes** for this build — the native build is an
outward, hard-to-reverse action. No mockup approval + no explicit yes → no native build. On
an existing site, every page overwrite is also confirmed by name. Each operative WordPress
skill repeats the gate itself, so it still holds when that skill is invoked directly instead
of through the orchestrator.

The mockup is the approval gate and the visual contract. It is **never** imported into the
builder — the native build reproduces it from the same spec + tokens, and `qa-review` diffs
the result against it.

## Builder-agnostic vs builder-specific
- **Agnostic, no WordPress at all**: `web-templates`, `ux-design-system`, `html-mockup`.
- **Agnostic, WordPress-aware**: `project-context` (reports `elementor` | `divi` | `unknown`
  and never guesses), `wordpress-performance`, `wordpress-seo`, `wordpress-forms` (it routes on
  the form plugin, not the page builder) and `wordpress-legal` (on the consent plugin).
- **Builder-aware, validated on Elementor only**: `qa-review` — its evidence checks look for
  Elementor build artefacts; the Divi equivalents are not validated. Also `woocommerce`: the
  commerce structure is generic, but every execution step and asset targets the Elementor
  Theme Builder, so there is no Divi commerce path today.
- **Builder-specific**: `elementor-core` (battle-tested), `elementor-theme-parts`
  (Elementor Pro Theme Builder only — no Divi equivalent) and `divi-core` (scaffold).

**The Divi path is unvalidated.** `divi-core` has no `assets/` directory at all, and the
`di_section` / `di_row` / `di_module` helper library its own SKILL.md describes does not exist
yet — which also means no Divi build gets an automatic container audit, because that audit
lives in the Elementor helper library. Nothing on the Divi path has been proven end-to-end on a real site. Do
not present it as parity with Elementor: flag unverified steps as unverified and append
confirmed findings to `divi-core/references/gotchas.md`.

## Knowledge vs gotchas — what actually exists today
The intended shape is that each core skill splits stable knowledge
(`references/knowledge.md`) from hard-won traps (`references/gotchas.md`). Only two skills
have reached it:

| Skill | `references/` today |
|---|---|
| `elementor-core` | `knowledge.md` + `gotchas.md` |
| `woocommerce` | `knowledge.md` + `gotchas.md` |
| `divi-core` | `gotchas.md` only — no knowledge file yet |
| `elementor-theme-parts` | `gotchas.md` only — no knowledge file yet |
| `ux-design-system` | `style-catalog/` (8 `STY-*`), `shipped-log.md`, `design-tokens.md`, `layout-patterns.md`, `motion.md` |
| `web-templates` | `design-system.md`, `recommender.md`, `toggles.md`, `templates/` |
| `html-mockup` | `mockup-guide.md` |
| `qa-review` | `house-rules.md` — the most cross-referenced file in the framework |
| `visual-verification` | `render-defects.md` — every defect found by looking, and the rule it produced |
| `project-context`, `wordpress-performance`, `wordpress-seo`, `wordpress-forms`, `wordpress-legal` | none |

Gotchas are the gold — grow them every time something surprises you. Shape and rules:
`CONTRIBUTING.md`.

## Assets (library, not skill)
Reusable code lives under a skill's `assets/`; skills reference assets by path and never
paste code inline. What exists:
- `elementor-core/assets/es-builder.php` — the helper library.
- `elementor-theme-parts/assets/es-theme-parts.example.php` — header/footer + Theme Builder
  parts. Lives in its own skill, not `elementor-core/assets/`; it loads `es-builder.php` as a
  dependency.
- `woocommerce/assets/es-shop-template.example.php`,
  `woocommerce/assets/es-product-single.example.php`.
- `html-mockup/assets/gallery/_build-gallery.php` generates `html-mockup/assets/chassis/ecommerce.html`
  and `html-mockup/assets/chassis/corporate.html` — the brand-neutral reference CHASSIS; the
  orchestrator mandates running the generator and starting from the one matching the SITE TYPE.
  Never start a corporate site from the ecommerce one. Running the generator is half the step:
  each ships pointed at one anchor so it renders, and the `AXIS POSITIONS` block must then be
  re-pointed at the anchor the dialogue resolved (`RT_MOCKUP_AXES_MISMATCH` gates it).
- `qa-review/assets/lighthouse-audit.mjs` — the server-side evidence script other skills' gates
  point at.
- `framework-audit/assets/framework-audit.php` — verifies this repo itself, not a built site.
- `visual-verification/assets/measure-context.js` — what a session actually cost, and the baseline
  context it re-read on every turn.
- `divi-core` has no `assets/` yet.

## Extending per project
Add domain-specific operative skills next to these (e.g. `build-home`, `build-product-page`,
`real-estate-listings`, `clinic-booking`). Reuse the same orchestrator, bases, and assets.
Keep new gotchas in the relevant `references/gotchas.md`.
