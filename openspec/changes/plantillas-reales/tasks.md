# Tasks: Plantillas reales — a real library, a perceptual gate, a native ceiling

> **Size deviation, declared.** The 530-word cap is exceeded, same precedent as `design.md` and the
> archived `2026-08-28-style-catalog/tasks.md`. 25 rule ids, 7 dual-mode tools and 7 wave-1
> Plantillas each need a paired RED/GREEN and a named fixture under `strict_tdd`; compressing below
> this would drop traceability the two prior catalog attempts lacked. Declared, not met by cutting
> content.

**Where `design.md` corrects the approved plan, `design.md` wins** — noted inline at each point that
matters (rule ids/count, `points_at_dir()`, the audit's offline boundary, PR slicing, cut-over order).

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | ~9,300 authored (excl. amputation) + ~25,000 deletion, across 19 PRs |
| 400-line budget risk | High in aggregate; OK per‑PR at the 800‑line budget except PR 1d (~780, **at the line**) and PR 3 (**certain overrun**, mechanical deletion) |
| Chained PRs recommended | Yes |
| Suggested split | 19 PRs: 0b, 1a–1e, 2a–2c, 3, 4.1–4.7 — see Work Units |
| Delivery strategy | auto-chain |
| Chain strategy | feature-branch-chain |

```text
Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High
```

`Decision needed before apply: No` — `auto-chain` was cached this session; chain strategy
(`feature-branch-chain`) and the 800-line budget are already `design.md`'s own decision and match
`config.yaml`'s `review_budget_lines: 800`. `sdd-apply` proceeds with PR 0b.

### Chain topology (feature-branch-chain)

Tracker: `feat/plantillas-reales` (current tip, 6 commits over `main@123a736` — PR 0's rescue +
baseline + SDD artifacts are already on it, see "Already complete" below). Each PR below bases on
its immediate predecessor; only the tracker merges to `main`, at phase boundaries (after 1e, after
2c, after 3, and after each 4.x individually — each Plantilla is independently revertible). Revert
strictly in reverse: reverting 1a–1e while 2a–2c stands leaves skills pointing at a contract that no
longer exists; reverting anything while 3 stands leaves FAIL‑level rules with no fixtures.

### Work Units

| PR | Scope | Base | Est. | Budget | Focused test | Runtime harness | Rollback |
|---|---|---|---|---|---|---|---|
| 0b | Anonymise `BSP-tuscapas.md` | tracker | ~40 | OK | repo‑wide grep, zero real-client hits | N/A — prose only | delete the one commit |
| 1a | `.gitattributes` + `herramientas/{color,scrim,huella}.php` | 0b | ~620 | OK | `php tests/test-herramientas.php` | `php herramientas/huella.php --comprobar` against a scratch tree | branch deletable, no downstream yet |
| 1b | `glosario/enfoques/paginas-obligatorias/recomendador.md` | 1a | ~500 | OK | PR review (named gates, no automated check) | N/A — prose contract | branch deletable |
| 1c | `es-vocabulario.php` + `vocabulario-nativo.md` + `RT_VOCABULARIO_SIN_SELLO` | 1b | ~450 | OK | `php tests/test-framework-audit.php` (new scenario) | `php es-vocabulario.php` against a reachable sandbox Elementor | branch deletable |
| 1d | Ficha/Veredicto formats, `plantilla_unit()`, 24 rules @ WARN, 5 new `fx_*()` | 1c | ~780 | **at the line** | `php tests/test-framework-audit.php` | N/A — offline rules only | split by id family if it overruns (see below) |
| 1e | `herramientas/{veredicto,comprobar-maqueta,empaquetar,indice}.php` | 1d | ~700 | OK | `php tests/test-herramientas.php` | `php herramientas/veredicto.php --comprobar <fixture-slug>` | branch deletable |
| 2a | `web-templates` rewrite | 1e | ~500 | OK | full chain | N/A — doc rewrite | branch deletable |
| 2b | `html-mockup`+`mockup-guide` + `ux-design-system` rewrite | 2a | ~500 | OK | full chain | N/A — doc rewrite | branch deletable |
| 2c | `blind-judges` (+`capture.mjs --viewport/--medir`) + qa-review row 37 + orchestrator route map | 2b | ~500 | OK | `php tests/test-framework-audit.php` + `node capture.mjs --medir <fixture>` | real headless Chrome against a served fixture page | branch deletable |
| 3 | Amputation + WARN→FAIL + `--clean` | 2c | ~25,000 (deletion) | **size:exception** | full chain, deleted paths absent | `install.ps1 --clean` against a temp `$DEST` | revert restores from git; re-run `install` **without** `--clean` |
| 4.1 | `plantillas/delao/` | 3 | ~600 | OK | full chain + juez B/A | `capture.mjs` sweep 430/768/1280, served over `http` | delete the folder + its `_indice.md` row |
| 4.2–4.7 | one Plantilla each (lumiere, terrazza, aranda, corte, tueste, bajura) | previous 4.x | ~600 ea. | OK | same as 4.1 | same as 4.1 | same as 4.1 |

**size:exception reason (PR 3, paste verbatim into the PR description)**: ~25,000-line mechanical
deletion of plan §5.1 (already discharged by 1a–2c proving the new contract green before the old one
is removed); splitting it would produce PRs that build nothing and pass nothing, the same reasoning
the archived style-catalog change used for its own amputation PR (1f).

**If PR 1d overruns 800 lines**: split by id family — `RT_PLANTILLA_*` / `RT_VEREDICTO_*`+
`RT_BIBLIOTECA_*` / the rest — each family stays independently green because every id lands at WARN.

### Human decisions required before certain slices

- **PR 0b**: approve the fictional brand identity (name/sector/domain) replacing the real client —
  no automated check exists for this, PR review only.
- **PR 3**: (a) confirm PR 0b is merged — it deletes `style-catalog/`, which still holds the client
  material until 0b lands; (b) accept the `size:exception`; (c) the **redeploy is a separate,
  explicitly announced step after this PR merges**: pause other Claude sessions → `install.ps1
  --clean` (Windows; `install.sh --clean` elsewhere) → `diff -rq ~/.claude/{skills,agents}
  <repo>/{skills,agents}` empty → resume. Do not fold this into the merge itself.
- **PR 4.1**: approve the delao Canvas (harvested from artifact `eab59854` / tip `ef4d80a`) as-is, or
  retouch, before the Maqueta is derived (proposal Open Decision 2).
- **PR 4.2–4.7**: each Plantilla's Canvas needs sign-off before its Maqueta is derived — recurring,
  not a one-time gate.
- **Phase 4 / wave 2**: sector selection for Plantillas 8–10 is the user's later choice — out of
  scope for this file (proposal Open Decision 4).

## Already complete on this branch — do not re-task

`20e6fbd` (rescue orphan, satisfies PR 0's dependency for `install --clean`), `dd1fcb9` (config.yaml
baseline, 1521 OK/0 FAIL), `ca1e124` (base-sweep evidence, `docs/plantillas-reales/2026-09-13-barrido-base.md`),
`ccc6630` (gitignore `blind-judges` corpus screenshots), `34ed5e8` (proposal/specs/design). PR 0's
design.md scope is fully satisfied; work resumes at PR 0b.

## PR 0b — Anonymise client material (unblocks PR 3)

- [ ] 0b.1 Read `skills/ux-design-system/references/style-catalog/BSP-tuscapas.md`; list every real
      domain, sector-plus-name pairing, brief detail and palette reference tying it to the real client.
- [ ] 0b.2 Rewrite as a fictional brand: no domain, no recognisable sector-plus-name pairing, no
      client identity — keep it as the worked Ruta a medida example (format/structure preserved). No
      history rewrite.
- [ ] 0b.3 Grep the repo for the real client's name/domain outside this file (`_bespoke-route.md`,
      `_backlog.md`, `shipped-log.md`, `blind-judges/references/corpus.md`) — confirm zero leakage.
