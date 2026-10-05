# Elementor knowledge (stable)

## Helper library API (`assets/es-builder.php`)
- `es_uid()` / `es_uid_reset($seed)` — deterministic seeded element IDs.
- `es_c($settings,$children,$inner=true)` — container (elType container). `es_w($type,$settings)` — widget.
- `es_size($n,$unit='px')` — slider value. `es_box($t,$r,$b,$l)` — dimensions.
- `es_section($children,$opts)` — boxed section with responsive padding (column direction).
- `es_split($children,$opts)` — TWO-COLUMN section: the section IS the row. `row` on desktop,
  `column` at tablet/mobile, children direct. Replaces `es_section( es_row(...) )` and its
  wasted level. `$opts`: bg, gap, align, reverse, settings.
- `es_wide($el,$pct,$mobile=100)` — width ON the element (`_element_width:'initial'` +
  `_element_custom_width`) instead of a wrapper container. Works on widgets and containers.
- `es_photo($slug,$height,$extra)` — image widget with `object-fit:cover`. Use instead of a
  container `background_image`: keeps the `alt`, saves a container.
- `es_grid($cols,$children,$gap,$extra,$inner=true)` — grid container (rows forced to `auto`, see gotchas); `$inner=false` for a root-level grid.
- `es_row`, `es_eyebrow`, `es_h`, `es_p`, `es_btn($text,$link,$style,$extra)`
  (styles: primary / dark / outline / outline-light), `es_card`, `es_feature_card`, `es_iconbox`.
- `es_cta_banner($img_slug,$title,$text,$btn_text,$btn_link,$bg)` — rounded closing-CTA band:
  full-bleed photo, dark scrim, copy and button on the left, wrapped in a normal section so it
  keeps the page's boxed width.
- `es_save_page($slug,$title,$elements,$tpl,&$action)` + `es_rebuild_css($post_id)`.
  `$action` reports FOUR outcomes: `created`, `updated`, `created-renamed` (WordPress published the
  page under a DIFFERENT slug because the one you asked for was taken — the URL you expect is not
  this page), `failed` (nothing was written; the return value is `0`). Anything other than
  `created`/`updated` needs a human. All three unhappy paths also speak through `es_warn()`, so
  they reach stdout under `ES_AUDIT_SILENT` and the durable log. Do NOT treat a returned id as
  proof the page went where you asked. `es_save_theme_part()` reports the same four.
- `es_key_offenders($type,$settings)` — a setting on the WRONG element type. Elementor names the
  same control differently by location: a container takes `padding`, a widget takes `_padding`
  (wrapper controls carry the underscore). The wrong form saves, opens and renders and simply does
  not apply. **MEASURED on Elementor 4.2.2** (build a page, read the regenerated `post-<id>.css`):
  a widget with `padding:44px`, a container with `_padding:66px` and `flex_direction` on a widget
  each emitted NO rule and the value appeared nowhere in the file. `es_container_walk()` calls it,
  so these reach the verdict through the existing offender channel. The key list is deliberately
  SHORT: `width` is excluded because it is both a container layout key and a real widget control
  (on ten widgets), and an invented offender costs more than a missed one.
  **Introspected** on Elementor 4.2.2 + Pro 4.2.1 over all 128 widget types that expose controls:
  the five container-only keys are a real control on **zero** widgets; `padding` is a real
  `dimensions` control on **three** (`nested-tabs`, `call-to-action`, `table-of-contents`) and
  `background_background` a real `choose` control on **seven** (`button`, `archive-posts`,
  `loop-grid`, `off-canvas`, `posts`, `paypal-button`, `stripe-button`).
  `es_owns_control($widget_type,$key)` asks Elementor directly (the whole `get_stack()`, content AND
  style controls: `get_controls()` alone omits the style ones outside the editor/REST, see gotchas)
  and falls back to that measured list only offline. Re-run the introspection when Elementor moves.
