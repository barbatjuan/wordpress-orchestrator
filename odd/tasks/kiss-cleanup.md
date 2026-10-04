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
- [ ] T1 Flow bugs (delegated writer). Reproduce each before fixing; report any that does not reproduce.
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

## Not in scope (owner decision needed, not asked yet)
- reducing `blind-judges` to one judge; removing Divi; collapsing the verifier-marker grammar;
  moving `docs/` and `openspec/` history out of the tree; deleting the three stale worktrees and old
  local branches; redeploying to `~/.claude` with `install --clean`.

## Acceptance
- a fresh clone passes the framework audit with no build step
- no skill, agent, README or CONTRIBUTING text points at a deleted path
- audit and all tests green after each task; counts before/after recorded below

## Progress / evidence
Baseline (main 1807d74 + security): audit `0 FAIL / 2 WARN / 0 JUDGE across 17 skills`, 80 `RT_*`
rows; tests audit-signals 22, container-hygiene 81, framework-audit 797 (~73 s), herramientas 277,
replay 11, security 30, write-path 616.
