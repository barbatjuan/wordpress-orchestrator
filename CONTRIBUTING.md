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

**Scope**: this section is enforced for the eight write-capable skills (`elementor-core`,
`divi-core`, `woocommerce`, `wordpress-seo`, `wordpress-performance`, `wordpress-forms`,
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
  reaches that directory's DIRECT children and no deeper — `references/templates/pages/` gets you
  to the README, and the README is what names the archetypes below it — and the skill's own
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
| `RT_QA_NO_AXIS_CHECK` | FAIL | `house-rules.md` never names an axis declaration the mockup gate demands, and the message lists each missing one. It reads the SAME `axis_declarations()` list `RT_MOCKUP_NO_AXES` reads, plus the `LP-*` composition blueprint both match separately — one list, two ends of one contract, so a sixth axis property cannot land on the mockup side with the QA side left behind. **Naming, not automating**: an axis `qa-review` can only hand to the user's eyes still has to be named as such. The failure it closes is a silent omission — `html-mockup/SKILL.md` told the operator its approved output was "the visual contract `qa-review` checks the build against" while `qa-review` contained zero mentions of the mockup, the contract or any axis, and the nearest row compared four hex values |
| `RT_NO_OFFLINE_TESTS` | FAIL | no offline test suite under `tests/` |
| `RT_GATE_LINE_UNREGISTERED` | FAIL | a `tests/test-*.php` file is absent from the testing gate line below |
| `RT_ROWTYPE_UNDOCUMENTED` | FAIL | a `ROW_TYPES` ID is not listed in this table |
| `RT_PERS_CATALOG_MISSING` | FAIL | `ux-design-system/references/style-catalog/` has no `STY-*.md` entries (style-catalog PR 4c retired the single `design-personalities.md` file this row used to check for) |
| `RT_PERS_MISSING_FIELD` | FAIL | a `STY-*.md` entry is missing a required field |
| `RT_PERS_DUPLICATE_ID` | FAIL | two style-catalog files declare the same ID — the later file (by sort order) is excluded rather than silently replacing the first, which would switch `RT_STYLE_TOO_SIMILAR` off for the real anchor (style-catalog PR 4c: "duplicate" now means two FILES agreeing on a heading, not two `### `ID`` blocks inside one file). **`BSP-*.md` is on the same namespace**, because the two globs now feed one anchor lookup (see `RT_MOCKUP_AXES_MISMATCH`): a bespoke declaration headed with a catalog id would replace that entry in the lookup *after* the pairwise comparison has already run over the real one, and nothing downstream would notice. Same row, same "report, never merge", first claimant authoritative |
| `RT_STYLE_TOO_SIMILAR` | FAIL | two catalog entries share more than two of the eight axis positions (style-catalog PR 2b renamed this from `RT_PERS_TOO_SIMILAR` and widened it from 5 to 8 axes — the >1-of-5 bar was 80% distinct, the new >2-of-8 bar is 75%: a deliberate relaxation in PROPORTION as the axis count grows, not a tightening, while the absolute number of axes that must differ rises from 4 to 6; style-catalog PR 4c repointed the parser from `design-personalities.md` to `STY-*.md`, no threshold change) |
| `RT_PERS_BAD_AXIS` | FAIL | a style names an axis position no axis defines. **Also read on `BSP-*.md`**, whose axes line is a catalog entry's line parsed by the same `pers_axes()`: `_bespoke-route.md` says the route buys freedom to *combine* any position on any axis, never to invent one, and nothing read that while `RT_BESPOKE_UNDECLARED` asked only whether each axis was ANSWERED. It has to be read now that those answers become an anchor (see `RT_MOCKUP_AXES_MISMATCH`), because an unvalidated position would leave the mockup gate comparing a real `:root` against a typo and calling the pair coherent |
| `RT_TPL_TOO_SIMILAR` | FAIL | two archetypes of one family share more than half of their combined section inventory. `RT_PERS_TOO_SIMILAR`'s twin, one layer up: that row keeps two personality anchors from shipping as the same site with a different accent, this one keeps two `TPL-*` of a family from shipping as the same architecture with a different name — the claim `web-templates/SKILL.md` makes in the words "many architectures". The inventory is the `COMP-*` ids inside the fenced block under `## 2. Wireframe`, deduped; § 3's prose and § 4's `Fijos:` line are commentary and are not counted, or a doc could talk its way out of its own architecture. **The half is measured, not chosen:** across the corporate family's ten pairs the shared fraction bottoms out at exactly one half — `TPL-C-01`/`TPL-C-03` and `TPL-C-02`/`TPL-C-03` each share 6 of 12 — so the bar is the closest two architectures nobody disputes are different have ever stood. Compared as `2·|A∩B| > |A∪B|`, in integers, and the row prints both counts and the shared ids. Within a family only, and **a family is the set one choice picks from**: the two home directories, plus each `pages/<role>/` subfolder on its own. `recommender.md` § 0 bifurcates on site type before any archetype is offered, so a cross-family resemblance costs no one a choice; and nobody picks between an about page and a product page — a site gets both — so comparing across page roles would measure a distance no client can spend. **The inner pages were outside this row for its whole life, and that is what it cost:** measured the first time it was pointed at them, `TPL-PDP-01`/`TPL-PDP-02` shared SEVEN of their eight sections and `TPL-SHOP-01`/`TPL-SHOP-02` six of seven — not similar, the same page with a different photo size. The homes had a gate and held their distance; the pages had none and converged, which is the mechanical reason every site in the catalogue shipped the same product page. Both duplicates were retired into the toggles they always were (`TGL-PDP-LAYOUT`, `TGL-SHOP-FILTERS`) |
| `RT_TPL_NO_WIREFRAME` | FAIL | a `TPL-*.md` wireframe is not fully readable. Its own row, not a silent skip, because an unreadable archetype leaves every pair it belongs to and `RT_TPL_TOO_SIMILAR` goes quiet exactly where the docs got worse — the shape `RT_PERS_DUPLICATE_ID` closes one layer up. **Three exclusive causes, each its own message,** ordered widest-first so a three-line block yields one finding and not four: no fenced block under `## 2. Wireframe`; a block naming no `COMP-*` anywhere; or — the dangerous one, a row at a time — a non-empty row carrying no id, quoted verbatim so the fix is obvious. That third arm is not decoration: it was added because mutation proved the other two were not enough. Turning `TPL-E-01`'s `COMP-GALLERY (lookbook)` back into the bare prose row it used to be left the whole tree at **0 FAIL**, since the block still held nine other ids and the pair it pulled together landed exactly *on* the half rather than past it. A prose row is how `TPL-E-01` and `TPL-E-03` measured 89% identical while differing, to a reader, by a lookbook against a brand story. Extended to `pages/<role>/` with the row above, it found forty-five unnamed rows across thirteen inner-page archetypes in one run — including `TPL-CART-01`, whose § 2 held TWO fenced blocks where the parser reads one, so the archetype declared the drawer sketch (no ids at all) while its real page sat invisible in the second block |
| `RT_TPL_UNROUTABLE` | FAIL | a `TPL-*.md` exists that `recommender.md` never names, or that its family `_README.md` never lists, or a family directory that holds archetypes and has no `_README.md` at all. `RT_TPL_TOO_SIMILAR` proves two archetypes are DIFFERENT; this one proves a client can ever be OFFERED one, which is a separate property and the one that actually broke. `TPL-C-06..12` shipped as seven finished archetypes — each with its own brand block, embedded typefaces and gallery strip — while `recommender.md` named none of them, not once; every documented route into the catalog reads that file, so seven templates were built and none could be recommended, at 0 FAIL the whole time. Same class as `RT_CATALOG_UNMENTIONED` one layer up, applied to the layer below: an unreachable catalog entry is indistinguishable from one that does not exist, except that somebody paid to build it. **Two ways in, because they fail apart:** `recommender.md` is the runtime path the agent reads to route a live client, the folder `_README.md` is the reading path a human compares before touching anything — missing from the first cannot be chosen, missing from the second gets rebuilt by the next person who never knew it was there. Matched on the bare id (`TPL-C-06`), never the filename, so a rename cannot switch the check off
| `RT_TOKENS_HARDCODED_FONT` | FAIL | `design-tokens.md` still hardcodes an example font pairing |
| `RT_CATALOG_UNMENTIONED` | FAIL | `ux-design-system/SKILL.md` never mentions `style-catalog/` |
| `RT_UXDS_NO_AXIS_STEP` | FAIL | `ux-design-system/SKILL.md` has no axis-resolving dialogue step |
| `RT_AXIS_VALUE_MISSING` | FAIL | an axis position's own table row in `design-system.md` carries no token-shaped value cell — a backticked name with an empty or prose cell beside it is a name, not a value |
| `RT_AXIS_BLUEPRINT_MISSING` | FAIL | an axis position is valued as a backticked blueprint id that its axis's definition file defines no heading for — `layout-patterns.md` for `composition`/`chassis`, `design-system.md` itself for `accent`/`ornament` (style-catalog PR 2b generalized this from a single hardcoded file) |
| `RT_PROOF_NOT_DISTINCT` | FAIL | the two proof mockups' `:root` blocks differ on fewer than four of the five axes, or one of the two files is missing — the message names which axes match |
| `RT_PROOF_COPY_DIFFERS` | FAIL | the two proof mockups do not render the same human-visible strings as multisets — the message names one string and how many times each file renders it. The other half of *same content → unmistakably different*; without it, editing one headline contaminates the experiment with every row green |
| `RT_MOCKUP_NO_AXES` | FAIL | an `html-mockup` asset declares no perceptual-axis tokens — every `.html` under `skills/html-mockup/assets/`, **at any depth**, must declare `--type-ratio`, `--display-lh`, `--fs-h1-max`, `--sp-scale`, `--elev-rest` and a `/* composition: LP-* */` marker in its `:root`, and the message names each one missing and the path relative to `assets/`. The walk recurses because the glob that did not left `assets/gallery/` — a whole page of anchors — outside the row that exists to catch exactly that third asset. The proof files gate the demonstration; this gates the files a real project is copied from. Assets whose basename starts with `_` are content, not pages, and are skipped |
| `RT_MOCKUP_GRID_AUTOFILL` | FAIL | a mockup grid uses `repeat(auto-fill` with no justification comment beside it. **The two spellings look identical and are not**: `auto-fill` creates every column that FITS the container whether or not an element exists for it, `auto-fit` collapses the empty ones so the elements that exist share the width. Three team portraits in a canvas that fits four render, under `auto-fill`, as three cards squeezed left with a quarter of the section empty — and an empty reserved column is indistinguishable from a missing card, which is what the reader circled in red before asking why it keeps happening. **Same shape as every other misalignment in this framework**: a container sizing itself against the space available instead of against its own siblings — chips that wrap, an `auto` column in an independent grid, an item with `min-width:auto` eating the gutter. Reasons in full: `ux-design-system/references/layout-patterns.md` § "Grid track counts". **Not a ban, a justification requirement**, because `auto-fill` is genuinely right where the empty track IS the point — a calendar month, a seat map, a contact sheet whose grid must not reflow as items are filtered. The marker `auto-fill:` in a comment within 400 characters before the declaration clears the row, which is the same shape as a SKILL.md verifier marker: the reason travels with the code, so the next reader never has to guess whether it was a decision or a default |
| `RT_MOCKUP_DISCLOSURE_STATE` | FAIL | a disclosure list is not built from `<details>`, or does not open exactly its first row. **One rule for every one of them** — `COMP-FAQ`, `COMP-ACCORDION`, any spec block of the same shape: all closed reads as a wall of headings the reader will not open, all open is a long page pretending to be a short one, and one open row shows the SHAPE of an answer so the rest can be judged without clicking. Reasons in full: `ux-design-system/references/layout-patterns.md` § "Disclosure lists". **Written after the drift, not before it**: `mockup-guide.md` had specified `<details>/<summary>` for `COMP-FAQ` from the start and the `.acc .qas` comment had warned in as many words that a second implementation is how two identical things drift — and when somebody finally looked there were FOUR emitters and THREE behaviours, one of which rendered `<div class="qa"><h3>` with every answer permanently on screen, with every gate green because none of them read emitted markup. **A run of siblings, not a list of wrapper classes**, and the first draft got that wrong: keying on `faqlist`/`qas` — the two classes the GALLERY happens to use — left `corporate-mockup.html` unchecked, which held nine `<details>` under a wrapper called `faq` and was the file a real project was copied from (style-catalog PR 1f retired it; the generated `chassis/*.html` are what a real project starts from now); a rule that inspects the tool and not the product is worse than none. So two or more `<details>` separated by nothing but whitespace are one list, whatever wraps them, and a SINGLE `<details>` is not a list and is not judged — that is `.handoff`, which correctly starts closed. **Three causes, three messages:** a FAQ section with no `<details>` at all; a list that opens none; a list that opens more than one |
| `RT_MOCKUP_ANCHOR_UNDECLARED` | FAIL | a starting or proof mockup does not say which anchor it is pointed at. Four files must carry `Anchor: PERS-*` in their `:root` — the generated `chassis/corporate.html` / `chassis/ecommerce.html` (style-catalog tasks.md 1f.2 retired the two hand-maintained originals, `corporate-mockup.html` / `ecommerce-mockup.html`, that these replaced) and the two `proof-*`. **A hardcoded list and not the glob**, for the reason the proof rows are hardcoded: a missing declaration is the file going QUIET, and a glob alone would make deleting the marker a way to switch `RT_MOCKUP_AXES_MISMATCH` off — which is precisely how `PERS-VITRINE` shipped outside `$PERS_IDS`, correct in every check that read what the file happened to contain. `assets/gallery/index.html` renders every anchor at once and belongs to none, so it declares nothing and is not on the list |
| `RT_MOCKUP_AXES_MISMATCH` | FAIL | a mockup's five axis labels are not the ones its declared anchor holds, and the message names each disagreeing axis with both positions. `RT_MOCKUP_NO_AXES` above asks whether the axes are DECLARED; this asks whether they are declared COHERENTLY. **The defect it exists for is the half-done re-point**, which is the likely one: the block is five token lines plus a `composition` marker, so pointing a project at a new anchor means editing six things, and every row that existed before this one stayed green on five out of six — the sixth ships as a site that is neither anchor. The block's own comment has read *"a hand-picked value is how every client site ends up looking the same"* since the axes landed and nothing read it, which is the same shape as the `var()` lesson in `mockup-guide.md`: **writing the explanation is not installing the gate**. **Both the label and the value, since style-catalog PR 1e:** a `scale: contained` marker beside a hand-typed `--fs-h1-max: 53` used to pass, because the label agreed with the anchor and nothing read the number — `axis_token_values()` now reads `design-system.md`'s own token table for the position the label claims and compares it against the same property read back out of `:root`; either disagreement FAILs, naming the token and both values. This still catches the axis that was never re-pointed AT ALL — the one that survives a re-point because nobody diffs a comment. `composition` is read as its layout pattern (`LP-STRICT-GRID` IS `strict-grid`), being the one axis with no custom property to carry it; the FIRST marker per axis wins, since `proof-editorial-mockup.html` repeats its `elevation` marker further down with prose after it. Checked on the four files above **plus any other asset the walk finds that declares an anchor**. **A valid anchor is a `STY-*.md` OR a `BSP-*.md`**: the lookup was built from the catalog glob alone, so `Anchor: BSP-X` FAILed here as an id the catalog does not define, and `ROUTE-BESPOKE`'s only remaining moves were to name a `STY-*` it does not hold — the exact defect this row exists to catch — or to omit the marker, which is `RT_MOCKUP_ANCHOR_UNDECLARED` on any asset that requires one. An escape hatch whose only conforming move is to break one of two rules is not one. The bespoke entry is compared exactly as a catalog entry is, label and token value alike, since the eight positions are the same eight read by the same parser; it stays out of `RT_STYLE_TOO_SIMILAR`, whose membership is its own `STY-*`-only list rather than the keys of this lookup, so the exclusion `_bespoke-route.md` promises is structural and not an accident of which block runs first |
| `RT_MOCKUP_FONT_NOT_EMBEDDED` | FAIL | an `html-mockup` asset names a font family it does not embed — a declared typeface nobody serves. Every mockup here named real families (Fraunces, Inter Tight, Archivo Expanded, Instrument Serif, DM Sans, Source Sans 3) and embedded none, and none of the six is installed on an ordinary machine, so `'Fraunces', Georgia, serif` rendered **Georgia** and every visual judgement made about this framework was a judgement of the fallback stack — with every axis row green throughout, because a token chain resolves perfectly into a face the machine does not have. **The family asked for is the FIRST in the stack**, which is the stack's own semantics (`font-family: A, B, C` = "A, else B"), so quoted fallbacks like `'Segoe UI'` and `'Arial Black'` need nothing and no allowlist is maintained. Read from `--font*` tokens and from literal `font-family` declarations, whole-file rather than `:root`-only — unlike an axis token, a font stack in a media query is a real request. **Two causes:** a family with no `@font-face` at all; or an `@font-face` whose `src` is a URL rather than a `data:` URI, which satisfies the first arm while being exactly what the Artifact CSP blocks. The mockup-side twin of `es_font_serving_check()`, which asks the same question of a WordPress build — different surfaces, so neither would have caught the other |
| `RT_MOCKUP_BLEED_FIXED_BAND` | FAIL | an `html-mockup` asset bleeds to `full-end` but pins `--content-width` at a fixed length. **Two arms, and neither fires alone**: a fixed band is the correct, ordinary shape under `LP-CENTERED` and `LP-STRICT-GRID`, which centre it and never bleed. It becomes a defect only beside a viewport-edge bleed, where the named-line grid `[full-start] minmax(pad,1fr) … minmax(pad,1fr) [full-end]` must sum to the SCREEN — so capping the twelve columns leaves the outer `1fr` gutter as the only track that can absorb a wider viewport, and a `1fr` has no ceiling. Measured on `assets/gallery/index.html` at `1140px`: left gutter 150.1 / 390.1 / **710.1px** at 1440 / 1920 / 2560, 10.4% of the screen growing to 27.7%, with the bleeding edge always at the glass — the composition drifting right by half the gutter and a dead left quarter at 2560. Any of `clamp(`, `min(`, `max(`, `vw`, `vmin` or `%` in the value clears the row; whether the band tracks the viewport *well* is `house-rules.md` row 32, which needs a browser and eight widths and cannot run here. This row caught nothing the day it was written — all three bleeding assets had just been fixed — and exists so the next fixed literal cannot land |
| `RT_MOCKUP_BLEED_NOT_MEDIA` | FAIL | an `html-mockup` asset sends something other than media to `full-start`/`full-end`, which IS the layout viewport edge. **The companion to `RT_MOCKUP_BLEED_FIXED_BAND`, and it looks at the other half of the same picture**: that row reads the relationship between the band and a bleed and is blind to WHICH element bleeds, so it was green on every build described here. **What shipped through the blind spot**, on `assets/gallery/index.html`, in the order the reader found it: card rows (`.services .items`, `.cases .items`, `.carousel .items`) at `c 1 / full-end`, where the last card’s surface ended on the glass while a `padding-right` inset its copy 32px — one object half bled and half not, measured at 2000 as frame right 2000.0 against body ink 1968.0, and called cut off across two rounds of fixes; `.hero .head` at `c 1 / full-end`, a copy block claiming a gutter its own ink never entered (h1 ink 227px short at 2000, 745px short at 2560); and `.band .formwrap` at `c 6 / full-end` — **a form**, every field and the submit button ending at exactly x=2560.0 on a 2560 viewport with a 1453.3px name input, which is not a styling defect but a control the reader cannot use. `documentElement.scrollWidth === clientWidth` throughout all three, so no overflow gate could see any of it, which is why this row is not an overflow gate. **A whitelist, not a measurement**: the subject of the `grid-column` declaration — its LAST simple selector, because `.hero .media` is a bleed and `.media .btn` would not be — must be media (`media`, `frame`, `figure`, `img`, `picture`, `video`, `slide(s)`, `hero-slides`, `shot`, `ph`) or an empty decorative panel (`slab`). Every name on that list is justified at the declaration; **an unrecognised subject is reported, not skipped**, because a whitelist that silently passes what it has never seen is the same blind spot one level up. Static and offline by choice: the geometry is `house-rules.md` row 32’s and is the better check, but it needs a browser and a host, whereas the subject of a declaration is decidable from the text. **It caught three files the day it was written** — the gallery, plus `.hero h1`, `.jobs .w2` and `.test .attrib` in `proof-direct-mockup.html`, and a `.bleed` hook in `proof-editorial-mockup.html` that named the ACT rather than the content and has been renamed `.media` |
| `RT_GALLERY_NOT_DISTINCT` | FAIL | two strips of a gallery asset are not two cards. A **gallery asset** is any `html-mockup` asset under a `gallery/` directory **or** any that renders `<section class="strip">` — two arms, because a rename would silence a path-only test and deleting the two data attributes would silence a content-only one, and both edits turn the row green by removing its subject. **Five causes, each its own message:** the asset renders no strips at all; a strip declares no `data-tpl` and/or no `data-pers`; the same `TPL × PERS` pair is rendered twice (which is also the "two strips sharing an anchor must declare different archetypes" rule, since sharing both is the same event); a strip names an anchor nothing declares a `[data-anchor="…"]` block for; or two strips of the **same archetype** sit at anchors differing on fewer than **4 of the 5** axes — the message names each matching axis with the value they share, exactly as `RT_PROOF_NOT_DISTINCT` does, and the two share one `axis_matches()` list so a sixth axis lands on both at once. The ≥4 bar is `RT_PERS_TOO_SIMILAR`'s, for the reason the style catalog gives: two anchors agreeing on two or more axes are the same site with a different accent colour. Anchor blocks are read with `preg_match_all`, so a CSS comment quoting a selector cannot shadow the real declaration — the three-byte-block trap `:root` already paid for once |
| `RT_GALLERY_NO_MANIFEST` | FAIL | a gallery asset renders an image no manifest row accounts for. The manifest is the `_gallery-images.md` **beside the asset**; its image table is the first whose header carries a cell reading exactly `Slug`, and column positions are read from that header rather than counted from the left. **Four causes:** images are rendered and no manifest sits beside the asset; the manifest carries no `Slug`-column table at all; a rendered `data-img` slug has no row; a row carries an empty `Slug` cell, or no `Licence` cell (the message says so explicitly when the column itself is absent). `es_photo()` resolves a WordPress **attachment slug** — not a URL, not a `data:` URI — so an image with no slug is a promise the native build cannot keep, and a licence recorded only in prose becomes false in silence the first time an image arrives from somewhere else |
| `RT_GALLERY_ONE_SHOOT` | FAIL | one photo shoot supplies more of a gallery's image set than the manifest's own register table claims distinct looks. The **shoot** of a row is its Freepik id with the last three digits dropped, and the check RE-DERIVES it from the `Freepik` cell instead of trusting the `Shoot` cell — a hand-typed shoot label is prose, and a concentration bound over prose is one you turn green by retyping a name. The bucket is 1,000 wide because Freepik issues ids in upload order: measured on this set, the widest span inside one batch is 497 and the narrowest gap between two batches is 17,583. **Four causes:** the manifest declares no `Registers` table with rows, so the cap has no divisor; it carries no `Shoot` column; it carries a `Shoot` column and no `Freepik` column to derive it from; or one shoot exceeds `ceil(N / R)` for N image rows and R registers — the bound comes from the file's two own numbers rather than from a threshold chosen here, so adding a register loosens it. **Concentration and not adjacency, measured:** the visible defect is two same-shoot frames side by side, but this audit reads generated HTML where the only available order is document order, and 2 of the 5 consecutive `data-img` pairs in the gallery's own corporate strip cross a `<section>` boundary — a 40% false-adjacency rate. Scoping to the real grid container would hardcode one builder's `class="items"` into the auditor and a rename would silently empty the check. Concentration also survives a reshuffle, which an adjacency rule would not. **What it cannot see, and says so in its own message:** two photographs of the same subject from two genuinely different shoots pass with distinct keys and still read as one picture; a batch straddling a multiple of 1,000 splits and looks more diverse than it is. Only a contact sheet catches either |
| `RT_GALLERY_NOT_BUILT` | FAIL | `assets/gallery/_build-gallery.php` is present and its `index.html` output is not — the gallery is generated and untracked, so a fresh clone has none until it is built. Gated on the generator's own presence so it never fires in a fixture root that writes only `index.html`. Fix: `php skills/html-mockup/assets/gallery/_build-gallery.php` |
| `RT_CHASSIS_NOT_BUILT` | FAIL | `RT_GALLERY_NOT_BUILT`'s sibling for the SECOND artifact the same generator run writes (design.md D1): `assets/gallery/_build-gallery.php` is present and one of `assets/chassis/corporate.html` / `assets/chassis/ecommerce.html` is not — the client chassis is generated and untracked, same as the gallery, so a fresh clone has neither until the one command below builds both. Checked per site type, so a build interrupted after writing one chassis and before the other still FAILs. Gated on the generator's own presence for the identical reason its sibling is. Fix: `php skills/html-mockup/assets/gallery/_build-gallery.php` |
| `RT_GALLERY_STALE` | FAIL | `assets/gallery/index.html` exists but no longer answers for its own inputs. Its sibling `RT_GALLERY_NOT_BUILT` asks whether the gallery was built; this asks whether it was built from **these** inputs, a question nothing else in the repo can ask any more — the file is untracked, so git holds no version to diff it against, and an archetype doc edited without a rebuild leaves the whole chain green over output that no longer matches it. The generator stamps a sha256 over its input set into the first line of its output; this row recomputes that digest from disk and compares. **The input set is defined once**, in `skills/html-mockup/assets/gallery/_gallery-fingerprint.php`, which the generator and this audit both `require` — a fingerprint written twice is two fingerprints, and the day they drift the disagreement reads exactly like the staleness this row is for. It covers what the generator READS: `_gallery-images.md`, `img/*.webp`, the `TPL-*.md` archetypes, `fonts/_fonts.php` and the `*.woff2` it embeds, `design-tokens.md`, and the generator's own source. It deliberately excludes `references/style-catalog/`, whose anchor positions are transcribed by hand rather than read (style-catalog PR 4c repointed this exclusion from the single `design-personalities.md` file it named before), and the fingerprint definition itself, which decides no output byte. **Two causes, each its own message:** the output records no fingerprint at all (built before this contract, or hand-edited), or it records one that does not match — both digests are quoted, truncated to 12 hex characters. Gated on the definition's presence in the audited root, exactly as its sibling gates on the generator's, so it never fires in a fixture root that writes only `index.html`. Fix: `php skills/html-mockup/assets/gallery/_build-gallery.php` |
| `RT_BUILDER_NO_TOKENS` | FAIL | a builder asset has no `es_tokens()` block a scan can be bounded by. Two shapes are accepted: it **declares** `es_tokens()` (`es-builder.php`), or it **inherits** it — requires `es-builder.php` and marks its region with a `start of the visual layer` comment, which is what the three sibling assets do, since redeclaring the function would fatal. It fails when neither holds, when `es_tokens()` never closes on a line of its own, when the start marker is missing on an inheriting asset, or when the `end of the visual layer` marker is missing or sits above the region start. An unbounded region is an unscannable one |
| `RT_BUILDER_HARDCODED_TOKEN` | FAIL | a builder asset types a visual literal inside its visual region, and the message names each one as `file:line → literal`. **Five arms.** (1) a hex in any CSS length, including one torn across a concatenation (`'#0F' . 'A968'`); (2) any colour or timing FUNCTION — `rgb(`/`rgba(`, `hsl(`/`hsla(`, `hwb(`, `lab(`, `lch(`, `oklab(`, `oklch(`, `color-mix(`, `cubic-bezier(`, `steps(`; (3) a **named** CSS colour — the full 148-keyword list plus `transparent`, matched only where a colour ends a CSS value, so Spanish prose containing `tan` or `snow` is not a finding; (4) a bare timing keyword (`ease`, `linear`, `steps`…) anchored on the duration in front of it, which is how `transition:…  .28s ease` gets caught while `es_t('ease')` does not; (5) format-blind — any `*color` key fed a quoted string, whatever the syntax, excepting the CSS-wide keywords and Elementor's `custom` control mode. `es_t( '…' )` reads are blanked before any arm runs, so a token whose NAME is a CSS keyword (`ease`, `transparent`) is never charged for being named. PHP comments are not scanned, nor is anything outside the region: a value that cannot reach the emitted data is not a value the axes have to move. `es_rgba( es_t( … ), '0.42' )` is the correct shape for a derived veil and is **not** a finding — the pattern requires no identifier character before `rgba(`. The glob covers the `assets/` of every skill that emits Elementor data — `elementor-core`, `elementor-theme-parts` and `woocommerce`, i.e. all four builder assets. It is a glob per skill rather than four filenames so a new asset dropped into any of those directories is caught, and an explicit skill list rather than one wildcard so it does not scan `framework-audit.php` itself |
| `RT_FONT_NO_SERVING_PATH` | FAIL | a builder asset's `es_tokens()` block names a font family while the framework is not equipped to find out whether anything serves it. Scoped to the token block itself — the one region `RT_BUILDER_HARDCODED_TOKEN` deliberately does not scan — so a declaration can never produce two rows, and sibling assets that inherit `es_tokens()` declare no family and are not asked. Generic and web-safe faces are skipped. **Three causes, each named in the message:** the asset declares no `es_font_serving_check()`; nothing in it calls one, so the check is a function no build reaches; or no `.md` in the tree names it, leaving an operator who sees the warning with nowhere to go. **What it does NOT assert, and the message says so:** whether the font files are actually served. They live on a WordPress site, not in this repo. Only the runtime check can ask the site, and even it answers `sin-confirmar` rather than clean when a build request cannot see the front end's enqueues |
| `RT_STYLE_REPEATS_RECENT` | WARN | `ux-design-system/references/shipped-log.md` — the ledger's newest row names a `Style` that also appears among the 4 rows before it, within the last 5. Read offline from the ledger itself, the same technique its sibling row below uses, because this audit cannot read `es_manifest_read()` (a live WordPress option). Deliberately WARN, not FAIL (`house-rules.md:31`: "a gate that always fails is a gate everyone learns to skip") — repetition is a judgment call the `blind-judges` skill's Judge A makes, over the identical 5-delivery window (`skills/blind-judges/references/corpus.md` "Retention"), so this row only advises. A match further back than the last 5 rows is invisible by design |
| `RT_STYLE_UNRESOLVED_DEFAULT` | FAIL | `ux-design-system/references/shipped-log.md` — a ledger row names which chassis a delivery started from (`Chassis`) but no resolved `Style`. Not a judgment call: `mockup-guide.md:436-447` recorded the real defect this closes — every corporate project shipped `PERS-INSTITUTIONAL` "not because anyone chose them but because nobody was asked to." PR 5a made the intake ask and persist the answer; this row makes the silence audible when that answer never reaches the ledger — the offline-visible shadow of a delivered mockup that still carries the untouched chassis default with no style resolution ever recorded |
| `RT_STYLE_PRECHARGE_UNSHIPPED` | FAIL | `ux-design-system/references/shipped-log.md` — for a row WITH a resolved `Style`, reads that `STY-*.md`'s own "Toggle precharge" table (`web-templates/references/toggles.md`'s shared cross-template list) and FAILs, naming the toggle and the style, for every declared toggle the row's `Toggles` column does not show shipped at the declared value. **Root cause 6** (proposal): the demo gallery varied structure and look but moved exactly one toggle off default across 67 strips — "configuration never did." **No universal floor, rejected by design**: the check is always against the RESOLVED style's own declared list, never a fixed count — a style declaring 2 toggles is exactly as satisfied by 2 shipped as a style declaring 6 is by 6 |
| `RT_BESPOKE_UNDECLARED` | FAIL | `ux-design-system/references/style-catalog/BSP-*.md` (style-catalog PR 6, `ROUTE-BESPOKE`) — a bespoke intake declaration names no position for one of the 8 axes (naming the missing one), or declares no wireframe under "## 2. Wireframe". Reuses `STY-*.md`'s own `**Axes:**` parser and `TPL-*.md`'s own wireframe parser rather than a third. `ROUTE-BESPOKE`'s whole contract is zero precharge — every axis answered explicitly before builder-core, none inherited — and this is the offline gate that makes an undeclared axis audible instead of silently defaulted, the same failure mode `mockup-guide.md:436-447` recorded once for the catalog itself |

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
Build the gallery first — it is generated and untracked, and the audit FAILs
(`RT_GALLERY_NOT_BUILT`) until it exists:

```bash
php skills/html-mockup/assets/gallery/_build-gallery.php
```

First, offline — no WordPress, no connector, both run in a second:

```bash
php skills/framework-audit/assets/framework-audit.php && php tests/test-container-hygiene.php && php tests/test-framework-audit.php && php tests/test-audit-signals.php && php tests/test-write-path.php && php tests/test-replay.php
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
proves determinism the other one has already lost.

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