- [ ] 0b.4 Human decision: approve the fictional identity before merge (0b.1 in the human-decisions list).
- Verification: no automated test — repo-wide grep + PR review only.

## PR 1a — `.gitattributes` + `herramientas/{color,scrim,huella}.php`

- [ ] 1a.1 Create `tests/test-herramientas.php`; add it to `CONTRIBUTING.md`'s `&&` chain in the same
      PR (or `RT_GATE_LINE_UNREGISTERED` FAILs).
- [ ] 1a.2 RED: `huella.php` fixture — same tree written once CRLF, once LF → digest must match; a
      `.woff2` with a `\r\n` byte pair must hash unchanged (binary, never normalised).
- [ ] 1a.3 GREEN: extract `huella.php` from `_gallery-fingerprint.php`'s pattern (`path=>sha256`,
      relative to `skills/`, missing input = `absent`, `ksort`, digest over `"<path> <hash>\n"`),
      LF-normalise `.md .html .json .css .js .svg .txt` only; dual-mode CLI `--plantilla <slug>
      --biblioteca --comprobar`.
- [ ] 1a.4 Create `.gitattributes`: `eol=lf` for the text extension set under `plantillas/**`,
      `binary` for `.webp .woff2 .png .jpg .avif`. RED/GREEN: a CRLF-committed fixture normalises to
      the same huella as its LF twin (spec `plantilla-library` "Fingerprint is platform-stable").
