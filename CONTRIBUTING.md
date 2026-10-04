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
- `SKILL.md` body stays concise: **aim ~500 words, hard ceiling ~600**. Detail → `references/`.
  Code → `assets/`. The body is what loads on activation, so every word is a tax on every run.
  Don't measure by eye — the audit counts for you and FAILs past the ceiling.
  (The aim was ~300 for a long time and **0 of 14 skills ever met it**, the lowest being 328 and
  one sitting exactly on the 600 ceiling. That is not a strict budget, it is a broken instrument:
  a threshold every single subject crosses separates nothing, so its WARN column carried no
  information and the honest reading of the report was "skip those 14 lines" — which is how a real
  row gets skipped with them. Moving it is the option this file already named, and it is the
  opposite of weakening a check: at ~500 the WARN goes back to distinguishing the five skills that
  are genuinely close to the ceiling from the nine that are not. The **600 FAIL is untouched**, and
  it is the load-bearing one. Watch `elementor-core` in particular: it sits at 598, so the next
  sentence anybody adds there breaks the gate.
  `php skills/framework-audit/assets/framework-audit.php --word-report` prints the current numbers
  — read them before quoting this paragraph, because it goes stale every time a skill is added.)
- Frontmatter needs `name`, `description` (with trigger words first), `license`,
  `metadata.author`, `metadata.version`.
- The orchestrator never gains CSS/HTML/PHP. Execution lives in skills.
- Builder-agnostic knowledge goes in `ux-design-system`; builder-specific execution in
  `elementor-core` / `divi-core`.

## 3. Every rule names its verifier, and every warning reaches a human

The "fewest containers" rule survived a whole build cycle being violated even though a correct
audit was already running. Two causes, both cheap to prevent, both easy to repeat in the next
skill. Treat these as review questions on any PR that adds a rule.

**Scope**: this section is enforced for the nine write-capable skills (`elementor-core`,
`divi-core`, `woocommerce`, `wordpress-seo`, `wordpress-performance`, `wordpress-security`, `wordpress-forms`,
`wordpress-legal`, `elementor-theme-parts`) **and for the orchestrator's
`## House rules`**, which are not softer than a skill's — they are the defaults every build
inherits, so a violation there ships on every site rather than one. An agent stating no House
rules is `RT_AGENT_NO_HOUSE_RULES`, a FAIL.

