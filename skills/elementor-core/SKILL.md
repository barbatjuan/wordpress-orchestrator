---
name: elementor-core
description: "Trigger: Elementor via PHP, WordPress Orchestrator, es-builder, _elementor_data, deploy Elementor page, sandbox php. Header, footer and Theme Builder templates are `elementor-theme-parts`, not this skill. Generate Elementor layouts as raw-PHP JSON and deploy them reliably. Battle-tested."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.3"
---

# Elementor Core (execution)

Build Elementor pages as raw PHP emitting `_elementor_data` JSON, deployed through the NovaMira
connector. Owns the helper library, the deploy pipeline and the Elementor failure modes; the
visual spec comes from `ux-design-system`.

## Activation Contract
Use when `project-context` reports builder = `elementor` and work runs through NovaMira
`execute-php` (not the Elementor UI).

**Build gate — blocking.** This skill writes to a live WordPress site. Do not run until the user
has given an explicit **yes** for THIS build; reached directly rather than routed, ask for that
yes yourself and stop until you get it.

## Hard Rules
- Native Elementor / Elementor Pro widgets only. No third-party widgets. No custom JS.
  (no verifier: an unregistered widget renders empty and shows up, but a third-party widget whose plugin installs cleanly, and custom JS, leave no trace anything greps for.)
- Custom CSS ONLY via the widget's native `custom_css` field (`selector{}`) — it always
  compiles; conditionally-enqueued assets (hover animations, swiper) do not.
  (verifier: the step-7 compiled-CSS grep — a rule that never compiled is absent from that file.)
- **Fewest containers that do the job.** One earns its place only by grouping 2+ children,
  carrying its own background/border/shadow, changing direction at a breakpoint, or boxing a
  lone widget no ancestor boxes. Three
  helpers make the flat shape the easy one: `es_split()` (the section IS the row — never
  `es_section( es_row(...) )`), `es_wide($el,58)` (a width is not a container), `es_photo()`
  (a photo is a widget, not a `background_image`). Target depth `section → grid|row → widget`.
  (verifier: es_container_audit() walks the saved tree and names every container that did not earn its place.)
- **Read the audit verdict.** `es_save_page()` prints `es_container_report()` to stdout before
  writing; end every build function with `es_audit_summary()` and deploy only on
  `VEREDICTO LIMPIO`. `optimizable` is a judgement call, not an error. Detail:
  `references/gotchas.md` → "Container hygiene".
  (verifier: es_container_report() prints the container verdict from inside the save, before that page's data is written.)
- Deterministic IDs: `es_uid_reset('<page>')` once per page, `es_uid()` per element.
  (verifier: `tests/test-replay.php` builds one page twice and diffs the emitted bytes, with a control proving the diff can fail.)
- Wrap all build logic in named functions — the sandbox `require_once`s every `.php` it holds on
  EVERY request, not on upload, and one fatal switches the whole directory off.
  (no verifier: self-verifying — top-level logic fatals the site before `execute-php` is ever reached, so a violation cannot ship quietly.)
- **Read `references/gotchas.md` before the first deploy.** Introspect widget/control names;
  never guess them.
  (no verifier: nothing can tell a guessed control name from a researched one until the build silently renders nothing.)

## Execution Steps
1. `es_manifest_read()`, then `es_manifest_verify()`. Any drift stops here: a recorded id the site
   disagrees with is how this session overwrites what the last one agreed to leave alone.
2. Copy `assets/es-builder.php` into `wp-content/novamira-sandbox/`; override `es_tokens()` — the
   one edit point — with the axis positions and brand `ux-design-system` resolved. Left at its
   defaults, every site ships the same green. Upload dependencies FIRST: a missing one stops the
   run.
3. `es_overwrite_preflight()` with EVERY slug this run writes; show the block, get the yes.
   Moving an existing page is `es_migrate_slug()`, never a second page at the new slug.
4. Write one `es_build_<page>()` per page → `es_save_page(...)`, which defaults to the
   `elementor_header_footer` template so the global header/footer survive. Header, footer and
   Theme Builder templates belong to `elementor-theme-parts`; commerce to `woocommerce`.
5. Deploy with the pipeline in `references/gotchas.md`, in that exact order.
6. `es_set_front_page()` once the home is saved: building a page called Inicio does nothing to
   what `/` serves. Read what it returns.
7. Verify server-side: fetch compiled `post-<id>.css` / front HTML, `substr_count` the expected
   selectors. State that visual confirmation needs the user.
8. `es_manifest_record('pages', …)` — slug → id **only**. Front page id: `'site'`; `'build'`:
   `es_build_fingerprint()`. A `false` means the next session starts blind.

## Output Contract
Report pages/templates built (ids), the audit verdict line, and the server-side grep counts that
prove the styles landed. Capture new failure modes into `references/gotchas.md`.

## References
- `references/gotchas.md` — deploy order, cache metas, sandbox auto-exec, name corrections.
- `references/knowledge.md` — helper API, containers/flex/grid, breakpoints, global kit, control keys.
