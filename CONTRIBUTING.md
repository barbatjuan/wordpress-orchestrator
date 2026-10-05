# Contributing

The point of this repo is to get better every project. Two rules keep it healthy.

## 1. Gotchas are the gold
When a build surprises you, capture it in the right `skills/<skill>/references/gotchas.md`
in this exact shape:

```
## <short title>
Symptom: <what you saw>
Cause: <verified reason>
Fix: <what worked>
Do NOT: <the trap>
```

Only add CONFIRMED findings. "Probably" belongs in a PR discussion, not in gotchas.

## 2. Keep the shape
- `SKILL.md` body stays concise: **aim ~500 words, hard ceiling ~600**. Detail goes in `references/`,
  code in `assets/`; the body loads on every activation. The audit counts and FAILs past 600.
  `php skills/framework-audit/assets/framework-audit.php --word-report` prints the current numbers
  (`elementor-core` sits near the ceiling: adding a sentence there can break the gate).
- Frontmatter needs `name`, `description` (with trigger words first), `license`,
  `metadata.author`, `metadata.version`.
- The orchestrator never gains CSS/HTML/PHP. Execution lives in skills.
- Builder-agnostic knowledge goes in `ux-design-system`; builder-specific execution in
  `elementor-core` / `divi-core`.

## 3. Every rule names its verifier, and every warning reaches a human

Review questions on any PR that adds a rule.

**Scope**: enforced for the nine write-capable skills (`elementor-core`, `divi-core`, `woocommerce`,
`wordpress-seo`, `wordpress-performance`, `wordpress-security`, `wordpress-forms`, `wordpress-legal`,
`elementor-theme-parts`) **and** the orchestrator's `## House rules`, which every build inherits.
An agent stating no House rules is `RT_AGENT_NO_HOUSE_RULES` (FAIL).

- **A rule with no verifier is a wish.** Every bullet under a write-capable skill's `## Hard Rules`
  must carry a **verifier marker**: `(verifier: <what checks it>)` or `(no verifier: <the admitted gap>)`,
  as its own line, exact lowercase token. The audit enforces that a marker PARSES and is not a
  placeholder, never its wording.
  - **FAIL**: missing closing paren (`RT_MARKER_UNCLOSED`); two markers on one bullet
    (`RT_MARKER_MULTIPLE`); token case not
    exactly `verifier:`/`no verifier:` (`RT_MARKER_CASE`); payload empty (`RT_MARKER_EMPTY`), under 12
    characters (`RT_MARKER_TOO_SHORT`), a placeholder like `TODO`/`n/a`/`x` (`RT_MARKER_STOPWORD`), or
    over 40 words (`RT_MARKER_OVERSIZE`); a `(verifier: …)` naming a row type, function, `tests/` path,
    house-rules row or execution step that does not exist (`RT_MARKER_TARGET_MISSING`).
  - **JUDGE**: a bullet with no marker (`RT_MARKER_ABSENT`); a `(verifier: …)` naming no locatable
    target (`RT_MARKER_PROSE_ONLY`; a documented gap is valid prose, so reported, not blocked); a
    `(no verifier: …)` naming something that DOES exist (`RT_MARKER_MISLABEL`; use `(verifier: …)`),
    except a backticked `tests/…` path, which may be cited as context for a gap. A write-capable skill
    with no Hard Rules, or the heading with no bullets under it, is a FAIL (`RT_HARD_RULES_MISSING_WRITE`).
  - **Both polarities cost the same**: the stop-word/length/size checks apply identically to
    `(verifier: …)` and `(no verifier: …)`.
  - A marker resolves against one of five shapes, tried in this order: an `es_[a-z_]+(` helper call,
    a backticked `tests/…` path, a `` `qa-review` house-rule row N``, a `step N` (optionally
    `` `<skill>` step N``), or a **backticked** `` `RT_…` `` row type. The row-type shape is tried LAST
    and must be backticked so a passing mention cannot silence the target the marker actually named.
    Function existence is checked with PHP's tokenizer: a name only in a comment or string does not count.
  - Marker lines are excluded from the `SKILL.md` word budget, capped at 40 words each on **every**
    skill (a marker over the cap is not excluded).
- **A warning only in `error_log()` is a warning nobody reads.** The sandbox returns STDOUT; the
  server's PHP log is never fetched. Route every warning through `es_warn()` (or echo alongside
  `error_log()` where the helper library may not be loaded).
- **Don't re-implement a check in prose.** `qa-review` calls `es_container_audit()` rather than
  describing the walk again; two implementations of one rule drift.