- **Manifest** (state between sessions): `es_manifest_read()` → `{schema, updated, sections}`;
  `es_manifest_record($section,$data)` merges ONE section, stamps it, READS IT BACK and returns
  false when it did not land. Section names come from `es_manifest_sections()`, so two skills
  writing different things never overwrite each other's — `pages` holds slug → id ONLY, the front
  page id lives in `site`'s `front_page_id`. It lives in a WordPress option and NOT beside this
  library, because the library sits in a sandbox the delivery phase deletes.
  `es_build_fingerprint()` fills `build` — the library's own sha1 plus the PHP/WP/Elementor
  versions, so a replay can tell whether the SAME library emitted both sites; a version it cannot
  read is `unknown`, never a plausible default. `es_tokens_reset()` drops the token cache, which
  chained builds need because `es_tokens( array() )` returns the PREVIOUS build's palette.
  Both, plus how a finished site reaches production and the three things the migration plugin does
  not know about it: `references/migration.md`.
  The manifest holds no design (`site`, `pages`, `build` only). The values Step 2 passes to
  `es_tokens()` come from the approved maqueta's `:root` (the brand `ux-design-system` placed
  inside the Plantilla's Enfoque; `qa-review` row 31 compares them).
  `es_manifest_verify()` contrasts the recorded page map and front page against the LIVE site and
  returns drift lines: page gone, slug moved by hand, same slug answered by a different post id
  (the worst, because everything looks fine), front page repointed. It reports and never repairs:
  only a human knows which truth was intended.
- **Sandbox liveness**: `es_sandbox_state()` -> `{safe_mode, reason, files}`. Read from the
  Novamira loader's source: it `require_once`s every `*.php` there on EVERY request (NOT "on
  upload"), but returns early when `.crashed` exists — one file's fatal disables all of them,
  announced only by a wp-admin banner no agent sees (on a site carrying `.crashed`, NO `es_*`
  function is defined). Catch: in safe mode this function is not loaded either, so
  `project-context` step 8 reads the file directly, before this library exists. Never delete
  `.crashed` without fixing the file named in it.
- `es_safe_mode_check()` → the offending filename, or `''`. Called from `es_save_page()`: `.crashed`
  stops the LOADER, not an explicit `require_once`, so a build runs to completion and reports
  success over a site nobody repaired. It warns **once per request** (the opposite of
  `es_approval_check()`: an unapproved write is a fact about one page, safe mode one fact about the
  site). It does not block, because the way out of a crashed sandbox is to run something and a guard
  that blocks writes blocks the repair too.
- **Delivery**: `es_sandbox_report()` lists what is still in `wp-content/novamira-sandbox/`;
  `es_sandbox_purge()` deletes the build scripts and then RE-READS, returning what SURVIVED —
  the proof is the re-read, because a purge blocked by permissions and one that worked look
  identical from `unlink()`. It never recurses, never touches unknown extensions, and never
  deletes a file that registers a WordPress hook; those still block delivery, they just need a
  human. `es_sandbox_runtime_hooks($path)` detects the last of those three: a hooking file may be
  the site's own behaviour, not build scaffolding (a client's `es-dlo-a11y.php` hooked
  `template_redirect` and wrapped every page in the `<main>` landmark Hello Elementor does not
  print). A hooking file is MOVED into the child theme and deleted here afterwards, never before;
  this framework writes PHP outside the sandbox only with explicit human authorization obtained
  beforehand, naming the exact file and destination; without it that move is a human's. The detector
  reads the source rather than loading it, skips comment lines, and returns the hook NAMES so the
  warning can say which ones.
  `es_backup_keys($ids)` returns the restore keys per
  page, newest last. `es_indexing_state()` reads `blog_public` only — `0` is "discourage search
  engines" — and deliberately does NOT parse robots.txt, because a half-parser is a confident
  wrong answer and a virtual robots.txt is invisible from disk anyway.
