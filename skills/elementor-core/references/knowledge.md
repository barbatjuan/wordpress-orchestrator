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
- `es_grid($cols,$children,$gap,$extra)` — grid container (rows forced to `auto`, see gotchas).
- `es_row`, `es_eyebrow`, `es_h`, `es_p`, `es_btn($text,$link,$style,$extra)`
  (styles: primary / dark / outline / outline-light), `es_card`, `es_feature_card`, `es_iconbox`.
- `es_cta_banner($img_slug,$title,$text,$btn_text,$btn_link,$bg)` — rounded closing-CTA band:
  full-bleed photo, dark scrim, copy and button on the left, wrapped in a normal section so it
  keeps the page's boxed width. It was in this file for months named by nothing at all, which is
  what `RT_HELPER_UNROUTABLE` now catches: a helper nobody can find gets rebuilt by hand.
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
  not apply. **MEASURED on Elementor 4.2.2**, by building a page and reading the regenerated
  `post-<id>.css`: a widget with `_padding:33px` produced `.elementor-element-…{padding:33px 33px
  33px 33px;}`, while a widget with `padding:44px` produced NO rule and `44px` appeared nowhere in
  the file. Both directions and the layout keys behave the same — a container with `padding:55px`
  emitted `--padding-top:55px`, one with `_padding:66px` emitted nothing, and `flex_direction` on a
  widget emitted nothing. The wrong form never reaches the stylesheet at all.
  `es_container_walk()` calls it, so these reach the verdict through the existing offender channel.
  The key list is deliberately SHORT: `width` is excluded because it is both a container layout key
  and a real widget control, and an invented offender costs more than a missed one.
  **Re-measured by introspection** on Elementor 4.2.2 + Pro 4.2.1, walking all 128 widget types
  that expose controls and asking each one. Two results, one confirming and one correcting:
  - The five container-only keys are a real control on **zero** widgets, in either spelling —
    and `width` is a real one on **ten**, so the hand-made exclusion was right for the right reason.
  - `padding` is a real `dimensions` control on **three** widgets (`nested-tabs`,
    `call-to-action`, `table-of-contents`) and `background_background` a real `choose` control on
    **seven** (`button`, `archive-posts`, `loop-grid`, `off-canvas`, `posts`, `paypal-button`,
    `stripe-button`). The checker was inventing an offender on all ten and telling their authors to
    break working code. `es_owns_control($widget_type,$key)` now asks Elementor directly when it is
    there, and falls back to that measured list offline. Re-run the introspection when Elementor
    moves: a hardcoded roster goes stale silently, which is why the live question comes first.
- **Manifest** (state between sessions): `es_manifest_read()` → `{schema, updated, sections}`;
  `es_manifest_record($section,$data)` merges ONE section, stamps it, READS IT BACK and returns
  false when it did not land. Section names come from `es_manifest_sections()`, so two skills
  writing different things never overwrite each other's, which a flat map guarantees they
  eventually will — `pages` holds slug → id ONLY, the front page id lives in `site`'s
  `front_page_id`. It lives in a WordPress option and NOT beside this library, because the
  library sits in a sandbox the delivery phase deletes — state that dies with the sandbox is not
  state.
  `es_build_fingerprint()` fills `build` — the library's own sha1 plus the PHP/WP/Elementor
  versions, so a replay can tell whether the SAME library emitted both sites; a version it cannot
  read is `unknown`, never a plausible default. `es_tokens_reset()` drops the token cache, which
  chained builds need because `es_tokens( array() )` returns the PREVIOUS build's palette.
  Both, plus how a finished site reaches production and the three things the migration plugin does
  not know about it: `references/migration.md`.
  `es_record_style_resolution($sty_id, $negative_brief, $rejected_tone)` is `design`'s call site
  (`art-direction-ledger`): call it once the style pick, negative brief and rejected colour
  temperature are resolved (`web-templates/references/recommender.md`), e.g. right where Step 2
  overrides `es_tokens()` with the same catalog entry. Fails closed — any of the three empty and
  nothing is written, `es_manifest_record()`'s own contract, not a new one. A second resolution in
  the same session overwrites `design`, never appends; delivery history is `shipped-log.md`'s job.
  `es_manifest_verify()` contrasts the recorded page map and front page against the LIVE site and
  returns drift lines: page gone, slug moved by hand, same slug answered by a different post id
  (the worst, because everything looks fine), front page repointed. It reports and never repairs
  — repairing means guessing which truth was intended, and only a human knows.
- **Sandbox liveness**: `es_sandbox_state()` -> `{safe_mode, reason, files}`. Read from the
  Novamira loader's source: it `require_once`s every `*.php` there on EVERY request (NOT "on
  upload"), but returns early when `.crashed` exists — one file's fatal disables all of them,
  announced only by a wp-admin banner no agent sees. Measured on two live sites: the one carrying
  `.crashed` had NO `es_*` function defined at all. Catch: in safe mode this function is not
  loaded either, so `project-context` step 8 reads the file directly, before this library exists.
  Never delete `.crashed` without fixing the file named in it.