- [ ] 1a.5 RED: `color.php` — a hex pair <4.5:1 → `--contraste` exits 1 naming the ratio; ≥4.5:1 →
      exits 0.
- [ ] 1a.6 GREEN: extract `color.php` (`srgb_lum/contrast/ratio_str/css_mix/ink_tint/ink_ends/
      ink_curve` + accent gate) from `_build-gallery.php:200-222,942-1116`; `--contraste <hex> <hex>`
      and `--maqueta <slug>` (4.5:1 text / 3:1 UI on every `:root` pair).
- [ ] 1a.7 RED (threat matrix): GD extension absent → `scrim.php --peor-pixel` exits **2**, never 0.
- [ ] 1a.8 GREEN: extract `scrim.php` (`worst_pixel/ink_mean/ink_pixel`) from
      `_build-gallery.php:1545-1700`; `--peor-pixel <img> <x> <y> <w> <h>`.
- [ ] 1a.9 Verify uniform exit contract across all three: 0 pass / 1 measured failure / 2
      usage-or-environment, never 0 for "could not measure."
- Verification: `php skills/html-mockup/assets/gallery/_build-gallery.php` (rebuild — generator still
  present, or `RT_GALLERY_STALE` fires) `&&` `php skills/framework-audit/assets/framework-audit.php
  && php tests/test-container-hygiene.php && php tests/test-framework-audit.php && php
  tests/test-audit-signals.php && php tests/test-write-path.php && php tests/test-replay.php && php
  tests/test-herramientas.php`.

## PR 1b — Contract documents

- [ ] 1b.1 `glosario.md`: the 8 domain nouns (§ proposal glosario table), Spanish only.
- [ ] 1b.2 `enfoques.md`: 8 Enfoque ids × 8 axes + par tipográfico + dirección de imagen, each
      "lo encarna: `<slug>`" or "sin plantilla"; zero CSS/token literals (spec `enfoque-vocabulary`,
      both PR-review-only named gates — no automated size/token check by design).
- [ ] 1b.3 `paginas-obligatorias.md`: legal ×4, 404, gracias-si-hay-formulario, ficha-por-unidad rule.
- [ ] 1b.4 `recomendador.md`: 8 Objetivo ids (spec `objetivo-routing`), negative reasoning per
      rejected candidate, and the explicit "ningún Objetivo encaja → ruta a medida" step referencing
      PR 0b's anonymised worked example.