- `es_migrate_slug($from,$to)` — MOVES the page instead of building a second one at the new slug
  (two pages competing, and the stale one usually wins because it has the history), verifies the
  slug actually moved, and records the pair in the `es_slug_redirects` option. **Nothing in this
  framework serves that option**, so the old URL still 404s: the function warns that on every
  successful move, and `qa-review` row 17 is what confirms a human or a plugin closed it. Refuses
  to move onto an occupied slug — that is finding 15's collision through the back door.

### Servir `es_slug_redirects`

This framework **does not close this one by itself**, and that is a rule rather than an omission: it
writes `.php` outside the sandbox only with explicit human authorization obtained beforehand, naming the exact file and destination, and the sandbox is emptied at hand-off — so
anything it installed there would be deleted by its own delivery phase. Without that authorization a person closes it, either
with a redirect plugin, or by saving this as `wp-content/mu-plugins/nvm-slug-redirects.php`. It is
a mu-plugin on purpose: those load before the theme and cannot be deactivated by accident.

```php
<?php
/* Plugin Name: NovaMira slug redirects
 * Serves the map es_migrate_slug() records. Reads it, never writes it. */
add_action(
	'template_redirect',
	function () {
		if ( ! is_404() ) {
			return;   // only a URL WordPress could not resolve; never shadow a live page
		}
		$map = get_option( 'es_slug_redirects' );
		if ( ! is_array( $map ) || ! $map ) {
			return;
		}
		$slug = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
		$slug = substr( $slug, strrpos( $slug, '/' ) === false ? 0 : strrpos( $slug, '/' ) + 1 );
		if ( ! isset( $map[ $slug ] ) ) {
			return;
		}
		$target = get_page_by_path( $map[ $slug ], OBJECT, 'page' );
		if ( ! $target || 'page' !== $target->post_type ) {
			return;   // the destination moved again or was deleted: a 404 beats a redirect loop
		}
		wp_safe_redirect( get_permalink( $target->ID ), 301 );
		exit;
	}
);
```

Three decisions worth keeping. It runs **only on a 404**, so it can never shadow a page that
resolves. It **re-resolves the destination** instead of trusting the map, because the map records
what was true at migration time and the destination can move again — and a redirect to a slug that
no longer exists is a loop, which is worse than the 404 it replaced. And it uses `es_page_by_slug`'s
own type check inline, because the slug space includes attachments.

`qa-review` row 17 requests `/<old>/` without following redirects and reads the status: `301` PASS,
`404` and `200` both FAIL. **Expect the FAIL until a human installs this** — that is what the row
is for.
- `es_theme_conditions_registered($id)` answers "is my template in the conditions cache" and
  **nothing more**. Registered is NOT rendering: Elementor resolves ONE template per location, so
  ask `es_theme_location_rivals($id)` → `{location: [other_ids]}` before reading a `true` as "the
  header is on the site". A rival — the previous agency's, the theme's, yesterday's build — means
  the template can be saved, conditioned, cached and still never appear, with every check green
  and the site looking untouched. `es_save_theme_part()` warns when the list is non-empty; it
  names rivals and never picks a winner, because the resolution order is not knowable from that
  option.
- `es_overwrite_preflight($slugs)` → `{rows[], overwrites, creates}` and PRINTS the block a human
  approves — never gated on `ES_AUDIT_SILENT`, an approval artifact is not routine output. Run it
  **before the first write**. Each row: `slug`, `id`, `action`, `status`, `is_elementor`,
  `is_front_page`, `converts`. The last two cost the most and show up the least.
  It also RECORDS the slugs it printed, and `es_save_page()` reads that record through
  `es_approval_check($slug)`: a slug the block never covered is REFUSED — `'failed'`, return 0,
  nothing written, not even the backup — and so is one whose page is no longer the one the block
  showed (it appeared, was replaced or was deleted since). The record is the option
  `es_preflight_slugs` (`slug => id seen`), not a variable: every connector call is a new PHP
  request and the human's yes comes between the preflight and the build. Each preflight REPLACES
  the previous one, `es_sandbox_purge()` deletes it, and a write that lands spends its slug, so one
  preflight covers one write. `es_theme_part_preflight( slug => conditions )` is the same gate for
  `es_save_theme_part()` (key `tpl:<slug>`): create or overwrite, current vs new conditions, rivals.
  Resuming an interrupted build is the same call on the slugs still to write: the unwritten ones
  are still approved, a page that landed needs a new preflight, which is cheap and shows the human
  the page as it is now. There is no override. `es_approval_check()` returns the verdict.