- `es_safe_mode_check()` → the offending filename, or `''`. Called from `es_save_page()`, because
  reporting safe mode was never the problem: `project-context` step 8 already reported it and the
  next step walked straight past. `.crashed` stops the LOADER, not an explicit `require_once`, so a
  build runs to completion and reports success over a site nobody repaired. It warns **once per
  request** — the opposite of `es_approval_check()`, deliberately: an unapproved write is a fact
  about one page, so falling silent would hide the rest, while safe mode is one fact about the site
  and repeating it per page would bury the pages under it. It does not block, because the way out
  of a crashed sandbox is to run something and a guard that blocks writes blocks the repair too.
- **Delivery**: `es_sandbox_report()` lists what is still in `wp-content/novamira-sandbox/`;
  `es_sandbox_purge()` deletes the build scripts and then RE-READS, returning what SURVIVED —
  the proof is the re-read, because a purge blocked by permissions and one that worked look
  identical from `unlink()`. It never recurses, never touches unknown extensions, and never
  deletes a file that registers a WordPress hook; those still block delivery, they just need a
  human. `es_sandbox_runtime_hooks($path)` is the last of those three and the one learned the hard
  way, on a real client's sandbox: `es-dlo-a11y.php` hooked `template_redirect` and wrapped every
  page in the `<main>` landmark Hello Elementor does not print. That is the site's accessibility,
  not build scaffolding, and it was living in the one directory whose job is to empty itself — so
  hand-off day would have deleted it silently with every check green. A hooking file is MOVED into
  the child theme and deleted here afterwards, never before; this framework may not write PHP
  outside the sandbox, so that move is a human's. The detector reads the source rather than loading
  it (loading is what the sandbox already does every request, and re-running it inside a report is
  a side effect), skips comment lines so a docblock about a removed hook cannot keep a dead file
  alive, and returns the hook NAMES so the warning can say which ones.
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

This framework **cannot close this one itself**, and that is a rule rather than an omission: it is
not allowed to write `.php` outside the sandbox, and the sandbox is emptied at hand-off — so
anything it could install would be deleted by its own delivery phase. A person closes it, either
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
  `es_approval_check($slug)`: writing a slug the block never covered warns, naming it. Per slug,
  not once per run — a single flag goes quiet after the first warning, and the write it would then
  hide is the unapproved one. It warns and does not block: an interrupted build has to be
  resumable without re-approving the pages that already landed. `es_approval_check()` returns the
  verdict, so it is readable without parsing stdout.
- `es_front_page_check()` → `'nothing-built'` | `'page'` | `'posts'`. Called from
  `es_audit_summary()`, so a run that saved pages while `/` still serves the blog says so on the
  one line the operator is told to read before deploying. It does NOT judge which page is the
  front page on a site that already has one: the options say which, never whether it is right, and
  an audit that complains about every correct site is one people scroll past. `$es_saved_pages`
  (slug → id, keyed by where the page LANDED, not what was asked for) is what it counts, and the
  honest source for `es_manifest_record('pages', …)`.
- `es_backup_page_state($id,$keys)` — parks the WHOLE displaced set (layout, page template, edit
  mode, template type, version, post fields, and `post_content`) in `_es_page_backup_<Ymd-His>`.
  **Call it before the first write**: it cannot tell an old value from a new one, and it used to
  run after four of the five keys had already been overwritten, preserving what had just been
  written. Restore key by key. `es_save_page()` calls it only when updating — a page it just
  created has nothing to displace.
- `es_restore_page_state($id,$key='')` — puts a page back the way a backup found it, and **reads
  every piece back**, returning `{key, restored[], failed[], safety}`. Empty `$key` picks the NEWEST
  backup, which is what a human means by "undo that". It BACKS UP FIRST, because restoring is
  itself destructive: the state it overwrites gets its own key, so restoring twice returns you to
  where you started instead of stranding you. A partial restore says WHICH pieces are missing
  rather than returning a cheerful true — the page is then in a mixed state and the warning says
  so. Handing over a key with no way to use it was the gap this closes: a backup nobody can restore
  is not a backup.
- `es_prune_backups($id,$keep=5)` → `{kept[], deleted[], still_there[]}`. Backups are never pruned
  by default — losing the one you needed costs more than the rows — but each now holds the whole
  displaced state, so unbounded is not an option either. Deletes oldest-first and re-reads, because
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
  `'sin-confirmar'`. Called from `es_audit_summary()`, beside `es_front_page_check()` and for the
  same reason: it is one fact about the SITE, so it belongs on the line the operator reads before
  deploying rather than repeated on every page. It asks WordPress which registered post types have
  "font" in the name and reads their published titles — derived, never a post-type constant copied
  out of one plugin — and reads `$GLOBALS['wp_styles']` directly (never `wp_styles()`, which
  instantiates the registry: a report may not change what it reports on) for a Google source among
  the handles this request ENQUEUED (`queue`) or already printed (`done`). **A registration is not
  proof:** core registers `open-sans` against `fonts.googleapis.com` on every installation and
  enqueues it nowhere, so the older probe — a scan of `registered` — accused every site in the
  world, and printed the RGPD warning at sites whose served HTML contains no `googleapis` at all.
  **`'sin-confirmar'` is not a pass and warns.** A build runs in a REST/CLI request where the front
  end's enqueues never fire, so nothing is enqueued here and that question has no answer from
  inside a build: `'alojada'` needs BOTH the families installed AND a front end that was actually
  looked at, and the warning names which half it could not see instead of reporting a clean site —
  a check made stricter until it goes quiet is worse than one that was loud and wrong, because
  nobody notices. Generic and web-safe faces are skipped. The once-per-build latch is `$es_font_said`, a global rather than a
  `static`, because a static cannot be reset and half the behaviour would be untestable.

