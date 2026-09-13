# Design: Plantillas reales — a real library, a perceptual gate, a native ceiling

> **Size deviation, declared.** The 800-word cap is exceeded. Eight architectural questions had to be
> resolved against verified `file:line` seams, and three of them (reachability, the committed hash,
> the audit's browser boundary) are places where the proposal's plan is *wrong on the measured
> facts*. Declared, following the `2026-08-28-style-catalog` precedent, rather than met by dropping
> the corrections.

## Technical Approach

The generator stops being the author; nothing replaces it as one. A **Plantilla** is a directory of
committed bytes — Canvas, Maqueta, img, Ficha, Veredicto — and every gate is a function of those
bytes plus one recorded browser measurement. Three seams carry the whole change:

1. **One reachable unit.** `reachable_files()` (`framework-audit.php:467`) is already transitive; what
   is *not* recursive is `points_at_dir()` (`:448`), which reaches a directory's DIRECT children only.
   A Plantilla folder becomes one unit by a 6-line scoped predicate, not by weakening that function.
2. **One digest definition, required out of the audited tree** — exactly the arrangement
   `_gallery-fingerprint.php:9-14` argues for ("two implementations of one rule drift, and the
   hand-rolled one loses"), with one change forced by the digest now being **committed**.
3. **The audit stays offline.** It measures contrast, paths, frontmatter and hashes itself; it
   *reads* the browser's verdict from `veredicto.md` and never drives Chrome. Same discipline the
   style-catalog design applied to `es_manifest_read()`.

## Architecture Decisions

### Decision: A Plantilla folder is one reachable unit, and `points_at_dir()` is not touched

**Choice**: keep the library whole under `skills/web-templates/references/plantillas/<slug>/`
(`ficha.md`, `canvas/`, `maqueta/index.html`, `img/`, `manifiesto-imagenes.md`, `veredicto.md`), plus
`_indice.md` and `_biblioteca.md`. Add to `framework-audit.php` a scoped predicate:

```php
/* A Plantilla is ONE reachable unit, anchored at its Ficha. Scoped on purpose: see below. */
function plantilla_unit( $rel ) {
	return preg_match( '#^references/plantillas/([a-z0-9-]+)/#', $rel, $m )
		? 'references/plantillas/' . $m[1] . '/ficha.md' : '';
}
```

used in the orphan loop (`:741-745`) to skip a file whose unit anchor is reachable. The chain is
`web-templates/SKILL.md` → `references/plantillas/_indice.md` → each row's **full path** to
`…/<slug>/ficha.md` → the whole folder. Full paths are mandatory, not style: `ficha.md`,
`veredicto.md` and `index.html` each occur once per Plantilla, so from the second Plantilla onward
`ambiguous_basenames()` (`:431`) refuses the bare basename and `file_handles()` (`:418`) demands the
path from the skill root.

| Option | Tradeoff | Verdict |
|---|---|---|
| Make `points_at_dir()` recursive (the plan) | Credits every descendant of any pointed-at directory repo-wide — the exact vacuity its own docblock (`:442-447`) was written to remove, and it silences `RT_ORPHAN_FILE` in `elementor-core/references/`, `qa-review/assets/` and everywhere else | Rejected |
| Library under `html-mockup/assets/` | `html_assets_deep()` (`:2126`) sweeps that root, so `RT_MOCKUP_*` would apply free — but it also matches `Main.dc.html`, and a Canvas is a 381 KB Claude Design export with remote fonts that would FAIL `RT_MOCKUP_FONT_NOT_EMBEDDED` on day one. It also puts web-templates' own catalog behind a skill boundary | Rejected |
| Split: documents in `references/`, `img/` + Maqueta in `assets/` | Breaks the unit, the one-folder digest, and one-PR-per-Plantilla revertibility; the Maqueta's `img/` paths would cross skills | Rejected |
| **Whole unit in `references/`, `RT_MOCKUP_*` walk gains a second root** | The mockup walk's root list grows from one path to two (`html-mockup/assets`, and `plantillas/*/maqueta` only) | **Chosen** |

**Rationale**: the unit rule trades UP, it does not weaken anything. Every file inside a Plantilla is
still named by a FAIL-level rule (`RT_PLANTILLA_SIN_CANVAS`, `_SIN_MAQUETA`,
`_IMAGEN_SIN_MANIFIESTO`) where `RT_ORPHAN_FILE` is only a WARN. Anchoring the unit at `ficha.md`
rather than at the directory means a folder with no Ficha stays fully orphaned *and* FAILs — two
rows, both true. The second mockup root is restricted to `maqueta/` by path, so the Canvas is
governed by `RT_PLANTILLA_SIN_CANVAS` alone: a Canvas is design authority, not a deliverable.

### Decision: the Ficha's section vocabulary is the Maqueta's own section ids — `COMP-*` retires

**Choice**: frontmatter exactly as the plan lists it (`slug`, `nombre`, `tipo`, `sector`, `objetivo`,
`enfoque`, `paginas`, `fuentes`, `canvas_url`, `variantes`, `html_widgets_max: 0`,
`css_custom_max: 0`), plus one field the plan lacks: `rupturas_extra: []` (see the derivation
decision). The machine-readable section list is the **first column of the `## Mapeo nativo` table**,
and each cell is the literal `id` of a `<section>` in `maqueta/index.html`.

**Alternatives considered**: keep `COMP-*` as the section vocabulary.

**Rationale**: measured, `COMP-*` has no consumer that survives Phase 2. It appears in
`_build-gallery.php` (352), `toggles.md` (93), the 47 `TPL-*` docs, `mockup-guide.md` (16),
`framework-audit.php` (12) and `style-catalog/` — all deleted — and **zero times anywhere under
`skills/elementor-core/`**. The builders never knew it. Its only parser, `tpl_wireframe_comps()`
(`:1350`, `:1381`), dies with `RT_TPL_NO_WIREFRAME` and `RT_BESPOKE_UNDECLARED`'s wireframe half.
Section ids are strictly stronger: a `COMP-*` id was checkable against a list, a section id is
checkable against the artifact, so `RT_PLANTILLA_SIN_MAPEO` becomes a two-way closure (every
`<section id>` has a Mapeo row; every Mapeo row names an id present in the file). And a vocabulary
shared across Plantillas is how "one site with 47 names" is spelled — the same abstract slot named in
every folder.

The Ficha names its `enfoque` **by id only**; the 8 axes live once, in `enfoques.md`. The measured
failure being corrected is two lists that disagreed (8 `STY-*` vs 5 `$ANCHORS`), and the way to not
have that again is to not have two lists.

### Decision: `huella.php` normalises text by extension allowlist, and the library digest is a digest of digests

**Choice**: `huella.php` copies `_gallery-fingerprint.php`'s exact shape — `path => sha256`, paths
relative to `skills/`, a missing input recorded as `absent` (never skipped), `ksort`, digest =
`sha256` over `"<path> <hash>\n"` lines — with three deliberate differences.

1. **Coverage** per Plantilla: `ficha.md`, `manifiesto-imagenes.md`, `canvas/**`, `maqueta/**`,
   `img/**`. **`veredicto.md` is excluded** — including the file the digest is stamped into is a
   fixed point that can never be sealed.
2. **LF normalisation, by extension allowlist.** `.md .html .json .css .js .svg .txt` are read,
   `\r\n → \n`, then hashed; `.webp .woff2 .png .jpg .avif` are hashed raw. The gallery fingerprint
   hashes raw and says why (`:35-38`): its output is untracked, so a digest never crosses a
   checkout. **This one is committed**, so with `core.autocrlf=true` a raw digest would report the
   platform as a defect. The allowlist is explicit because that same docblock is right that sniffing
   text-vs-binary and getting it wrong on a `.woff2` is silent corruption of the check itself.
3. **`.gitattributes` (new file)** pins `eol=lf` for the text set under
   `skills/web-templates/references/plantillas/**` and `binary` for the image/font set. Belt and
   braces on purpose: the attribute keeps the *diff* clean for files git checks out, normalisation
   keeps the *hash* right for bytes a local tool wrote (PowerShell redirection emits CRLF).

**Library composition**: `_biblioteca.md` holds one row per Plantilla, `slug` + its huella, and its
own huella is `sha256` over `"<slug> <huella>\n"` lines sorted by slug — never a re-hash of every
byte. So one Plantilla changing moves exactly one row, `RT_BIBLIOTECA_OBSOLETA` names *which*, and
judge A recaptures only that cover. Covers land at `plantillas/_capturas/<slug>-<huella12>.png`
(gitignored); a cover whose huella-keyed file already exists is reused, which is what bounds the
"~10 min per round" risk to one capture per entry instead of N.

**Alternatives considered**: hash raw like the gallery (breaks on Windows, and the digest is
committed); hash the whole library as one flat manifest (every Plantilla change invalidates every
cover); include `veredicto.md` (unsealable).

### Decision: the toolbox lives in `html-mockup/assets/herramientas/`, dual-mode, and the audit `require`s it

**Choice**: seven files, each simultaneously a library and a CLI, guarded by
`if ( 'cli' === PHP_SAPI && realpath( $argv[0] ) === __FILE__ )` so `framework-audit.php` can
`require` `huella.php` and `color.php` out of the tree it was given with `--root` — the same
arrangement, for the same stated reason, as `_gallery-fingerprint.php` today.

| File | Extracted from | CLI |
|---|---|---|
| `color.php` | `srgb_lum`/`contrast`/`ratio_str`/`css_mix` (`_build-gallery.php:200-222`), `ink_tint`/`ink_ends`/`ink_curve` + the accent gate (`:942-1116`) | `--contraste <#hex> <#hex>`; `--maqueta <slug>` (re-measures every `:root` pair against 4.5:1 text / 3:1 UI) |
| `scrim.php` | `worst_pixel`/`ink_mean`/`ink_pixel`, GD (`:1545-1700`) | `--peor-pixel <img> <x> <y> <w> <h>` |
| `huella.php` | `_gallery-fingerprint.php`'s pattern | `--plantilla <slug>`, `--biblioteca`, `--comprobar` |
| `veredicto.php` | new | `--sellar <slug>`, `--comprobar <slug>`, `--biblioteca` |
| `comprobar-maqueta.php` | new | `--maqueta <slug>` |
| `empaquetar.php` | `img()` (`:207`) + `_embed-fonts.php` | `--publicar <slug> --out <ruta>` |
| `indice.php` | `template_card_html()`'s idea | `--indice`, `--galeria` |

Uniform exit contract: `0` pass, `1` a measured failure, `2` usage/environment. **Never `0` for
"could not measure"** — that is the typed-not-available discipline, at the process boundary.

**Reachability** (`RT_HELPER_UNROUTABLE`, WARN, derived at `:960-999`): a function no asset calls must
be named by some markdown. `mockup-guide.md` gains one `## Herramientas` section naming each file by
path and each *public* function by name. Functions called by a sibling tool need no mention — the
list is derived, and enumerating it is the staleness the rule exists to catch.

**Rejected**: putting the tools in `framework-audit/assets/` (the audit would then own the
Maqueta's colour engine, and its own assets are audited by itself), and one `herramientas.php`
god-file (the amputation exists to stop exactly that).

### Decision: the audit never opens a browser — three defect classes become one cell vocabulary

**Choice**: split the gates by what can be computed from bytes.

- **Offline, in PHP, by the audit**: contrast from the Maqueta's `:root` (`RT_PLANTILLA_CONTRASTE`),
  page/anchor closure (`RT_PLANTILLA_PAGINAS_ROTAS`), remote URLs and font resolution
  (`RT_PLANTILLA_SIN_MAQUETA`), `@media` widths vs the declared set (`RT_PLANTILLA_RUPTURA`),
  frontmatter, hashes, index rows.
- **Recorded, then read**: everything that needs layout. `capture.mjs` gains `--viewport` and
  `--medir`; the sweep writes the `barrido:` table into `veredicto.md`; the audit reads cells.

A `barrido:` cell is `✓` **only** when all four hold at that viewport, and any other value
(`PARCIAL`, `no-disponible`, or a finding string) is a `RT_PLANTILLA_BARRIDO` FAIL:

| # | Measured | Catches (`docs/plantillas-reales/2026-09-13-barrido-base.md`) |
|---|---|---|
| 1 | `scrollWidth <= clientWidth` on `documentElement` | the anchor bar leaving the screen |
| 2 | for every `display:grid`: resolved track count > 1 at ≥1024 when it has ≥3 children | `.items.cols-4` computing ONE 173px track at every width |
| 3 | for every `display:grid`: `children % tracks === 0` | phantom tracks and orphan cards on three pages |
| 4 | at 430 only: exactly one `[aria-expanded]` control in the header, and the desktop link list `display:none` | **no burger nav on any page**, and the CTA wrapping to a second row |

`--medir` dumps `{selector, tracks, hijos, scrollWidth, clientWidth}` per grid, so 2 and 3 are two
integers, not a judgement. Non-empty `<title>` per page and es-ES price format (`68,00 €`, symbol
last) are string checks and go to `comprobar-maqueta.php`, offline.

**Rationale**: `RT_PLANTILLA_DESBORDE` as the plan names it would have required the audit to drive
Chrome. The audit is the offline chain's first command; making it depend on a browser makes the whole
chain unrunnable on a machine without one, and the fallback ("skip if absent") is a gate that
disappears exactly when it matters. `RT_VEREDICTO_INCOMPLETO` owns the document's *shape* (juez B,
`autojuzgado`, `vistas/saltadas`, one row per `paginas:` entry × three columns);
`RT_PLANTILLA_BARRIDO` owns a *present cell that is not* `✓`. No overlap.

### Decision: two breakpoints, because the builder library carries exactly two suffixes

**Choice**: a Maqueta declares `@media` at **`max-width:1024px`** (tablet) and **`max-width:767px`**
(mobile), and nothing else. `RT_PLANTILLA_RUPTURA` (FAIL) reads every `@media` width out of the file
and compares against that set plus the Ficha's `rupturas_extra:` (default `[]`); a declared extra
must also name, in the Mapeo nativo's Nota column, the Elementor custom breakpoint it maps to.

**Rationale**: measured, `es-builder.php:1716` accepts exactly
`^(flex_direction|grid_columns_grid|content_width)_(tablet|mobile)$` and `es_grid()` (`:1080-1082`)
writes exactly `grid_columns_grid`, `_tablet`, `_mobile`. The generated chassis uses 599 / 767 / 768
/ 900 / 1023 / 1024 / 1280 — seven — and 900 and 599 have no native expression at all. A Maqueta that
breaks at 900 declares a layout the native build cannot reproduce, so it is a `css_custom_max`
violation authored two steps upstream of the count. The ceiling belongs in the derivation contract,
not only in QA.

**Mobile artboard obligation**: every page with a desktop artboard MUST have `<Pagina>-movil.dc.html`
beside it at 390 wide; `RT_PLANTILLA_SIN_CANVAS` checks the pairing by filename. That is where the
burger is *designed* — the barrido measured zero burger navs in the entire generated catalog, which
is what happens when mobile is derived rather than drawn.

**One file, two forms**: on disk `maqueta/index.html` is one document with inline `<style>`,
`<img src="img/…">` and `@font-face` at `../../../../html-mockup/assets/fonts/*.woff2`. Pages are
`<section data-pagina="inicio">` with `href="#inicio"` navigation, so dead-link closure is pure
string work offline. `empaquetar.php --publicar` emits a gitignored `dist/<slug>.html` with fonts and
images inlined as `data:` for the Artifact. Committing the inlined form instead would put ~7 × 60 KB
× 1.37 of base64 into every Plantilla's diff and make `huella.php` hash the same bytes twice.
`RT_MOCKUP_FONT_NOT_EMBEDDED` gains one exception, scoped to `plantillas/**`: a relative
`*.woff2` path that **resolves on disk** counts as embedded. The resolution check is what keeps the
exception from being a hole.

### Decision: `vocabulario-nativo.md` is generated by a read-only sandbox introspector and carries a seal

**Choice**: a new `skills/elementor-core/assets/es-vocabulario.php`, uploaded to the sandbox, writes
nothing, and walks `\Elementor\Plugin::instance()->widgets_manager->get_widget_types( null )`
(measured to return all 130 — `es-builder.php:2451`), emitting per widget type: registered, Pro or
free, and which layout controls it owns via the same question `es_owns_control()` (`:2456`) asks. It
also emits the responsive suffix set from `:1716` and the kit slots `es_kit_apply()` (`:3664`)
carries `es_tokens()` into. Output is markdown on STDOUT, headed by a seal:
`elementor:` · `pro:` · `widgets:` · `fecha:` · `generado_por: es-vocabulario.php`.
`RT_VOCABULARIO_SIN_SELLO` (FAIL) requires those five fields.

**Alternatives considered**: a `--vocabulario` mode on `es-builder.php` (touches the write path the
`emitted-golden.txt` golden covers, for a read-only feature); writing it by hand
(`elementor-core/references/knowledge.md:41-51` records that the from-memory answer was wrong in
**both** directions across 128 widget types — five container keys claimed on widgets that own none,
and `padding`/`background_background` denied to ten that do).

**Rationale**: the seal is what makes "never hand-written" checkable rather than aspirational. And
`es_kit_apply()` carrying tokens into the global kit is precisely what makes `css_custom_max: 0`
*reachable*: colour and type live in Site Settings, so a Maqueta whose `:root` maps 1:1 onto kit
slots needs no stylesheet. A token with no kit slot is a design change request, declared in the Mapeo
nativo's Nota column — never a CSS exception.

**What `qa-review` counts** — new house-rules row **37** (the table ends at 36; `RT_HOUSERULES_ROW_PHANTOM`
makes the number load-bearing), verdict source `auto`, worlds W1/W3 like row 11 because it reads
stored data:

- `html_widgets` = elements with `widgetType === 'html'` in `_elementor_data` (Divi: `et_pb_code`),
  over every page **and** every Theme Builder template in scope.
- `css_custom` = **CSS rules**, not fields: concatenate per-element `custom_css`, page-settings
  `custom_css` and the kit's `custom_css`, strip comments, count `{`-delimited blocks.
- Compare against the Ficha's ceilings, resolved through the manifest's `design` section (which now
  persists the Plantilla slug). No slug recorded = **UNVERIFIED**, never PASS.

`RT_QA_NO_AXIS_CHECK` is *renamed* to `RT_QA_SIN_RECUENTO_NATIVO` (same "house-rules names the thing
the gate demands" shape), so this costs a fixture rewrite, not a new fixture.

### Decision: `nm_axes()` and `axis_matches()` are deleted, not repurposed

**Choice**: delete both, with `pers_axes()`, `axis_declarations()`, `axis_rows_for()`,
`axis_signature_of_block()`, `proof_axis_signature()`, `tpl_wireframe_comps()` and
`$mockup_axis_alt` (`:2405`). Nothing parses the 8 axes afterwards; they exist as prose in
`enfoques.md`. `RT_ENFOQUE_SIN_PLANTILLA` (WARN) needs the Enfoque **id list** only.

**Alternatives considered**: keep `nm_axes()` to validate each Enfoque's 8 axis lines.

**Rationale**: that is `RT_PERS_BAD_AXIS` under a Spanish name, and the measured finding this whole
change answers is that a validated axis table changed 4 pixels out of 8 — composition, accent,
chassis and ornament were emitted as *comments*. An axis the audit parses is an axis something will
eventually try to emit. `nm_axes()` was the right answer to the style-catalog's question (four
hardcoded copies that had to agree); with all five consumers retired it has no callers left.

### Decision: rule disposition — 27 ids out, 25 in, `ROW_TYPES` ends at 78

Verified against the real registry (`framework-audit.php:48-131`): **80 ids today**, five of them
newer than the plan (`RT_CAPTURE_OUT_DEFAULTED`, `RT_ROWTYPE_PHANTOM`, `RT_HOUSERULES_NO_WORLD`,
`RT_MIGRATION_NO_EXCLUDE`, `RT_HOUSERULES_ROW_PHANTOM` — all orthogonal, all untouched).

**Corrections to plan §5.4**, which is internally inconsistent:

| Plan says | Measured / decided |
|---|---|
| "Retirar (25)" then lists **26** | 23 retire outright; 4 more are **renames** whose semantics and fixture survive; 27 ids leave `ROW_TYPES` |
| `RT_BESPOKE_UNDECLARED` is in the retire list **and** "conserva su id" | Keeps its id. Its `pers_axes()` half dies with the axes; its `tpl_wireframe_comps()` half is replaced by Objetivo + Enfoque + section-id list |
| `RT_GALLERY_NOT_BUILT`/`_STALE` → `RT_INDICE_NO_GENERADO`/`_OBSOLETO`, **and** `RT_INDICE_DESACTUALIZADO` | Three ids for one fact. `_galeria.html` is gitignored, so those two would demand a build before a fresh clone can be green — the `RT_GALLERY_NOT_BUILT` pattern this change is removing. **Only `RT_INDICE_DESACTUALIZADO`** (FAIL): `_indice.md`'s rows and short hashes vs the folders on disk. Both dropped |
| `RT_PLANTILLA_DESBORDE` via `capture.mjs` | Renamed `RT_PLANTILLA_BARRIDO`, reads a recorded cell. The audit stays offline |
| `RT_QA_SIN_RECUENTO_NATIVO` added while `RT_QA_NO_AXIS_CHECK` retires | One rename |
| ~19–20 added | **25** (21 new + 4 renames), including `RT_PLANTILLA_RUPTURA` and `RT_VOCABULARIO_SIN_SELLO`, which the plan lacks |

New ids, all `RT_` + a Spanish noun: `RT_PLANTILLA_{SIN_FICHA, SIN_CANVAS, SIN_MAQUETA,
PAGINAS_ROTAS, SIN_MAPEO, CONTRASTE, BARRIDO, PAR_IGUAL, RUPTURA, IMAGEN_SIN_MANIFIESTO,
UNA_SESION}` · `RT_VEREDICTO_{AUSENTE, OBSOLETO, INCOMPLETO, NO_PROFESIONAL}` ·
`RT_BIBLIOTECA_{AUSENTE, OBSOLETA, MISMA_MANO}` · `RT_ENFOQUE_{SIN_PLANTILLA, REPETIDO_RECIENTE}` ·
`RT_INDICE_DESACTUALIZADO` · `RT_LEDGER_SIN_VEREDICTO` · `RT_RECOMENDADOR_SIN_RUTA_A_MEDIDA` ·
`RT_QA_SIN_RECUENTO_NATIVO` · `RT_VOCABULARIO_SIN_SELLO`.

`RT_ENFOQUE_REPETIDO_RECIENTE` keeps `RT_STYLE_REPEATS_RECENT`'s exact mechanic, decided in the
archived style-catalog design D5: last 5 data rows of `shipped-log.md`, WARN if the newest row's
Enfoque also appears in the 4 before it. `RT_LEDGER_SIN_VEREDICTO` reads two new ledger columns
(`veredicto_hash`, `veredicto_fecha`).

**Harness constraint, honoured**: `fx_run_ok()` injects `--row-types` into every scenario and
`fx_track_ids()` (`test-framework-audit.php:98`) accumulates every id printed in the ID column, then
diffs against `ROW_TYPES`. So **every new id ships with its fixture in the same PR** or the chain
goes red on the coverage assertion — and every retired id must lose its fixture in the same PR too,
or `add()` exits 3 on an unregistered id.

### Decision: PR slicing, and a cut-over that does not break the deployed copy

**Choice**: `feature-branch-chain` off `main @ 123a736`, 800-line budget, one `size:exception`.

| PR | Scope | Est. lines | Budget |
|---|---|---|---|
| 0 | Rescue the deployed-only orphan `woocommerce/references/add-to-cart-from-listing.md`; `docs/plantillas-reales/` witness | ~120 | OK |
| 1a | `.gitattributes`; `herramientas/{color,scrim,huella}.php` + tests | ~620 | OK |
| 1b | `glosario.md`, `enfoques.md`, `paginas-obligatorias.md`, `recomendador.md` (with the Ruta a medida step) | ~500 | OK |
| 1c | `es-vocabulario.php` + generated `vocabulario-nativo.md` + `RT_VOCABULARIO_SIN_SELLO` | ~450 | OK |
| 1d | Ficha/Veredicto formats; `plantilla_unit()`; 25 rules **at WARN**; `fx_plantilla/fx_enfoque/fx_veredicto/fx_biblioteca/fx_indice`; `CONTRIBUTING.md` rows | ~780 | **at the line** |
| 1e | `herramientas/{veredicto,comprobar-maqueta,empaquetar,indice}.php` + tests | ~700 | OK |
| 2a–2c | Skill rewrites: `web-templates` / `html-mockup` + `mockup-guide` / `ux-design-system` + `blind-judges` + `qa-review` row 37 + orchestrator route map | ~500 each | OK |
| 3 | **Amputation**: harvest to `docs/plantillas-reales/cosecha/`, delete plan §5.1, WARN → FAIL, `--clean` | ~25 000 (deletion) | **`size:exception`** |
| 4.1–4.7 | One Plantilla per PR (`delao` first) | ~600 each | OK |

PR 1d is the risky one: 25 rule bodies plus their fixtures against a 780-line estimate. If it
overruns, split by id family (`PLANTILLA` / `VEREDICTO` + `BIBLIOTECA` / the rest) — each family is
independently green because rules land at WARN.

**Cut-over ordering.** `install.sh:33-35` states it plainly: overwrite in place, and *"files deleted
upstream are NOT removed"*. So:

1. Phases 0–2 are purely additive → redeploying without `--clean` is safe, and other sessions keep
   working off a `~/.claude` that has both vocabularies.
2. `--clean` lands **in the amputation PR itself**, but the redeploy is a separate announced step:
   merge → pause other sessions → `install --clean` → `diff -rq ~/.claude/skills <repo>/skills` empty
   → resume.
3. `--clean` removes `$DEST/skills` and `$DEST/agents` wholesale, then copies. Rejected: an
   installed-file manifest (state the installer has never kept); rejected: per-file diff deletion (it
   would silently delete a user's own file under `~/.claude/skills/`). Guard: refuse unless both
   directories already exist and `$DEST` is not `$HOME` itself.
4. **Ordering constraint**: the deployed-only orphan must reach `main` in PR 0, *before* any
   `--clean` run destroys it. That is why PR 0 is a PR and not housekeeping.

Rollback is reverse phase order, as the proposal states; reverting the amputation means re-running
`install` **without** `--clean`.

## Data Flow

    Canvas (.dc.html, desktop + -movil)          ← design authority, user-approved
        │ derived by hand under mockup-guide.md's contract
        ▼
    maqueta/index.html ──► comprobar-maqueta.php ──► color.php / scrim.php   (offline)
        │                                                    │
        │ capture.mjs --viewport --medir (430/768/1280)       │
        ▼                                                    ▼
    barrido cells ──► veredicto.md ◄── huella.php --sellar ◄── ficha.md + canvas/ + img/
        │                    │
        │                    └──► _biblioteca.md  (sha256 of the per-slug huellas; judge A)
        ▼
    framework-audit.php  ── require ─► herramientas/huella.php, color.php
        │  reads bytes + recorded cells; never opens a browser
        ▼
    RT_PLANTILLA_* / RT_VEREDICTO_* / RT_BIBLIOTECA_* / RT_INDICE_DESACTUALIZADO

    ficha.md ceilings ──► es_manifest_record('design', slug) ──► qa-review row 37
    es-vocabulario.php (sandbox, read-only) ──► vocabulario-nativo.md ──► every Canvas

## File Changes

| File | Action | Description |
|---|---|---|
| `web-templates/references/plantillas/<slug>/**` | Create | The unit: `ficha.md`, `canvas/`, `maqueta/index.html`, `img/`, `manifiesto-imagenes.md`, `veredicto.md` |
| `web-templates/references/plantillas/{_indice,_biblioteca}.md` | Create | Row per Plantilla with full path to its `ficha.md`; library huella + judge A |
| `web-templates/references/{glosario,enfoques,vocabulario-nativo,paginas-obligatorias,recomendador}.md` | Create | Contract documents; the last is generated |
| `html-mockup/assets/herramientas/*.php` | Create | Seven dual-mode tools |
| `elementor-core/assets/es-vocabulario.php` | Create | Read-only sandbox introspector |
| `.gitattributes` | Create | `eol=lf` + `binary` for `plantillas/**` |
| `framework-audit.php` | Modify | `plantilla_unit()`; second mockup root; 27 ids out; 25 in; `nm_axes()`/`axis_matches()` and 6 parsers deleted |
| `tests/test-framework-audit.php` | Modify | See Testing Strategy |
| `qa-review/references/house-rules.md` | Modify | Row 37 |
| `blind-judges/assets/capture.mjs` | Modify | `--viewport`, `--medir` |
| `install.sh`, `install.ps1` | Modify | `--clean` |
| `html-mockup/assets/{gallery/**,chassis/*,proof-*.html,_axis-proof-content.md}` | Delete | Amputation PR |
| `web-templates/references/{templates/**,toggles.md,recommender.md,design-system.md}` | Delete | Amputation PR |
| `ux-design-system/references/style-catalog/**` | Delete | Amputation PR |

## Testing Strategy

Measured on `tests/test-framework-audit.php` (5 751 non-blank lines, 532 `ok()` call sites,
797 assertions reported):

| Layer | What | Approach | Measured churn |
|---|---|---|---|
| Unit | The seven tools | New `tests/test-herramientas.php`, added to the `CONTRIBUTING.md` chain in the same PR or `RT_GATE_LINE_UNREGISTERED` FAILs | ~150 new assertions |
| Unit | `huella.php` normalisation | RED first: same tree written once CRLF and once LF → identical digest; a `.woff2` with a `\r\n` byte pair → digest unchanged by normalisation | ~20 |
| Integration | Retiring rules | **183 of 532 `ok()` call sites** name a rule that retires outright (≈270 of 797 assertions) — deleted with their fixtures in the amputation PR | −183 sites |
| Integration | Re-pointed rules | **88 call sites** name a rule that survives with a new target (`RT_MOCKUP_*`, `RT_GALLERY_NO_MANIFEST`/`_ONE_SHOOT`, `RT_BESPOKE_UNDECLARED`, `RT_STYLE_REPEATS_RECENT`) — **rewritten in place**, not deleted. The plan's estimate omitted this half | ~88 rewritten |
| Integration | 25 new ids | `fx_plantilla()`, `fx_enfoque()`, `fx_veredicto()`, `fx_biblioteca()`, `fx_indice()`; one mutation scenario per id (drop a field, age a hash, plant a remote URL, plant a non-`✓` cell, plant a duplicate `tipo+objetivo+enfoque` pair) | ~130 new |
| Integration | Levels | Every new id asserted with `fx_row_level()`, not presence — WARN in Phase 1, FAIL after the amputation, and the transition is itself an assertion change | 25 × 2 |
| Coverage | The ratchet | `fx_track_ids()` diffs observed ids against `ROW_TYPES` — automatic, no per-scenario opt-in | — |
| Untouched | `tests/test-write-path.php` and `emitted-golden.txt` | The write path is not in scope | 0 |

Total touched ≈ 400 of 532 call sites (**~55–60 % of the assertion surface**, against the plan's
35–40 %). Strict TDD: every rule is RED with its fixture before its body exists.

## Threat Matrix

| Boundary | Minimum adversarial cases | Applicability | Design response | Planned RED tests |
|---|---|---|---|---|
| Documentation-like paths | `.dc.html` swept as a Maqueta; `_galeria.html`; `*.example.php` | **Applicable** | Classification is by **path**, not extension: the second `RT_MOCKUP_*` root is `plantillas/*/maqueta/` only, so a Canvas is never judged as a deliverable | A Canvas with a remote Google Fonts `@import` emits **no** `RT_MOCKUP_FONT_NOT_EMBEDDED`; the same import inside `maqueta/index.html` FAILs |
| Git repository selection | `git -C`, relative vs absolute `--root` | N/A — this change adds no git invocation; `--root` resolution (`:144-159`) is untouched | — | — |
| Commit state | staged / `commit -a` / empty index | N/A — nothing in this change reads or writes the index | — | — |
| Push state | tracking branch, first push | N/A — no push automation | — | — |
| PR commands | `--head`, env prefix, composed commands | N/A — no PR automation | — | — |
| **Destructive install (`--clean`)** | `$DEST` unset/empty → `rm -rf /skills`; `$DEST` = `$HOME`; a first install with no `skills/` yet; a symlinked `$DEST` | **Applicable** | Refuse unless `$DEST/skills` **and** `$DEST/agents` both exist, `$DEST` resolves outside `$HOME` itself, and `$DEST` is not a symlink; delete only those two directories; print what will be removed and require the flag explicitly | One RED test per case in `tests/test-herramientas.php` against a temp `$DEST`; the empty-`$DEST` case must exit 2 and delete nothing |
| **Sandbox introspection subprocess** | `es-vocabulario.php` left in `wp-content/novamira-sandbox/` after hand-off | **Applicable** | Read-only, no `es_*` write call, and it is covered by existing house-rule 22 (`es_sandbox_report()` must return empty) with no exemption added | A tree with `es-vocabulario.php` still in the sandbox must FAIL row 22 exactly as any other leftover |
| **GD / headless-Chrome subprocess** | GD absent; Chrome absent; a partial sweep | **Applicable** | `scrim.php` exits 2 (never 0) when GD is missing; a missing browser writes `no-disponible` into the cell, which is not `✓`, so `RT_PLANTILLA_BARRIDO` FAILs | Fixture with a `no-disponible` cell → FAIL, not PASS; fixture with a `PARCIAL` cell → FAIL |

## Migration / Rollout

Phased exactly as the proposal's table, with the cut-over ordering decided above. No data migration:
there is no runtime state. The one irreversible step is `install --clean`, gated behind PR 0's rescue
of the deployed-only orphan and behind a `diff -rq` verification with other sessions paused.

## Open Questions

- [ ] Client material already in a public Apache-2.0 repo (`BSP-tuscapas.md`,
      `corpus/2026-09-12-arborea-*.jpg`) — anonymise, move out of tree, or accept. **Blocks the
      amputation PR**, because that PR is where `BSP-tuscapas.md` would otherwise simply be deleted
      and the question would disappear unanswered. User, before Phase 2.
- [ ] Whether `colour-and-tone-system` retires or survives as a floors-only capability once
      `color.php`/`scrim.php` are its only host. `sdd-spec` decides; the tool contract above works
      either way.
- [ ] `rupturas_extra:` needs a name for the Elementor custom breakpoint it maps to. Elementor's
      extra breakpoints (`mobile_extra`, `tablet_extra`, `laptop`, `widescreen`) are off by default;
      whether the native build may enable one is a Phase 4 question and `es-vocabulario.php`'s output
      is what will answer it.
- [ ] Judge A's cover capture is keyed by huella. If a Plantilla changes only its Ficha prose, the
      huella moves and the cover is recaptured for nothing. Narrowing the cover key to
      `maqueta/ + img/` would fix that but would give `_biblioteca.md` a second digest definition —
      deliberately deferred rather than guessed.