- `es_front_page_check()` → `'nothing-built'` | `'page'` | `'posts'`. Called from
  `es_audit_summary()`, so a run that saved pages while `/` still serves the blog says so on the
  one line the operator is told to read before deploying. It does NOT judge which page is the
  front page on a site that already has one: the options say which, never whether it is right, and
  an audit that complains about every correct site is one people scroll past. `$es_saved_pages`
  (slug → id, keyed by where the page LANDED, not what was asked for) is what it counts, and the
  honest source for `es_manifest_record('pages', …)`.
- `es_backup_page_state($id,$keys)` — parks the WHOLE displaced set (layout, page template, edit
  mode, template type, version, post fields, and `post_content`) in `_es_page_backup_<Ymd-His>`.
  **Call it before the first write**: it cannot tell an old value from a new one. Restore with
  `es_restore_page_state()`. `es_save_page()` calls it only when updating — a page it just created
  has nothing to displace.
- `es_restore_page_state($id,$key='')` — puts a page back the way a backup found it, and **reads
  every piece back**, returning `{key, restored[], failed[], safety}`. Empty `$key` picks the NEWEST
  backup, which is what a human means by "undo that". It BACKS UP FIRST, because restoring is
  itself destructive: the state it overwrites gets its own key, so restoring twice returns you to
  where you started instead of stranding you. A partial restore says WHICH pieces are missing
  rather than returning a cheerful true — the page is then in a mixed state and the warning says
  so.
- `es_prune_backups($id,$keep=5)` → `{kept[], deleted[], still_there[]}`. Backups are never pruned
  by default (losing the one you needed costs more than the rows), but each holds the whole
  displaced state. Deletes oldest-first and re-reads, because
  `delete_post_meta()` returns false both on failure and on nothing-to-delete. `$keep` is floored at
  1: keeping zero is not pruning, it is deleting the backups.
- `es_front_page()` → `{mode:'posts'|'page', id, slug}` — the ONE resolver for "what does `/` serve".
  Never guess the home from a slug: on an install whose front page is `/`, `/inicio/` is dead.
  `page_on_front` alone is NOT a front page; without `show_on_front='page'` WordPress renders
  the blog. `es_set_front_page($slug)` points it at a page and READS THE OPTIONS BACK, returning
  `0` and warning if they did not land — `update_option()` returns false both on failure and on an
  unchanged value, so its boolean proves nothing either way. Repointing an existing front page
  warns naming the page that stops being shown; that page stays published, it just stops being
  the one anybody lands on. **Call it once the home is saved**, and hand the id to `qa-review`
  row 16 — the options say WHICH page is the front page, never whether it is the right one.
- `es_img($slug)` — attachment lookup by slug → url+id.
- `es_container_audit($elements)` →
  `{containers,widgets,max_depth,offenders[],optimizable[],unaudited{elType:{count,first}}}`.
  `es_container_report($elements,$label)` echoes to stdout AND `error_log()`s, returns the same
  array; `es_save_page()` calls it automatically before writing.
  `es_audit_summary()` → one verdict line for the whole run. The LINE is the artifact; the int is
  for branching: `0` clean, `>0` offender count, `-1` nothing was audited (a wiring bug — it used
  to return 0, same as a pass), `-2` part of the tree uses elTypes the audit cannot judge. `-2`
  wins. Branch on the INTEGER, never on a word found in the line: your page label is interpolated
  into the line's deep-nesting suffix, so a page can put any text of its own there.
  `-1` speaks through `es_warn()`, so it reaches stdout even under `ES_AUDIT_SILENT`: silencing the
  routine report must never silence "the report never ran".
  **Call it at the end of every build function** — the per-page lines scroll past, the verdict
  is what the deploy step reads. `ES_AUDIT_SILENT` mutes the audit REPORT — the per-page lines and
  the verdict — and nothing else. It does NOT reach `es_warn()`: silencing routine output must
  never silence a warning.