- **A rule with no verifier is a wish.** Every bullet under a write-capable skill's
  `## Hard Rules` must carry a **verifier marker**: `(verifier: <what checks it>)` or
  `(no verifier: <the admitted gap>)`, written as its own line, the LAST thing in the bullet
  (nothing may follow the closing `)`), using the exact lowercase token. An admitted gap gets
  fixed later, a silent one does not. (`wordpress-seo`'s "one H1 per page" sat unenforced this way
  until it became house-rule row 12.) The audit enforces the marker's SHAPE, never its wording —
  it cannot decide "should this rule exist", only "does this rule admit what checks it".
  - **FAIL** — the marker is missing its closing paren (`RT_MARKER_UNCLOSED`); two markers sit on
    one bullet (`RT_MARKER_MULTIPLE`); text follows the closing `)` (`RT_MARKER_TRAILING_TEXT`);
    the token's case does not match `verifier:`/`no verifier:` exactly (`RT_MARKER_CASE`); the
    payload is empty (`RT_MARKER_EMPTY`), under 12 characters (`RT_MARKER_TOO_SHORT`), a
    placeholder like `TODO`/`n/a`/`x` (`RT_MARKER_STOPWORD`), or over 40 words
    (`RT_MARKER_OVERSIZE`); a `(verifier: …)` names a row type, function, `tests/` path,
    house-rules row or execution step that does not exist (`RT_MARKER_TARGET_MISSING`).
  - **JUDGE** — a bullet carries no marker at all (`RT_MARKER_ABSENT`); a `(verifier: …)` names no
    locatable row type/function/path/row/step at all (`RT_MARKER_PROSE_ONLY`, a documented gap is still
    valid prose, so this is reported, not blocked); a `(no verifier: …)` names something that DOES
    exist (`RT_MARKER_MISLABEL` — use `(verifier: …)` instead), **except** when it cites a
    backticked `tests/…` path: naming a neighbouring test file as context for a gap is not the
    same as claiming that file checks the rule. A write-capable skill that states no Hard Rules is
    a FAIL (`RT_HARD_RULES_MISSING_WRITE`), not a JUDGE — a skill that writes to a client's live
    site and states no rules is objectively wrong. "States no rules" means the section is absent
    **or** present with no bullets under it; typing the heading and stopping is not a way through.
  - **Both polarities cost the same.** The stop-word/length/size checks above apply identically to
    `(verifier: …)` and `(no verifier: …)` — asserting a verifier that does not exist must never
    be cheaper than admitting the gap.
  - A marker is resolved against one of five shapes, tried in this order: an `es_[a-z_]+(` helper
    call, a backticked `tests/…` path, a `` `qa-review` house-rule row N``, a `step N` (optionally
    `` `<skill>` step N`` for a different skill's own numbered `## Execution Steps`), or a
    **backticked** `` `RT_…` `` row type this audit declares — its own checks are verifiers too.
    First match wins, so the row-type shape is tried LAST and must be backticked: an ID is legal
    prose, and an unquoted one tried first let a passing mention silence the check on the target
    the marker actually named. Function
    existence is checked with PHP's tokenizer, not a text search — a name mentioned only in a
    comment or a string literal does not count as defined.
  - Marker lines are excluded from the `SKILL.md` word budget below (they are provenance for the
    audit and the reviewer, not an instruction the model executes), capped at 40 words each so the
    exclusion cannot become a place to hide prose. The cap is enforced on **every** skill, not only
    the write-capable ones: a marker over the cap is simply not excluded, so its words count
    against the ceiling even where no `RT_MARKER_OVERSIZE` row is reported. A marker-shaped line
    found outside
    `## Hard Rules` is `RT_MARKER_OUTSIDE_RULES` (WARN) and stays counted.
- **A warning only in `error_log()` is a warning nobody reads.** The sandbox returns STDOUT;
  the server's PHP log is never fetched. Route every warning through `es_warn()` (or echo
  alongside `error_log()` where the helper library may not be loaded). This is not cosmetic:
  "this template will NOT appear on the front end" and "the header is being built WITHOUT its
  navigation" were both log-only, which means both could ship unnoticed.
- **Don't re-implement a check in prose.** `qa-review` calls `es_container_audit()` rather than
  describing the walk again. Two implementations of one rule drift, and the hand-rolled one loses.
- **Every file under `references/` or `assets/` must be REACHABLE, at any depth**
  (`RT_ORPHAN_FILE`). Reachability starts at `SKILL.md` and spreads only through files already
  reachable, so an index counts as a pointer but an index nobody can reach vouches for nothing.
  A file is named by its path, by its filename **with the extension**, or by its family prefix
  (`TPL-C-01` reaches `TPL-C-01-services-leadgen.md`) — never by the bare stem, which is an
  ordinary English word and would credit `name.md` from any frontmatter. A pointer at a directory
  reaches that directory's DIRECT children and no deeper — a pointer at `references/plantillas/`
  gets you to `_indice.md`, and the index is what names the folders below it — and the skill's own
  `references/`/`assets/` roots are not pointers, since that is where the walk starts. A filename
  that occurs twice in one skill must be cited by its **full path from the skill root**: with two
  `_README.md` files, both `_README.md` and `pages/_README.md` credit neither.
- **A helper nothing calls has to be named by something** (`RT_HELPER_UNROUTABLE`). A function no
  asset calls is an ENTRY POINT: the only thing left that can invoke it is an instruction file
  telling a model to. So one that no markdown names at all is unreachable, and the two ways that
  happens are worth telling apart — dead weight, or a helper that was written, tested and never
  wired. The second is this repo's own recurring bug: `es_set_front_page()` was measured against a
  live site, documented in a house rule and called from nothing, so a build could finish green
  with the client's front page untouched. The list is DERIVED, never enumerated; a roster of "the
  helpers that matter" goes stale the first time somebody adds one, which is the thing being
  checked. `*.example.php` defines nothing here: an example is a file you copy and rewrite, so its
  build function is meant to be replaced rather than called.
- **Prefer a helper over a rule.** `es_section()` hardcoded `flex_direction:column`, so building
  two columns REQUIRED the extra container the rule forbade. When the library makes the wrong
  shape the easy one, no amount of documentation wins. Fix the library first (`es_split()`,
  `es_wide()`, `es_photo()`), then write the rule.