- **Every file under `references/` or `assets/` must be REACHABLE, at any depth**
  (`RT_ORPHAN_FILE`). Reachability starts at `SKILL.md` and spreads only through files already
  reachable. A file is named by its path, by its filename **with the extension**, or by its family
  prefix (`TPL-DEEP-01` reaches `TPL-DEEP-01-first.md`), never by the bare stem. A pointer at a
  directory reaches its DIRECT children only (a pointer at `references/plantillas/` reaches
  `_indice.md`, which names the folders below); the skill's own `references/`/`assets/` roots are not
  pointers. A filename that occurs twice in one skill must be cited by its **full path from the skill root**.
- **A helper nothing calls has to be named by something** (`RT_HELPER_UNROUTABLE`). A function no asset
  calls is an ENTRY POINT: only an instruction file telling a model to call it can invoke it, so one no
  markdown names is either dead weight or written, tested and never wired (`es_set_front_page()` once
  was). The list is DERIVED, never enumerated. `*.example.php` defines nothing here: it is a file you
  copy and rewrite.
- **Prefer a helper over a rule.** When the library makes the wrong shape the easy one, no
  documentation wins. Fix the library first (`es_split()`, `es_wide()`, `es_photo()`), then write the rule.

### Row types

Every row `framework-audit.php` can print is declared in its `ROW_TYPES` registry (`… --emit-row-types`
lists them) and mirrored here. An ID missing from this table is a FAIL (`RT_ROWTYPE_UNDOCUMENTED`).