- `es_font_serving_check()` → `'sin-wordpress'` | `'sin-familias'` | `'alojada'` | `'google'` |
  `'sin-confirmar'`. Called from `es_audit_summary()`, beside `es_front_page_check()`: one fact about
  the SITE, on the line the operator reads before deploying. It asks WordPress which registered post
  types have "font" in the name and reads their published titles (derived, never a post-type
  constant copied out of one plugin), and reads `$GLOBALS['wp_styles']` directly (never
  `wp_styles()`, which instantiates the registry: a report may not change what it reports on) for a
  Google source among the handles this request ENQUEUED (`queue`) or already printed (`done`).
  **A registration is not proof:** core registers `open-sans` against `fonts.googleapis.com` on every
  installation and enqueues it nowhere. **`'sin-confirmar'` is not a pass and warns.** A build runs in
  a REST/CLI request where the front end's enqueues never fire, so that question has no answer from
  inside a build: `'alojada'` needs BOTH the families installed AND a front end that was actually
  looked at, and the warning names which half it could not see. Generic and web-safe faces are
  skipped. The once-per-build latch is `$es_font_said`, a global rather than a `static`, because a
  static cannot be reset and half the behaviour would be untestable.
- `es_front_font_probe( $url = '' )` → `'sin-http'` | `'google'` | `'sin-servir'` | `'limpio'` | `'sin-confirmar'`.
  The OTHER END of the same question, and an **entry point**: nothing in the asset calls it,
  `qa-review` does (its Hard Rules name it). It fetches the served HTML — the only place the answer
  lives — and is the only thing that can honestly clear the build's permanent `'sin-confirmar'`.
  Separate from `es_font_serving_check()` because that one is a report and a report may not change
  what it reports on; this makes an HTTP request. **It demands a 200
  AND a closed document before it will say `'limpio'`:** a 401, a 500, an empty body and a holding
  page all contain zero occurrences of `googleapis`, so "I did not find it" is worth nothing until
  the bytes are known to be the page's. Both needles — `fonts.googleapis.com` is the stylesheet,
  `fonts.gstatic.com` the font file a bad self-hosting job still pulls from the same third country.
  `'limpio'` is a statement about THE URL FETCHED, not about the site, which is why `$url` exists.
  **`'limpio'` also means every declared family ARRIVES.** The families come from the kit's global
  typography (what the site says; the probe runs in another request, whose `es_tokens()` are the
  defaults and are NOT used) plus the first family of every `font-family:` in the page CSS it already
  fetched (widgets carry their own; the stylesheets of plugins and core are skipped, because they use their icon
  fonts whether or not the page loads them); nothing declared anywhere is `'limpio'`, never a FAIL (Divi, a fresh
  kit). Only declared families' files are downloaded; responses are capped, the probe has a 30 s budget
  and other hosts go through `wp_safe_remote_get()`. Each one that is not generic or a system
  face needs an `@font-face` reachable from the served HTML (inline `<style>` or a linked stylesheet,
  `url()` resolved against it) whose file is a `data:` URI of font bytes or answers **200 with font
  magic bytes** (a 404 and a theme's soft-404 do not count). Anything else is `'sin-servir'`: a FAIL,
  not a warning. Measured on prueba1 (Elementor 4.2.4): Fraunces and Inter Tight declared in the kit,
  served from nowhere, the page rendered system fonts and the probe said `limpio`.
- `es_font_host( $family, $woff2_bytes, $weight = '400 700', $style = 'normal', $licence = '' )` → the file's
  URL, or `false` after saying why (it refuses a family that is not letters/digits/spaces/hyphens, a weight
  that is not `400` or `400 700`, any style but normal/italic, and bytes that are not `wOF2`: all of it ends up in CSS). The no-Pro, no-PHP, no-Google way to serve a face: writes the `woff2` to
  `uploads/es-fonts/`, adds its `@font-face` (`font-display:swap`) to WordPress's Additional CSS between
  `es-fonts` markers (idempotent per family/style/weight; other CSS untouched), and sets
  `elementor_google_font` to `0`. It reads the CSS back and says so when WordPress did not keep it.
  Elementor writes ONE generic fallback per site after every family (kit `default_generic_fonts`, token
  `font_fallback`, default `Sans-serif` = Elementor's own): set it to the generic of the face that
  carries most of the text, because it cannot follow a serif head and a sans body at once.

### Servir las familias tipograficas

`es_tokens()` names `font_head` and `font_body` and every heading and paragraph this framework emits
carries them as `typography_font_family`. **Writing them does not make them exist on the
site**: the family is a value written into Elementor, and whether a browser can render it is a
separate fact. `es_font_host()` (below) is what makes it exist, and `es_front_font_probe()` is what
proves it; skip either and a build with every gate green ships the client a system fallback.

**Self-host. Never Google's CDN.** This is not a preference and not a performance note: a page that
requests `fonts.googleapis.com` sends every visitor's IP address to a third country the moment the
page opens, with no consent and no legal basis, and EU courts have fined *site owners* for it — the
Munich ruling of Jan 2022 being the one everybody cites. This framework's clients are Spanish. See
`wordpress-legal`, which owns the consent side of the same problem.

The procedure. The default needs **no PHP file**, but `es_font_host()` changes the WHOLE site (Elementor's Google
Fonts off for every page, including ones this framework never built, and rules in the theme's Additional CSS), so
run it only AFTER the build's yes (SKILL.md step 3), never before the preflight. The first run records the
previous state in the option `es_font_host_previous` (previous Additional CSS, previous `elementor_google_font`,
files written); `es_font_unhost()` restores it and removes the files. `es_sandbox_purge()` does NOT delete that
record: the fonts stay on the delivered site, so that is where a human finds the undo. Additional CSS is stored
per theme: switching theme drops the fonts until `es_font_host()` runs again.

1. **Take the bytes.** The families this repo ships are SIL Open Font License, so serving them from the
   client's own domain is licensed. The Plantilla's maqueta names its two families; the OFL `woff2`
   subsets (latin, enough for Spanish) are in `skills/html-mockup/assets/fonts/` (`_fonts.php` maps
   family name to file). Only the weights the build uses.
2. **`es_font_host()` once per face**, with the file's bytes as the argument and the face's true weight
   range (`'400 700'` for the variable Fraunces and Inter Tight). Pass the family's `*-OFL.txt` from that fonts
   folder as `$licence`: the OFL asks for its text to travel with the font, and it is written beside it. Names must equal the token value
   (`Fraunces`, not `fraunces`). This is the mechanism that works without Elementor Pro.
3. **Elementor Pro alternative:** *Elementor → Custom Fonts → Add New*, named exactly as the token.
   **Child-theme alternative:** `@font-face` in a stylesheet plus a `wp_enqueue_style`. That is PHP
   outside the sandbox: this framework writes `.php` there only with explicit human authorization
   obtained beforehand, naming the exact file and destination; without it nothing is written.
4. **Then turn Google's copy off**, or the self-hosted files are dead weight under a request that
   still leaks. In Elementor that is the *Google Fonts* dropdown under *Elementor → Settings*, set
   to *Disable* (option `elementor_google_font` = `0`, which `es_font_host()` writes). A theme or plugin that enqueues its own Google stylesheet has to be
   switched off separately, and only the network requests prove it is gone.
5. **Re-run the build** and read `es_audit_summary()`. `'alojada'` means the check found the family
   installed (Custom Fonts only: `es_font_host()` leaves no font post). `'sin-confirmar'` means it could
   not confirm it from a build request — the honest answer, not a failure. `qa-review` row 36 closes it:
   `es_front_font_probe()` per page must answer `limpio`, and a family that is declared and not served is
   `sin-servir`, a FAIL.

## Containers, flex, grid
- Layout with flex + grid containers, not the legacy section/column. `content_width` boxed|full.
- **Fewest containers that do the job** (house rule). A container earns its place only by grouping
  2+ children, carrying its own background/border/shadow, changing direction at a breakpoint, or
  boxing a lone widget no ancestor already boxes — Elementor gives a widget no other way to sit at
  the boxed content width, so there the wrapper IS the mechanism.
  Target depth `section → grid|row → widget`. Padding alone is never a reason to exist — put it on
  the widget's `_padding`. `es_container_audit()` measures this; read its log line, and read the
  `NO AUDITABLE` block too: pre-3.6 `section`/`column` elTypes and kit imports are elements this
  audit has no opinion about. They are counted and named there rather than skipped (a page built
  entirely of them would otherwise read as clean).
- **Confirmed on Elementor 4.2.4 (prueba1, `terrazza`):** a single grid container with
  `content_width:'boxed'` at the ROOT (no wrapping section) renders as one container at the boxed
  width with its columns intact (the carta's two columns and the two-menu band, measured in the 1280px
  render). Use `es_grid( $cols, $kids, $gap, array( 'content_width' => 'boxed', …padding ), false )`:
  the last argument is `$inner`, and a root container is not an inner one (default `true`, unchanged).
  `es_section( es_grid(...) )` still works and the audit still reports it as `optimizable`; the flat
  form saves the level. The audit judges the root grid clean (one container, no offender).
- Flex item sizing: `_flex_grow` / `_flex_shrink` / `_element_width:auto`. To keep a cluster from
  stretching, set grow/shrink 0 and DON'T set `content_width:full` on it (that forces ~100% width).
- Grid columns: `grid_columns_grid` (+ `_tablet` / `_mobile`). For a 2-col mobile grid pass
  `grid_columns_grid_mobile => {unit:fr,size:2}`.

## Breakpoints & responsive keys
- Per-device suffixes: `_tablet`, `_mobile` on most controls (`width_mobile`, `align_mobile`,
  `flex_justify_content_mobile`, `flex_wrap_mobile`, `padding_mobile`, typography sizes…).
- Visibility: `hide_desktop:'hidden-desktop'`, `hide_tablet`, `hide_mobile`.
- Button full width inside its container: `align => 'justify'` (or force `.elementor-button{width:100%}`).

## Global kit
- Kit id = `get_option('elementor_active_kit')`. Set global colors/typography/buttons there so
  the whole site inherits. Regenerate kit CSS after cache clears.
- **`es_kit_apply()` is what does it.** It carries `es_tokens()` into the kit — the four system
  colours keyed by Elementor's own `_id`s, the body ground, and the link pair — MERGING into
  whatever the kit already holds, and returns the kit id only after reading the write back.
  `es_tokens()` paints only where a helper writes a colour explicitly; the ground, an unstyled
  heading and every link inherit from the kit, and a fresh kit's `_elementor_page_settings` is empty
  (without this the `h1` renders in Elementor's factory blue on a white body). Call it once per
  build, before `es_rebuild_css()`, and regenerate the kit CSS after.

## Control names that are easy to get wrong (introspect to confirm)
- Archive products widget: `wc-archive-products` (NOT `archive-products`).
- Button hover: `button_background_hover_color`, `hover_color`, `button_hover_border_color`
  (NOT `background_hover_color`).
- Menu cart: `cart_type` = `side-cart` | `mini-cart`; `automatically_open_cart:'yes'`;
  item colors `product_title_color` / `product_price_color` / `product_quantity_color`.
- image-box: `image_size` = width slider (%), `thumbnail_size` = WP file size,
  `image_height` + `image_object_fit`.
- Introspect anything unsure:
  `array_keys(\Elementor\Plugin::instance()->widgets_manager->get_widget_types('<name>')->get_controls())`.