### Servir las familias tipograficas

`es_tokens()` names `font_head` and `font_body` and every heading and paragraph this framework emits
carries them as `typography_font_family`. **Nothing in this framework makes those families exist on
the site** — there is no `@font-face`, no enqueue, no registration anywhere. The family is a value
written into Elementor; whether a browser can render it is a separate fact nobody was checking, and
a build with every gate green can still ship the client a system fallback.

**Self-host. Never Google's CDN.** This is not a preference and not a performance note: a page that
requests `fonts.googleapis.com` sends every visitor's IP address to a third country the moment the
page opens, with no consent and no legal basis, and EU courts have fined *site owners* for it — the
Munich ruling of Jan 2022 being the one everybody cites. This framework's clients are Spanish. See
`wordpress-legal`, which owns the consent side of the same problem.

The procedure, and it is a **human's**, for the same reason `es_slug_redirects` is: this framework
may not write `.php` outside the sandbox, and the sandbox is emptied at hand-off.

1. **Download the family.** The families this repo ships as defaults are SIL Open Font License, so
   downloading the `woff2` files and serving them from the client's own domain is licensed. Take
   only the weights the build actually uses — every extra weight is a request nobody reads.
2. **Elementor Pro:** *Elementor → Custom Fonts → Add New*. Name it **exactly** the token value
   (`Space Grotesk`, not `space-grotesk`): `es_font_serving_check()` matches on that title, and so
   does Elementor when it resolves `typography_font_family`. Upload one `woff2` per weight/style.
3. **Without Elementor Pro:** the `woff2` files go in the child theme, with `@font-face` (and
   `font-display:swap`) plus a `wp_enqueue_style` for the stylesheet that declares them. That is PHP
   outside the sandbox, so a person writes it.
4. **Then turn Google's copy off**, or the self-hosted files are dead weight under a request that
   still leaks. In Elementor that is the *Google Fonts* dropdown under *Elementor → Settings*, set
   to *Disable* — the tab it sits on has moved between Elementor versions, so find the dropdown
   rather than trusting a path. A theme or plugin that enqueues its own Google stylesheet has to be
   switched off separately, and only the network requests prove it is gone.
5. **Re-run the build** and read `es_audit_summary()`. `'alojada'` means the check found the family
   installed. `'sin-confirmar'` means it could not confirm it from a build request — which is the
   honest answer, not a failure, and `qa-review` closes it by loading a real page and reading the
   network requests, which is the only place the truth is visible.

## Containers, flex, grid
- Layout with flex + grid containers, not the legacy section/column. `content_width` boxed|full.
- **Fewest containers that do the job** (house rule). A container earns its place only by grouping
  2+ children, carrying its own background/border/shadow, changing direction at a breakpoint, or
  boxing a lone widget no ancestor already boxes — Elementor gives a widget no other way to sit at
  the boxed content width, so there the wrapper IS the mechanism.
  Target depth `section → grid|row → widget`. Padding alone is never a reason to exist — put it on
  the widget's `_padding`. `es_container_audit()` measures this; read its log line, and read the
  `NO AUDITABLE` block too: pre-3.6 `section`/`column` elTypes and kit imports are elements this
  audit has no opinion about. They are counted and named there rather than skipped, because a page
  built entirely of them used to measure 0 containers / 0 widgets / depth 0 and read as clean.
- Open question worth resolving on a real site: `es_section( es_grid(...) )` is this repo's dominant
  idiom and costs one level. A single grid container with `content_width:'boxed'` plus the section
  padding *should* collapse the pair into one. Plausible, NOT confirmed — the audit reports it as
  `optimizable`, never as an error. Confirm on a live build before flattening anything wholesale,
  then record the result here.
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
  This line said "set global colors there" for weeks and nothing did: measured on the first real
  build, five pages reported `VEREDICTO LIMPIO` with the type scale exact to the pixel and the
  `h1` painted `rgb(110,193,228)` — Elementor's factory blue — on a WHITE body. `es_tokens()`
  paints only where a helper writes a colour explicitly; the ground, an unstyled heading and every
  link inherit from the kit, and a fresh kit's `_elementor_page_settings` is empty. Call it once
  per build, before `es_rebuild_css()`, and regenerate the kit CSS after.

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