### Row types

Every row `framework-audit.php` can print is declared in its `ROW_TYPES` registry (run
`… --emit-row-types` to list them from the source of truth) and mirrored here. An ID missing from
this table is itself a FAIL (`RT_ROWTYPE_UNDOCUMENTED`), so this table cannot silently drift from
the code — adding a check without adding its row here fails the audit on itself.

| Row type | Level | What it means |
|---|---|---|
| `RT_NO_SKILL_MD` | FAIL | a skill directory has no `SKILL.md` |
| `RT_NO_FRONTMATTER` | FAIL | `SKILL.md` has no YAML frontmatter block |
| `RT_FRONTMATTER_MISSING_KEY` | FAIL | frontmatter is missing a required key |
| `RT_NAME_MISMATCH` | FAIL | frontmatter `name:` does not match its directory |
| `RT_NO_TRIGGER` | FAIL | description carries no `Trigger:` words |
| `RT_BODY_OVER_600` | FAIL | `SKILL.md` body is past the ~600-word ceiling |
| `RT_BODY_OVER_500` | WARN | `SKILL.md` body is past the ~500-word aim |
| `RT_NO_BUILD_GATE` | FAIL | a write-capable skill has no blocking build gate |
| `RT_GATE_NOT_LISTED` | FAIL | a skill declares a blocking build gate but is missing from `$WRITE_CAPABLE` — that list is what makes the gate, marker and write checks apply, so being off it disables all three at once |
| `RT_BROKEN_REFERENCE` | FAIL | `SKILL.md` points at a `references/`/`assets/` path that does not exist |
| `RT_ORPHAN_FILE` | WARN | a `references/`/`assets/` file, at any depth, is reachable from nothing |
| `RT_NO_HARD_RULES` | WARN | `SKILL.md` states no Hard Rules — section absent, or present with no bullets |
| `RT_HARD_RULES_MISSING_WRITE` | FAIL | a write-capable skill states no Hard Rules — section absent, or present with no bullets |
| `RT_AGENT_NO_HOUSE_RULES` | FAIL | an agent states no House rules — section absent, or present with no bullets |
| `RT_MARKER_ABSENT` | JUDGE | a Hard Rule bullet names no verifier marker |
| `RT_MARKER_MULTIPLE` | FAIL | a Hard Rule bullet carries two or more verifier markers |
| `RT_MARKER_CASE` | FAIL | a verifier marker token is not the exact lowercase literal |
| `RT_MARKER_UNCLOSED` | FAIL | a verifier marker's opening paren is never closed |
| `RT_MARKER_TRAILING_TEXT` | FAIL | text follows a verifier marker's closing paren |
| `RT_MARKER_EMPTY` | FAIL | a verifier marker's payload is empty |
| `RT_MARKER_STOPWORD` | FAIL | a verifier marker's payload is a stop-word placeholder |
| `RT_MARKER_TOO_SHORT` | FAIL | a verifier marker's payload is under 12 characters |
| `RT_MARKER_OVERSIZE` | FAIL | a verifier marker's payload is over the 40-word cap |
| `RT_MARKER_TARGET_MISSING` | FAIL | a `(verifier: …)` marker names a target that does not exist |
| `RT_MARKER_MISLABEL` | JUDGE | a `(no verifier: …)` marker names a target that DOES exist (backticked `tests/…` paths exempt) |
| `RT_MARKER_PROSE_ONLY` | JUDGE | a `(verifier: …)` marker names no locatable target |
| `RT_MARKER_OUTSIDE_RULES` | WARN | a verifier-marker-shaped line sits outside `## Hard Rules` |
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
| `RT_MOCKUP_GRID_AUTOFILL` | FAIL | a Plantilla maqueta (`plantillas/*/maqueta/index.html`) grid uses `repeat(auto-fill` with no `auto-fill:` justification in a comment within 400 characters before it. `auto-fill` creates every column that FITS the container whether or not an element exists for it, so a reserved empty column reads as a missing card; `auto-fit` collapses them. Not a ban: a calendar or a seat map wants the empty track |
| `RT_MOCKUP_DISCLOSURE_STATE` | FAIL | a Plantilla maqueta disclosure list is not built from `<details>`, or does not open exactly its first row. All closed is a wall of headings, all open is a long page pretending to be a short one, and a `faq` section holding no `<details>` at all is the third cause. A single `<details>` is not a list. One named flat FAQ (`$maqueta_known_debt` in the audit, today `corte`) reports at WARN with its reason attached instead of FAIL |
| `RT_MOCKUP_FONT_NOT_EMBEDDED` | FAIL | a Plantilla maqueta names a font family it does not embed — a declared typeface nobody serves, so every review judges the fallback stack. Two arms: a family first in a stack with no `@font-face`, and an `@font-face` whose `src` is a URL rather than a `data:` URI (the Artifact CSP blocks it). First in the stack is the design; later entries are fallbacks and need nothing |
| `RT_MOCKUP_BLEED_FIXED_BAND` | FAIL | a Plantilla maqueta bleeds to `full-end` but pins `--content-width` at a fixed length. Two arms, neither fires alone: a fixed band is correct in a centred layout, but beside a `full-end` bleed the outer `1fr` gutter is the only track that can absorb a wider screen and nothing bounds it. A fluid value (`clamp`, `min`, `max`, `vw`, `%`) passes; whether it tracks the viewport well is house-rules row 32 |
| `RT_MOCKUP_BLEED_NOT_MEDIA` | FAIL | a Plantilla maqueta sends something other than media to `full-start`/`full-end`, which IS the viewport edge. A whitelist, fail-closed on the unknown: media names pass; a card, a run of copy or a form control at the glass fails, and `bleedband` is admitted only on a `<section>` |
| `RT_GALLERY_NO_MANIFEST` | FAIL | a Plantilla maqueta renders an image its `manifiesto-imagenes.md` (beside the folder) carries no row for, or that manifest has a row with an empty `Slug` or no `Licencia`. The file name IS the WordPress attachment slug, and this repository is public, so an image whose terms are recorded nowhere is a right nobody checked we had to give. The id keeps the name of the gallery it first guarded |
| `RT_BUILDER_NO_TOKENS` | FAIL | a builder asset has no `es_tokens()` block a scan can be bounded by. Two shapes are accepted: it **declares** `es_tokens()` (`es-builder.php`), or it **inherits** it — requires `es-builder.php` and marks its region with a `start of the visual layer` comment, which is what the three sibling assets do, since redeclaring the function would fatal. It fails when neither holds, when `es_tokens()` never closes on a line of its own, when the start marker is missing on an inheriting asset, or when the `end of the visual layer` marker is missing or sits above the region start. An unbounded region is an unscannable one |
| `RT_BUILDER_HARDCODED_TOKEN` | FAIL | a builder asset types a visual literal inside its visual region, and the message names each one as `file:line → literal`. **Five arms.** (1) a hex in any CSS length, including one torn across a concatenation (`'#0F' . 'A968'`); (2) any colour or timing FUNCTION — `rgb(`/`rgba(`, `hsl(`/`hsla(`, `hwb(`, `lab(`, `lch(`, `oklab(`, `oklch(`, `color-mix(`, `cubic-bezier(`, `steps(`; (3) a **named** CSS colour — the full 148-keyword list plus `transparent`, matched only where a colour ends a CSS value, so Spanish prose containing `tan` or `snow` is not a finding; (4) a bare timing keyword (`ease`, `linear`, `steps`…) anchored on the duration in front of it, which is how `transition:…  .28s ease` gets caught while `es_t('ease')` does not; (5) format-blind — any `*color` key fed a quoted string, whatever the syntax, excepting the CSS-wide keywords and Elementor's `custom` control mode. `es_t( '…' )` reads are blanked before any arm runs, so a token whose NAME is a CSS keyword (`ease`, `transparent`) is never charged for being named. PHP comments are not scanned, nor is anything outside the region: a value that cannot reach the emitted data is not a value the axes have to move. `es_rgba( es_t( … ), '0.42' )` is the correct shape for a derived veil and is **not** a finding — the pattern requires no identifier character before `rgba(`. The glob covers the `assets/` of every skill that emits Elementor data — `elementor-core`, `elementor-theme-parts` and `woocommerce`, i.e. all four builder assets. It is a glob per skill rather than four filenames so a new asset dropped into any of those directories is caught, and an explicit skill list rather than one wildcard so it does not scan `framework-audit.php` itself |
| `RT_FONT_NO_SERVING_PATH` | FAIL | a builder asset's `es_tokens()` block names a font family while the framework is not equipped to find out whether anything serves it. Scoped to the token block itself — the one region `RT_BUILDER_HARDCODED_TOKEN` deliberately does not scan — so a declaration can never produce two rows, and sibling assets that inherit `es_tokens()` declare no family and are not asked. Generic and web-safe faces are skipped. **Three causes, each named in the message:** the asset declares no `es_font_serving_check()`; nothing in it calls one, so the check is a function no build reaches; or no `.md` in the tree names it, leaving an operator who sees the warning with nowhere to go. **What it does NOT assert, and the message says so:** whether the font files are actually served. They live on a WordPress site, not in this repo. Only the runtime check can ask the site, and even it answers `sin-confirmar` rather than clean when a build request cannot see the front end's enqueues |

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
There is no build step: a fresh clone passes the audit as it stands. Offline — no WordPress, no
connector:

```bash
php skills/framework-audit/assets/framework-audit.php && php tests/test-container-hygiene.php && php tests/test-framework-audit.php && php tests/test-audit-signals.php && php tests/test-write-path.php && php tests/test-replay.php && php tests/test-herramientas.php && php tests/test-security.php
```

The audit enforces everything on this page that a machine can decide: frontmatter, the word
budget, broken `references/` and `assets/` pointers, a write-capable skill that lost its build
gate, `error_log()` with no stdout channel, and Hard Rules in write-capable skills that name no
verifier. Its `JUDGE` rows are NOT passes — a model has to read them; that half is
`skills/framework-audit/SKILL.md`. The test suites guard the container audit itself, because the
code that enforces the rules needs something enforcing it too: `test-container-hygiene.php` for
what the walk decides, `test-audit-signals.php` for what each channel may mute and what each
return value means, and `test-write-path.php` for what the save functions report when the write
did not do what was asked. `test-audit-signals.php` runs itself twice, in a parent and a `--loud`
child, because `ES_AUDIT_SILENT` is a constant and a single process can only ever observe one of
the two worlds. `test-write-path.php` drives a WordPress that can be told to fail on demand, which
is the only way to reach branches a real site reaches only when something has already gone wrong.
`test-replay.php` asserts the property migration-by-replay rests on: that a build reproduces
itself. It shares `test-write-path.php`'s fake WordPress through `tests/lib/fake-wp.php` rather
than copying it, because two fake WordPresses drift and the day they differ is the day one suite
proves determinism the other one has already lost. `test-herramientas.php` guards the shared
`skills/html-mockup/assets/herramientas/` toolbox (`color.php`, `scrim.php`, `huella.php`):
both halves of each dual-mode file, the library functions in-process and the CLI's own `0`/`1`/`2`
exit contract through real child processes. It also drives `install.sh` and `install.ps1`
`--clean` against a temp destination (`INSTALL_DEST`, with HOME pointed at a temp directory too, so
the real `~/.claude` is never a target): that `--clean` replaces only what the repo ships and leaves
foreign skills, agents and files alone.

This chain is a static `&&` list, not a glob, so a new test file that nobody adds here would
silently never run. There is a check, and it is worth knowing exactly what it proves: the audit
FAILs on a test file whose path appears **nowhere in this document**. It does not read the chain
itself, so naming its path in the prose above satisfies it. Add the command, not a mention.

Add checks to `skills/framework-audit/assets/framework-audit.php`, never as prose in a skill.

Then install locally (`install.ps1` / `install.sh`) and test at the right depth:

- **Design-phase changes** (`web-templates`, `ux-design-system`, `html-mockup`) need **no
  WordPress at all** — that phase is builder-agnostic by design. Run it greenfield and read the
  Artifact.
- **Anything that writes** needs a throwaway NovaMira site. Confirm the build gate actually
  blocks: a skill reached directly, without the orchestrator, must still refuse to write until
  you say yes. A change that lets a write through unasked is a regression, however good it looks.
- Then confirm `qa-review` passes with server-side evidence, including its house-rules checklist,
  before merging. Server-side evidence means fetched CSS/HTML with counted selectors — never a
  claimed visual result.