| Row type | Level | What triggers it |
|---|---|---|
| `RT_NO_SKILL_MD` | FAIL | a skill directory has no `SKILL.md` |
| `RT_NO_FRONTMATTER` | FAIL | `SKILL.md` has no YAML frontmatter block |
| `RT_FRONTMATTER_MISSING_KEY` | FAIL | frontmatter is missing a required key |
| `RT_NAME_MISMATCH` | FAIL | frontmatter `name:` does not match its directory |
| `RT_NO_TRIGGER` | FAIL | description carries no `Trigger:` words |
| `RT_BODY_OVER_600` | FAIL | `SKILL.md` body is past the ~600-word ceiling |
| `RT_BODY_OVER_500` | WARN | `SKILL.md` body is past the ~500-word aim |
| `RT_NO_BUILD_GATE` | FAIL | a write-capable skill has no blocking build gate |
| `RT_GATE_NOT_LISTED` | FAIL | a skill declares a blocking build gate but is missing from `$WRITE_CAPABLE` (which switches the gate, marker and write checks on) |
| `RT_BROKEN_REFERENCE` | FAIL | `SKILL.md` points at a `references/`/`assets/` path that does not exist |
| `RT_ORPHAN_FILE` | WARN | a `references/`/`assets/` file, at any depth, is reachable from nothing |
| `RT_NO_HARD_RULES` | WARN | `SKILL.md` states no Hard Rules (section absent, or no bullets) |
| `RT_HARD_RULES_MISSING_WRITE` | FAIL | a write-capable skill states no Hard Rules (section absent, or no bullets) |
| `RT_AGENT_NO_HOUSE_RULES` | FAIL | an agent states no House rules (section absent, or no bullets) |
| `RT_MARKER_ABSENT` | JUDGE | a Hard Rule bullet names no verifier marker |
| `RT_MARKER_MULTIPLE` | FAIL | a Hard Rule bullet carries two or more verifier markers |
| `RT_MARKER_CASE` | FAIL | a verifier marker token is not the exact lowercase literal |
| `RT_MARKER_UNCLOSED` | FAIL | a verifier marker's opening paren is never closed |
| `RT_MARKER_EMPTY` | FAIL | a verifier marker's payload is empty |
| `RT_MARKER_STOPWORD` | FAIL | a verifier marker's payload is a stop-word placeholder |
| `RT_MARKER_TOO_SHORT` | FAIL | a verifier marker's payload is under 12 characters |
| `RT_MARKER_OVERSIZE` | FAIL | a verifier marker's payload is over the 40-word cap |
| `RT_MARKER_TARGET_MISSING` | FAIL | a `(verifier: …)` marker names a target that does not exist |
| `RT_MARKER_MISLABEL` | JUDGE | a `(no verifier: …)` marker names a target that DOES exist (backticked `tests/…` paths exempt) |
| `RT_MARKER_PROSE_ONLY` | JUDGE | a `(verifier: …)` marker names no locatable target |
| `RT_ERRORLOG_NO_STDOUT` | FAIL | an error_log call has no paired stdout channel |
| `RT_CAPTURE_OUT_DEFAULTED` | FAIL | a `.mjs` asset gives `--out` a default instead of requiring it |
| `RT_ROWTYPE_PHANTOM` | FAIL | prose in `skills/` or `agents/` cites a row type `ROW_TYPES` does not declare |
| `RT_HOUSERULES_NO_WORLD` | FAIL | a house-rules row calls the library but never says which world it can be proven in |
| `RT_MIGRATION_NO_EXCLUDE` | FAIL | a `references/migration.md` never names `novamira-sandbox` |
| `RT_HOUSERULES_ROW_PHANTOM` | FAIL | house-rules prose cites a row number the table does not contain |
| `RT_HELPER_UNROUTABLE` | WARN | an asset function no asset calls is named by no markdown either |
| `RT_WRITE_NOT_LISTED` | FAIL | code writes to WordPress but the skill is missing from `$WRITE_CAPABLE` |
| `RT_AGENT_CODE_BLOCK` | FAIL | an agent markdown file contains a code block |
| `RT_AGENT_ROUTE_MISSING` | FAIL | an agent routes to a skill that does not exist |
| `RT_AGENT_SKILL_UNMENTIONED` | WARN | an agent never mentions an existing skill |
| `RT_HOUSERULES_NO_VERDICT` | FAIL | a `house-rules.md` row has no verdict source |
| `RT_HOUSERULES_MISSING` | FAIL | `qa-review/references/house-rules.md` is missing |
| `RT_NO_OFFLINE_TESTS` | FAIL | no offline test suite under `tests/` |
| `RT_GATE_LINE_UNREGISTERED` | FAIL | a `tests/test-*.php` file is absent from the testing gate line below |
| `RT_ROWTYPE_UNDOCUMENTED` | FAIL | a `ROW_TYPES` ID is not listed in this table |
| `RT_TOKENS_HARDCODED_FONT` | FAIL | `design-tokens.md` still hardcodes an example font pairing |
| `RT_MOCKUP_GRID_AUTOFILL` | FAIL | a Plantilla maqueta grid uses `repeat(auto-fill` with no `auto-fill:` justification comment within 400 characters before it (`auto-fit` collapses empty columns; a calendar or seat map may justify `auto-fill`) |
| `RT_MOCKUP_DISCLOSURE_STATE` | FAIL | a Plantilla maqueta disclosure list is not built from `<details>`, does not open exactly its first row, or a `faq` section holds no `<details>`; one named flat FAQ (`$maqueta_known_debt` in the audit, today `corte`) reports at WARN with its reason |
| `RT_MOCKUP_FONT_NOT_EMBEDDED` | FAIL | a Plantilla maqueta names a font family it does not embed: a family first in a stack with no `@font-face`, or an `@font-face` whose `src` is a URL rather than a `data:` URI (the Artifact CSP blocks it); later stack entries need nothing |
| `RT_MOCKUP_BLEED_FIXED_BAND` | FAIL | a Plantilla maqueta bleeds to `full-end` but pins `--content-width` at a fixed length (both arms must hold); a fluid value (`clamp`, `min`, `max`, `vw`, `%`) passes; whether it tracks the viewport well is house-rules row 32 |
| `RT_MOCKUP_BLEED_NOT_MEDIA` | FAIL | a Plantilla maqueta sends something other than media to `full-start`/`full-end` (the viewport edge); whitelist, fail-closed: media names pass, a card, copy run or form control fails, `bleedband` only on a `<section>` |
| `RT_GALLERY_NO_MANIFEST` | FAIL | a Plantilla maqueta renders an image its `manifiesto-imagenes.md` (beside the folder) has no row for, or a manifest row has an empty `Slug` or no `Licencia` (the file name IS the attachment slug) |
| `RT_BUILDER_NO_TOKENS` | FAIL | a builder asset has no `es_tokens()` block a scan can be bounded by: it must **declare** `es_tokens()` (`es-builder.php`) or **inherit** it (requires `es-builder.php` and a `start of the visual layer` comment); also fails when `es_tokens()` never closes on a line of its own, the start marker is missing on an inheriting asset, or the `end of the visual layer` marker is missing or above the region start |
| `RT_BUILDER_HARDCODED_TOKEN` | FAIL | a builder asset types a visual literal inside its visual region (`file:line → literal`). Five arms: (1) a hex in any CSS length, including one torn across a concatenation; (2) a colour or timing FUNCTION (`rgb(`/`rgba(`, `hsl(`/`hsla(`, `hwb(`, `lab(`, `lch(`, `oklab(`, `oklch(`, `color-mix(`, `cubic-bezier(`, `steps(`); (3) a **named** CSS colour (148 keywords plus `transparent`, matched only where a colour ends a CSS value); (4) a bare timing keyword (`ease`, `linear`, `steps`…) after a duration; (5) any `*color` key fed a quoted string, except CSS-wide keywords and Elementor's `custom` mode. `es_t( '…' )` reads are blanked first; PHP comments and anything outside the region are not scanned; `es_rgba( es_t( … ), '0.42' )` is the correct shape for a derived veil. The glob covers `assets/` of `elementor-core`, `elementor-theme-parts` and `woocommerce` |
| `RT_FONT_NO_SERVING_PATH` | FAIL | a builder asset's `es_tokens()` block names a non-generic font family and the framework cannot find out whether anything serves it. Scoped to the token block; assets that inherit `es_tokens()` are not asked. Three causes, each named in the message: no `es_font_serving_check()` declared; nothing calls one; or no `.md` names it. It does NOT assert the files are served (they live on the WordPress site; the runtime check answers `sin-confirmar` when the front end's enqueues are invisible) |