- Verification: PR review against each spec's named gates (no automated check exists for prose
  completeness) + full chain (unchanged, nothing machine-checked yet).

## PR 1c — Native vocabulary by introspection

- [ ] 1c.1 RED (threat matrix, "never a hand-written `vocabulario-nativo.md`"): no live Elementor
      reachable → `es-vocabulario.php` exits **2**, writes nothing, never fabricates output.
- [ ] 1c.2 GREEN: `skills/elementor-core/assets/es-vocabulario.php`, read-only, walks
      `widgets_manager->get_widget_types(null)` (expect 130), emits per-widget registered/Pro-or-free/
      layout controls via `es_owns_control()`, the responsive suffix set (`:1716`), and kit slots
      (`es_kit_apply()` `:3664`); STDOUT markdown headed by the seal `elementor: · pro: · widgets: ·
      fecha: · generado_por: es-vocabulario.php`.
- [ ] 1c.3 RED: `vocabulario-nativo.md` fixture missing any one seal field → `RT_VOCABULARIO_SIN_SELLO`
      FAILs.
- [ ] 1c.4 GREEN: all five fields present → silent.
- [ ] 1c.5 RED (threat matrix, sandbox leftover): a tree with `es-vocabulario.php` still present
      under `wp-content/novamira-sandbox/` FAILs house-rule 22, no exemption added.
- Verification: same full chain as 1a, plus the new `RT_VOCABULARIO_SIN_SELLO` scenarios in
  `test-framework-audit.php`.

## PR 1d — Ficha/Veredicto contract, `plantilla_unit()`, 24 rules @ WARN

- [ ] 1d.1 `plantilla_unit()` RED: before the predicate exists, a file under `plantillas/delao/canvas/`
      is orphaned (`RT_ORPHAN_FILE` FAILs) even though `_indice.md` points at `.../delao/ficha.md`,
      because `points_at_dir()` only reaches direct children.
- [ ] 1d.2 `plantilla_unit()` GREEN: add the 6-line scoped predicate (`framework-audit.php`, used in
      the orphan loop `:741-745`); the same fixture now stays silent for every file under
      `plantillas/delao/**`. Regression fixture: `points_at_dir()` itself is untouched — orphan checks
      in `elementor-core/references/` and `qa-review/assets/` are unaffected.
- [ ] 1d.3 `RT_PLANTILLA_SIN_MAPEO` two-way closure — RED (direction A): a `<section id="hero-velo">`
      in the Maqueta has no Mapeo nativo row → FAILs naming the id. RED (direction B): a Mapeo nativo
      row names a section id absent from the Maqueta → FAILs (the opposite direction — this is the
      "two-way" half, not covered by direction A alone). GREEN: the two id sets match exactly.
