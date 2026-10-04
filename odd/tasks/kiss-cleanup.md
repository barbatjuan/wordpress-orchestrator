# kiss-cleanup

## Objective
Make the framework KISS without losing reliability: fix the bugs in the current flow, remove the
legacy generated-catalog apparatus, and cut instruction files down to instructions.

## Problem / why
PR #22 replaced the generated catalog with real Plantillas, but the planned amputation
(`openspec/changes/plantillas-reales/tasks.md`, "PR 3 — Amputation") never ran. About 31k tracked
lines and 34 of 80 audit rules guard code the current flow never reads, a fresh clone is red until
a 17.9k-line generator runs, and several documents contradict the new flow. Owner instruction
(2026-10-04): "todo KISS, pero que funcione lo más cercano a la perfección posible"; approved all
three steps below.

## Constraints
- Branch `chore/kiss-cleanup`, stacked on `feat/wordpress-security` (both touch `house-rules.md`,
  the orchestrator and `CONTRIBUTING.md`).
- One writer at a time, one work-unit commit per task. No push, no PR: those are the owner's.
- Strict TDD: enabled (source: owner global instructions). Runner: `php tests/<file>.php`.
- Gate after every task: framework audit 0 FAIL, every `tests/test-*.php` 0 FAIL.
- Never weaken a check to make it pass; a retired rule goes with its fixture, a live rule stays.
- Load-bearing, do not touch: `es-builder.php` write-path safety and its tests,
  `tests/fixtures/emitted-golden.txt`, `herramientas/*`, `fonts/`, the Plantilla library, the lean
  `wordpress-*` skills.
- Delivery strategy: `exception-ok` for T2 (mechanical deletion, the openspec plan's own
  `size:exception`); the rest `single-pr` per task. Chain strategy decided at PR time by the owner.

## Tasks
- [x] T1 Flow bugs (delegated writer). Reproduce each before fixing; report any that does not reproduce.
  - `plantillas/_indice.md` disagrees with `veredicto.php --comprobar` (15/17 "sin veredicto")
  - `barro` seal reported `caducado` on a clean tree; `_biblioteca.md` count
  - qa-review row 37: `css_custom_max: 0` in fichas vs `es-builder.php` helpers that write `custom_css`
    (return as a decision gap if the fix is a product choice)
  - `_wordpress-orchestrator-framework.md` mandates the generator the orchestrator forbids
  - `elementor-core` SKILL/knowledge still ask for axes, `STY-*`, `es_record_style_resolution`
  - `blind-judges/SKILL.md` vs `veredicto.php --sellar-ruta`; `design-tokens.md` "Eight axes"; row-number drift in `house-rules.md`
- [ ] T2 Amputation (delegated writer), following the openspec PR 3 section and `design.md` File Changes:
  delete gallery, chassis, proofs, style-catalog, `templates/**`, `recommender.md`, `toggles.md`,
  `shipped-log.md`; slim `design-system.md` to the numbers `test-write-path.php` and `es-builder.php` pin;
  retire the legacy `RT_*` rows with their code, fixtures and `CONTRIBUTING.md` rows; remove generator
  instructions from README/CONTRIBUTING/overview/.gitignore; `install.sh`/`install.ps1 --clean`;
  tick the PR 3 boxes in the openspec tasks file truthfully.
- [ ] T3 Trim to instructions (delegated writer): `CONTRIBUTING.md` rule table to one line per id;
  orchestrator to routing, gates and delivery, pointing at `qa-review` for rules; `house-rules.md`
  narration cut, overlapping rows merged only where the check is identical; `es-builder.php`
  incident narration in comments cut (comments only, golden fixture unchanged).

- [ ] T4 Further optimisation (owner, 2026-10-04: "optimiza todo"), reversible in-repo items only:
  - collapse the verifier-marker grammar to "a named verifier exists or the gap is admitted";
    retire the `RT_MARKER_*` rows that only police trailer syntax, with their fixtures
  - move finished process history (`docs/`, archived `openspec/` changes and specs for deleted
    capabilities, `checklist.py`/`checklist.json`) out of the working tree; git history keeps it
  - remove dead helpers left without a caller after T2 (`es_record_style_resolution`, unused
    `color.php` ink functions, `scrim.php` if no step uses it), each with its tests
  - speed: the audit test should no longer take ~73 s once legacy fixtures are gone; record the time

## Not in scope (owner decision needed)
- Divi stays: a scanned client site runs the Divi theme, so the scaffold is in use.
- reducing `blind-judges` to one judge: changes what the quality gate proves; asked with evidence
  after T3, not assumed.
- deleting the three stale worktrees and old local branches, and redeploying to `~/.claude` with
  `install --clean`: irreversible or outside the repo; asked explicitly at the end.

## Acceptance
- a fresh clone passes the framework audit with no build step
- no skill, agent, README or CONTRIBUTING text points at a deleted path
- audit and all tests green after each task; counts before/after recorded below

## Progress / evidence
Baseline (main 1807d74 + security): audit `0 FAIL / 2 WARN / 0 JUDGE across 17 skills`, 80 `RT_*`
rows; tests audit-signals 22, container-hygiene 81, framework-audit 797 (~73 s), herramientas 277,
replay 11, security 30, write-path 616.

T1 (2026-10-04, delegated writer, prose only — no PHP changed): 7 of 9 reported defects reproduced
and fixed (`_indice.md` no longer carries a veredicto column, the tool is the source of truth;
overview, elementor-core, blind-judges, design-tokens, two stale row references, recomendador/enfoques
pointers). Audit `0 FAIL / 2 WARN`, all tests unchanged and green; `veredicto.php --biblioteca`:
16 of 17 vigentes before and after. The per-slug listing in `_indice.md` stays: the audit's
reachability check reads it.
Open owner decisions (not fixed, evidence in the T1 report):
- `barro` seal was already stale in the commit that sealed it (`b287a48`): re-judge or leave unoffered
- `_biblioteca.md` records Judge A over 15 Plantillas; there are 17
- qa-review row 37: `es_btn()` and other helpers write `custom_css`, 15 of 17 fichas set
  `css_custom_max: 0`, and the row counts helper CSS — every built site would FAIL
- row 31(a) cannot compare axis variables for maquetas whose `:root` lacks them
- 8 fichas and 3 canvas manifests still say the index marks them "sin veredicto" (editing a ficha breaks its seal)