## Workflow
```
git checkout -b <type>/<short-name>     # gotcha/side-cart-trap, feat/build-home, fix/…
# edit
git add -A
git commit -m "<type>: <summary>"       # conventional commits, no AI attribution
git push -u origin <branch>
# open a PR
```

## Versioning
Bump `metadata.version` in a skill's frontmatter when its contract changes. `divi-core`
stays < 1.0 until its path is validated end-to-end on a real site.

## Testing a change
No build step: a fresh clone passes the audit as it stands. Offline (no WordPress, no connector):

```bash
php skills/framework-audit/assets/framework-audit.php && php tests/test-container-hygiene.php && php tests/test-framework-audit.php && php tests/test-audit-signals.php && php tests/test-write-path.php && php tests/test-replay.php && php tests/test-herramientas.php && php tests/test-security.php
```

The audit enforces everything on this page that a machine can decide: frontmatter, the word
budget, broken `references/` and `assets/` pointers, a write-capable skill that lost its build
gate, `error_log()` with no stdout channel, and Hard Rules with no verifier. Its `JUDGE` rows are NOT
passes: a model has to read them (`skills/framework-audit/SKILL.md`). The test suites guard the code
that enforces the rules:

- `test-container-hygiene.php`: what the container walk decides.
- `test-audit-signals.php`: what each channel may mute and what each return value means. It runs
  itself twice, in a parent and a `--loud` child, because `ES_AUDIT_SILENT` is a constant.
- `test-write-path.php`: what the save functions report when the write did not do what was asked,
  and the build gate: `es_save_page()` writes nothing for a slug that did not pass
  `es_overwrite_preflight()`. It drives a fake WordPress that can be told to fail on demand
  (`tests/lib/fake-wp.php`, shared with `test-replay.php` so two fakes cannot drift).
- `test-replay.php`: a build reproduces itself (migration-by-replay rests on it).
- `test-herramientas.php`: the shared `skills/html-mockup/assets/herramientas/` toolbox (`color.php`,
  `scrim.php`, `huella.php`), library functions in-process and the CLI's `0`/`1`/`2` exit contract
  through child processes. It also drives `install.sh` and `install.ps1` `--clean` against a temp
  destination (`INSTALL_DEST`, HOME pointed at a temp directory): `--clean` replaces only what the repo
  ships and leaves foreign skills, agents and files alone.

The chain is a static `&&` list, not a glob. The audit FAILs on a `tests/test-*.php` whose path appears
**nowhere in this document**; it does not read the chain itself, so add the command, not a mention.

Add checks to `skills/framework-audit/assets/framework-audit.php`, never as prose in a skill.

Then install locally (`install.ps1` / `install.sh`) and test at the right depth:

- **Design-phase changes** (`web-templates`, `ux-design-system`, `html-mockup`) need **no
  WordPress at all** — that phase is builder-agnostic by design. Run it greenfield and read the
  Artifact.
- **Anything that writes** needs a throwaway site on a connector (`skills/project-context/references/connector.md`).
  Confirm the build gate blocks: a skill reached directly, without the orchestrator, must still
  refuse to write until you say yes, and `es_save_page()` must refuse a slug the preflight did not
  list. A change that lets a write through unasked is a regression.
- Then confirm `qa-review` passes with server-side evidence (fetched CSS/HTML with counted selectors,
  never a claimed visual result), including its house-rules checklist, before merging.