- [ ] 1d.4 24 new/renamed ids, WARN, one mutation fixture each (`RT_VOCABULARIO_SIN_SELLO` already
      shipped in 1c; 25 total per `design.md`'s Rule Disposition):

  | Id | RED fixture | GREEN |
  |---|---|---|
  | `RT_PLANTILLA_SIN_FICHA` | `ficha.md` absent, or `objetivo`/`enfoque` id unresolved | complete, ids resolve |
  | `RT_PLANTILLA_SIN_CANVAS` | no `<Pagina>-movil.dc.html` beside a desktop artboard | mobile artboard paired by filename |
  | `RT_PLANTILLA_SIN_MAQUETA` | `maqueta/index.html` absent, remote URL, or a `.woff2` that doesn't resolve on disk | present, local-only (plantillas/** font exception) |
  | `RT_PLANTILLA_PAGINAS_ROTAS` | a `paginas:` entry unreachable from nav, or a dead "ver ficha" link | every page reachable, zero dead links |
  | `RT_PLANTILLA_CONTRASTE` | a `:root` pair < 4.5:1 text / 3:1 UI via `color.php --maqueta` | all pairs clear the floor |
  | `RT_PLANTILLA_BARRIDO` | a barrido cell reads `no-disponible`, `PARCIAL`, or a finding string | cell reads `✓` |
  | `RT_PLANTILLA_PAR_IGUAL` | two fichas share `tipo`+`objetivo`+`enfoque` | no duplicate triple |
  | `RT_PLANTILLA_RUPTURA` | an `@media` width outside `{1024,767}` ∪ `rupturas_extra:`, or an extra with no Elementor breakpoint named in Mapeo nativo's Nota | only the two breakpoints, or a justified+mapped extra |
  | `RT_PLANTILLA_IMAGEN_SIN_MANIFIESTO` | an `img/*.webp` with no `manifiesto-imagenes.md` row | every image has a row |
  | `RT_PLANTILLA_UNA_SESION` | manifest rows show two `sesión` values in one Plantilla | one session |
  | `RT_VEREDICTO_AUSENTE` | `veredicto.md` absent | present |
  | `RT_VEREDICTO_OBSOLETO` | recorded `hash` ≠ `huella.php`'s current fingerprint after an edit | resealed hash matches |
  | `RT_VEREDICTO_INCOMPLETO` | missing `juez_b`, a página×breakpoint cell, or `vistas`/`saltadas` | all six fields + full barrido table |
  | `RT_VEREDICTO_NO_PROFESIONAL` | `juez_b: no-profesional` | `profesional` |
  | `RT_BIBLIOTECA_AUSENTE` | `_biblioteca.md` absent | present |
  | `RT_BIBLIOTECA_OBSOLETA`\* | a Plantilla's hash changes, `_biblioteca.md`'s huella not recomputed | huella = sha256 of current per-slug hashes |
  | `RT_BIBLIOTECA_MISMA_MANO` | juez_a groups ≥2 Plantillas with no `aceptado por el usuario:` line | line present, or no group |
  | `RT_ENFOQUE_SIN_PLANTILLA` (WARN, permanent — never flips to FAIL) | an Enfoque no Plantilla embodies | every Enfoque embodied or marked "sin plantilla" |
  | `RT_ENFOQUE_REPETIDO_RECIENTE` (WARN, permanent — rename of `RT_STYLE_REPEATS_RECENT`, same mechanic) | newest `shipped-log.md` row's Enfoque repeats in the 4 before it | repeat only ≥5 rows back |
  | `RT_INDICE_DESACTUALIZADO` (rename/merge of `RT_GALLERY_NOT_BUILT`+`RT_GALLERY_STALE`) | a `plantillas/<slug>/` folder with no `_indice.md` row, or a stale hash | rows = folders, hashes current |
  | `RT_LEDGER_SIN_VEREDICTO` | a `shipped-log.md` row missing `veredicto_hash`/`veredicto_fecha` | both present |
  | `RT_RECOMENDADOR_SIN_RUTA_A_MEDIDA` | no "ningún Objetivo encaja" step in `recomendador.md` | step present with negative reasoning |
  | `RT_QA_SIN_RECUENTO_NATIVO` (rename of `RT_QA_NO_AXIS_CHECK` — fixture rewrite, not new) | `house-rules.md` has no native-count row | row exists (content lands in PR 2c) |

  \* `design.md`'s Rule Disposition table names this `RT_BIBLIOTECA_OBSOLETA`; `specs/veredicto-gate/
  spec.md`'s own scenario literally reads `RT_BIBLIOTECA_OBSOLETA`. Ship `RT_BIBLIOTECA_OBSOLETA`
  — `design.md` is this session's authority on naming — and flag the spec's stale spelling for
  correction; do not ship both.
- [ ] 1d.5 Rewrite (not delete) `RT_BESPOKE_UNDECLARED`'s existing fixture in place: its
      `pers_axes()` axis-check half is removed with the axes; its `tpl_wireframe_comps()` half is
      replaced by an Objetivo + Enfoque + section-id-list check. Keeps its id.
- [ ] 1d.6 Re-point 88 existing call sites in place (not new ids): `RT_MOCKUP_FONT_NOT_EMBEDDED`
      gains the `plantillas/**` relative-`.woff2`-resolves-on-disk exception; `RT_MOCKUP_DISCLOSURE_
      STATE`/`_GRID_AUTOFILL`/`_BLEED_*` gain `plantillas/*/maqueta/` as a second walked root (the
      second mockup root — `points_at_dir()` itself stays untouched per the design's rejected-alternative
      table).
- [ ] 1d.7 Add `fx_plantilla()`, `fx_enfoque()`, `fx_veredicto()`, `fx_biblioteca()`, `fx_indice()` to
      `tests/test-framework-audit.php`; register every new id in `ROW_TYPES` and `CONTRIBUTING.md` in
      the same PR (`fx_track_ids()` diffs observed ids against `ROW_TYPES` — an id without its fixture
      here goes red on the coverage assertion).
- [ ] 1d.8 Assert every new id's level with `fx_row_level()` at WARN (the FAIL transition is PR 3's
      job, except `RT_ENFOQUE_SIN_PLANTILLA`/`RT_ENFOQUE_REPETIDO_RECIENTE`, which stay WARN forever).
- Verification: same full chain as 1a/1c; if the diff nears 800 lines, split by id family (see
  Review Workload Forecast) before opening the PR.

## PR 1e — Remaining toolbox

- [ ] 1e.1 RED: `veredicto.php --sellar <slug>` on a Plantilla with an unresolved section → refuses to
      seal (exit 1); `--comprobar <slug>` on a stale hash → exit 1.
- [ ] 1e.2 GREEN: `veredicto.php` (`--sellar/--comprobar/--biblioteca`) writes/validates the
      `veredicto.md` hash via `huella.php`.
- [ ] 1e.3 RED: `comprobar-maqueta.php` against a fixture with `€68,00` or Rioplatense copy → reports
      the defect (locale check, spec `veredicto-gate` "Locale defect blocks the professional verdict").
- [ ] 1e.4 GREEN: `comprobar-maqueta.php` (tokens in `:root`, fonts requested vs served, accent,
      zero remote resources, non-empty `<title>`, es-ES price format).
- [ ] 1e.5 RED/GREEN: `empaquetar.php --publicar <slug> --out <ruta>` — fonts/images inlined as
      `data:` in a gitignored `dist/<slug>.html`; committed `maqueta/index.html` stays untouched
      (byte-count check: output size grows, source diff is zero).
- [ ] 1e.6 RED/GREEN: `indice.php --indice` regenerates `_indice.md`'s rows from disk; `--galeria`
      regenerates the gitignored `_galeria.html`.
- [ ] 1e.7 Add `## Herramientas` to `mockup-guide.md` naming each of the 7 tools by path and every
      public function by name (`RT_HELPER_UNROUTABLE`, WARN, derived — a function no asset calls must
      be named by some markdown).
- Verification: same full chain, plus `php tests/test-herramientas.php` covering all 4 new tools.

## PR 2a — `web-templates` rewrite

- [ ] 2a.1 `SKILL.md`: choose by Objetivo, hand the Plantilla (canvas+maqueta+ficha) to the design
      step, explicit Ruta a medida. Body ≤600 words.
- [ ] 2a.2 Delete stale References pointing at `templates/**`/`toggles.md`/`recommender.md`/
      `design-system.md` (files still exist until PR 3 — only the pointer/prose changes here).
- Verification: full chain (rebuild gallery first).

## PR 2b — `html-mockup` + `mockup-guide` + `ux-design-system` rewrite

- [ ] 2b.1 `mockup-guide.md`: derivation contract (desktop+mobile artboard → responsive CSS, one
      artifact, fonts embedded); mobile-artboard obligation stated as a Hard Rule.
- [ ] 2b.2 `ux-design-system/SKILL.md` + `design-tokens.md`: tokens expressed only via
      `es_kit_apply()` into Site Settings, never CSS; `layout-patterns.md`/`motion.md` filtered to
      what exists natively.
- Verification: full chain (rebuild gallery first — still present until PR 3).

## PR 2c — `blind-judges` mandatory + qa-review row 37 + route map

- [ ] 2c.1 RED: `capture.mjs` has no `--viewport`/`--medir` flags — running `--medir` against a
      fixture page fails to emit `{selector, tracks, hijos, scrollWidth, clientWidth}`.
- [ ] 2c.2 GREEN: implement `--viewport <w>` and `--medir`, emitting the four barrido measurements
      (spec `veredicto-gate`, `docs/plantillas-reales/2026-09-13-barrido-base.md`):
      1. `documentElement.scrollWidth <= clientWidth`;
      2. every `display:grid` with ≥3 children resolves >1 track at ≥1024px;
      3. every grid's `children % tracks === 0`;
      4. at 430px, exactly one `[aria-expanded]` header control and the desktop link list
         `display:none`.
- [ ] 2c.3 RED/GREEN fixtures per measurement, mirroring the exact defects the base sweep recorded
      (`.items.cols-4` single-track collapse; phantom-track/orphan-card grids; zero burger nav +
      CTA wrap at 430).
- [ ] 2c.4 `qa-review/references/house-rules.md` row 37: count `widgetType:"html"` + `custom_css` in
      `_elementor_data` (Divi: `et_pb_code` + custom CSS) against the Ficha's ceilings resolved via
      `es_manifest_record('design', slug)`; no slug recorded = UNVERIFIED, never PASS. Table now ends
      at 37 (`RT_HOUSERULES_ROW_PHANTOM` makes the count load-bearing).
- [ ] 2c.5 `agents/wordpress-orchestrator.md` + `_wordpress-orchestrator-framework.md`: route map with
      the Claude Design step, Ruta a medida, and the Veredicto gate before client approval.
- Verification: full chain (rebuild gallery first) + `node skills/blind-judges/assets/capture.mjs
  --viewport 430 --medir <fixture>` against a real served fixture page (runtime harness, real
  headless Chrome — not a PHP fixture).

## PR 3 — Amputation (size:exception), WARN→FAIL, `--clean`

- [ ] 3.1 Harvest remaining `$BRANDS`/`$CONTENT` (not wave 1) to `docs/plantillas-reales/cosecha/`.
- [ ] 3.2 Delete plan §5.1 per `design.md`'s File Changes table: `templates/**`, `toggles.md`,
      `recommender.md`, `design-system.md`, `html-mockup/assets/{gallery/**,chassis/*,proof-*.html,
      _axis-proof-content.md}`, `style-catalog/**` (BSP-tuscapas.md already relocated in PR 0b/1b).
- [ ] 3.3 Delete the 23 outright-retiring rule ids + `nm_axes()`, `axis_matches()`, `pers_axes()`,
      `axis_declarations()`, `axis_rows_for()`, `axis_signature_of_block()`, `proof_axis_signature()`,
      `tpl_wireframe_comps()`, `$mockup_axis_alt` — with their ~183 `ok()` call sites and fixtures
      (≈270 assertions), per `design.md`'s Rule Disposition.
- [ ] 3.4 Flip the 23 WARN-level ids from PR 1d/1c/2c to FAIL (`fx_row_level()` transition assertion),
      excluding `RT_ENFOQUE_SIN_PLANTILLA`/`RT_ENFOQUE_REPETIDO_RECIENTE`, which stay WARN.
- [ ] 3.5 `--clean` in `install.sh`/`install.ps1`: refuse unless `$DEST/skills` **and** `$DEST/agents`
      both exist, `$DEST` resolves outside `$HOME` itself, `$DEST` is not a symlink; delete only those
      two directories, print what will be removed, require the flag explicitly.
- [ ] 3.6 RED (threat matrix, one case each in `tests/test-herramientas.php` against a temp `$DEST`):
      `$DEST` unset/empty → exit 2, delete nothing; `$DEST = $HOME` → refuse; first install, no
      `skills/` yet → refuse `--clean`, allow plain install; symlinked `$DEST` → refuse.
- [ ] 3.7 GREEN: all four cases pass; a normal populated `$DEST` cleans and reinstalls.
- [ ] 3.8 Merge to `main`.
- [ ] 3.9 **Redeploy (human-gated, separate step)**: pause other Claude sessions → `install.ps1
      --clean` → `diff -rq ~/.claude/{skills,agents} <repo>/{skills,agents}` empty → resume.
- Verification: from this PR on, drop the gallery-rebuild prefix (generator deleted;
  `RT_GALLERY_NOT_BUILT`/`RT_GALLERY_STALE` go silent by construction — generator absent). Full
  chain: `php skills/framework-audit/assets/framework-audit.php && php tests/test-container-hygiene.php
  && php tests/test-framework-audit.php && php tests/test-audit-signals.php && php
  tests/test-write-path.php && php tests/test-replay.php` — 0 FAIL, deleted paths confirmed absent.

## PR 4.1 — `plantillas/delao/` (wave 1, corporate · cartera-curada · editorial)

- [ ] 4.1.1 Harvest the delao Canvas (7 `.dc.html` artboards, artifact `eab59854`), 12
      `delao-*.webp`, literal copy, `lanes.md`, `handoff-block.md` from the stale pilot branch (tip
      `ef4d80a`) — not merged, harvested only, per repo rule against history rewrite.
- [ ] 4.1.2 Human decision: approve the Canvas as-is or retouch (proposal Open Decision 2) before
      deriving the Maqueta.
- [ ] 4.1.3 Derive `maqueta/index.html` (5 pages + legal + 404) per `mockup-guide.md`'s contract;
      `manifiesto-imagenes.md`; contrast measured via `color.php --maqueta delao`; complete Mapeo
      nativo (two-way closure from 1d.3 must hold).
- [ ] 4.1.4 `veredicto.php --sellar delao`: juez B + `capture.mjs` sweep at 430/768/1280 served over
      `http`; update `_biblioteca.md` (juez A, "same hand" check) and `_indice.md`.
- Verification: full chain + the 24 new ids stay silent (not just fixture-passing) against real
  content + `capture.mjs` sweep, all pages, served over `http`, inside a subagent.

## PR 4.2–4.7 — remaining wave-1 Plantillas, one per PR

Same cycle as 4.1, in order (each takes a distinct Objetivo + Enfoque, per the plan's tanda-1 table):

| PR | Slug | Tipo | Objetivo | Enfoque |
|---|---|---|---|---|
| 4.2 | lumiere | corporate | ritual-bono | materia |
| 4.3 | terrazza | corporate | reservar-mesa | lujo-oscuro |
| 4.4 | aranda | corporate | stock-ocasion | tecnologico |
| 4.5 | corte | ecommerce | tienda-talla | vitrina |
| 4.6 | tueste | ecommerce | suscripcion | institucional |
| 4.7 | bajura | ecommerce | tienda-lote | directo |

Each PR: new Canvas in Claude Design (approved by the user, not a reproduction of the old gallery
strip) → derived Maqueta → manifest → contrast → Mapeo nativo → per-Plantilla Veredicto → library
Veredicto (`_biblioteca.md`, incremental) → `_indice.md`. `RT_PLANTILLA_PAR_IGUAL` and
`RT_BIBLIOTECA_MISMA_MANO` must both pass after each entry.

## Out of scope for this file

Wave 2 (Plantillas 8–10, 2–3 further sectors) and the one real client delivered end to end
(`shipped-log.md` + judges' corpus) are Phase 4, the user's later choice (proposal Open Decision 4) —
not tasked here.
