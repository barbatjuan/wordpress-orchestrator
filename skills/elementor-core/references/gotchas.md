# WordPress Orchestrator + Elementor (raw PHP) — hard-won gotchas

Every one of these cost real debugging time. Trust them.

## Deploy pipeline (the ONLY reliable order)
1. **Upload** files via `novamira/create-upload-link` + multipart `curl -F file=@`.
   Raw PUT `--data-binary` is Forbidden by hosting. Token expires (~20 min) and
   the connector intermittently returns "requires additional permissions" — just retry.
2. `require_once` the builder files, then **call the build function explicitly**. End that build
   function with `es_audit_summary()` and **read the verdict it prints** before moving on
   (see "Container hygiene" below). **`LIMPIO` is the only verdict you may deploy on**, and the
   integer it returns is what to branch on, not the text.
   The other three each mean stop, for a different reason: `A CORREGIR` — fix and rebuild;
   `NO AUDITABLE` — part of that tree is elTypes the audit cannot judge, so zero offenders proves
   nothing; `SIN AUDITAR` — `es_container_report()` never ran at all: either the audit is not wired
   into the build function, or the summary was called before anything was saved, or every page
   failed to save. That last one is why this verdict speaks through `es_warn()` and cannot be
   silenced by `ES_AUDIT_SILENT`. The integer it returns matches: `0` clean, `>0` the offender
   count, `-2` not judgeable, `-1` never audited. Both failures are NEGATIVE on purpose, so an old
   `if ( es_audit_summary() )` cannot read them as success, and `-2` beats an offender count
   because you cannot be asked to fix what was never judged.
3. For every touched post id: `delete_post_meta(id,'_elementor_css')` +
   `delete_post_meta(id,'_elementor_element_cache')` + `@unlink(uploads/elementor/css/post-<id>.css)` +
   `\Elementor\Core\Files\CSS\Post::create(id)->update()`.
4. **`es_kit_apply()` FIRST**, then regenerate kit CSS:
   `\Elementor\Core\Files\CSS\Post::create(get_option('elementor_active_kit'))->update()`.
   Regenerating before writing the kit compiles the OLD palette and the whole site keeps
   Elementor's factory blue on white with every check green — see "The build's colours do not
   reach the site until the KIT is written" below. Read what `es_kit_apply()` returns: `0` means
   nothing landed.
5. Regenerate Theme Builder conditions cache (see woocommerce skill).
6. **Verify server-side** — fetch `post-<id>.css` / the front HTML and `substr_count` the
   expected selectors. The browser is often policy-blocked from the sandbox domain, so
   this server-side grep is the only verification available. NEVER claim it works from data alone; say it's verified server-side.

## Container hygiene — the three rules that killed the nesting

The four verdict lines and the four integers `es_audit_summary()` returns are in step 2 above.

Found on a real build (de la O Abogados) AFTER the audit had already shipped. The audit was
right and still changed nothing, for two reasons worth remembering:

- **It reported into `error_log()`**, i.e. the server's PHP log, which nobody fetches. The
  sandbox returns STDOUT, so `es_container_report()` now echoes as well. A rule that is
  measured but never seen is a rule that does not exist.
- **The helper library pushed you into the mistake.** `es_section()` hardcodes
  `flex_direction:column`, so the only way to build two columns was
  `es_section( es_row( ... ) )` — the extra level came from the library, not from carelessness.

Each rule now has a helper that makes the flat version the easy one, AND an audit check that
catches the flat version's absence.

| # | Reflex | Rule | Helper |
|---|--------|------|--------|
| 1 | `es_section( es_row( array($izq,$der) ) )` | **The section IS the row.** `flex_direction:row` on the section, `column` at tablet/mobile, halves as direct children | `es_split()` |
| 2 | A container wrapping one widget to make it 58% wide | **A width does not justify a container.** `_element_width:'initial'` + `_element_custom_width` on the element itself | `es_wide($el, 58)` |
| 3 | `background_image` on a container | **A photo is a widget, not a background.** The background needs a (usually empty) container to live in and ships with no `alt` | `es_photo($slug, $h)` |

Measured on that build, not estimated:

| Página | Antes | Después |
|--------|-------|---------|
| Contacto | 8 contenedores, prof. 4 | 4, prof. 2 |
| Home | 44 contenedores, prof. 4, 5 offenders | 39, prof. 3, 0 offenders |

The last 3 home offenders were the portraits as container backgrounds; moving them to
`es_photo()` cleared them without touching anything else.

**Three severities, on purpose.** `offenders` are wrong with no argument. `optimizable` is a
container whose only child is a GRID — `es_section( es_grid(...) )` is this repo's own dominant
idiom, and an audit that screams on every normal build is one people learn to ignore. Whether
that pair collapses into a single boxed grid container is plausible and **not confirmed**;
verify on a live site before flattening it wholesale. A container whose only child is a flex
ROW is a different story — that one always collapses, so it IS an offender. A child stacking in
a COLUMN is not: `es_split()` would change the axis, so the remedy printed there is to merge the
pair, not to call `es_split()`.

`unaudited` is the third, and it exists because silence is not a verdict. It maps each elType
the walk has no opinion about — pre-3.6 `section`/`column`, a kit import, a future element — to
its count and where it first appeared. Those elements used to fall off the walk entirely, so an
imported page measured 0 containers / 0 widgets / depth 0 and printed `VEREDICTO LIMPIO`. It is
**not** an offender: you cannot fix an import by rewriting an `es_*()` call.

That rule runs one level deeper than it looks. **Below** an element the audit cannot judge it makes
no contextual claim either: the depth is still measured into `max_depth`, but it is never charged
as a `profundidad > 3` offender; a boxed width is not assumed to be inherited; and a container
whose ONLY child is an unjudgeable element is not judged at all, because "pass the padding to the
widget" is not something you can do to a legacy `column`. What a container gets wrong on its OWN —
empty, or wrapping a lone widget for nothing — is still its caller's to fix wherever it sits. The
line is between a container's own defect, which you wrote, and its context, which an import
handed it.

**The one exception to "a wrapper around a single widget is an offender."** A container that is
the only thing constraining a lone widget to the boxed content width earns its place, because
Elementor gives a widget no way to do that itself — `es_section( es_w('wc-archive-products') )`
is the shape. All three conditions matter: the child is a WIDGET, `content_width` is explicitly
`'boxed'` (the runtime default is boxed, so an absent key is not a decision), and no ancestor is
already boxed. Padding is deliberately NOT part of this and must not be added to it — padding on
a wrapper is the canonical thing that belongs on the widget.

`object-fit` on the image widget is hyphenated (that is the control id) and Elementor only
honours it while `height` has a value. Both confirmed on that build.

## The build's colours do not reach the site until the KIT is written

Found on the first real build (LocalWP `prueba1`), and it is the loudest kind of green-and-wrong:
five pages, `VEREDICTO LIMPIO` on all five, the type scale exact to the pixel — `--fs-h1-max`
reaching 120px with a 0.82 leading — and the `h1` rendering **`rgb(110,193,228)` on a WHITE body**.
That blue is Elementor's factory default. Not one resolved colour was on the page.

`es_tokens()` paints only where a helper writes a colour EXPLICITLY. The page ground, a heading
with no `title_color`, and every link inherit from the Elementor kit instead — and a fresh kit's
`_elementor_page_settings` is an empty array. `es_kit_apply()` is the missing step; call it once
per build before `es_rebuild_css()`, then regenerate the kit CSS (deploy step 4).

## `outline-light` is named for the SURFACE it sits on, not for the page's brightness

Measured: the hero's ghost CTA rendered `rgb(14,17,19)` on a `#0E1113` ground — **1:1, invisible**,
with a border at `rgba(14,17,19,.5)` that was invisible too. A call to action nobody can see, and
no text-based check can see it either.

The style takes `es_t('on_inverse')`, which is *the ink that goes ON the inverted surface*. On a
light page the inverted surface is dark, so that ink is light and the style is correct. On an
`ink` ground the inverted surface is LIGHT, so the same token resolves to near-black. **On a dark
ground the ghost button is `outline`** (which takes `es_t('text')`); it measured 17.48:1.

## Elementor enqueues its Google Fonts too late for `wp_enqueue_scripts`

Two separate facts, and both bit on the same build.

**The build itself leaks.** `es_tokens()`'s default `font_head`/`font_body` are `Space Grotesk`
and `Manrope`; leave them and Elementor dutifully requests both from `fonts.googleapis.com`. A
build that never chose a typeface still ships the GDPR problem `knowledge.md` § "Servir las
familias tipograficas" describes.

**And the obvious fix does not work.** Dequeuing by URL in `wp_enqueue_scripts` — even at
`PHP_INT_MAX` — runs BEFORE Elementor registers its `elementor-gf-*` handles during the frontend
render. Measured: 3 requests survived the dequeue, and `elementor_google_fonts = 0` did not stop
them either. `add_filter( 'elementor/frontend/print_google_fonts', '__return_false' )` did: 0
requests across every page. Keep a URL-matching dequeue on `wp_print_styles` as the net for a
theme or plugin enqueuing its own.

## Sandbox executes every .php on EVERY request — and one fatal switches them all off
Symptom: top-level build logic in the sandbox crashes the site ("error crítico"); later, uploads
appear to do nothing at all and every `es_*` call is an undefined function.

Cause: read from `wp-content/plugins/novamira/includes/sandbox-loader.php`, not inferred. The loader
globs `*.php` in the sandbox and `require_once`s each one on **every request** — not "on upload",
which is what this entry used to say. Top-level code therefore runs during `index.php`, before
WordPress finishes loading: a live site's `.crashed` recorded `wp_insert_post()` there dying on an
undefined `is_user_logged_in()`. And before that loop the loader does
`$is_safe_mode = file_exists( $crashed_file ); if ( $is_safe_mode ) { return; }` — so **one file's
fatal disables the WHOLE sandbox, silently**. The only notice is a wp-admin banner, which an agent
working through the connector never sees. Measured on two live sites: the one carrying `.crashed`
had no `es_*` function defined at all, the one without loaded normally.

Confirmed end to end on a live site: with `.crashed` present a counter file uploaded to the sandbox
never ran at all; once the sandbox was emptied and `.crashed` removed, the same file's counter
climbed on every request that boots WordPress — REST/MCP calls and front-end hits alike, with no
caching plugin involved.

Trap when measuring this: `get_option()` is cached per request, so reading the counter INSIDE the
same request that triggered the other hits returns a stale value. It looked like front-end requests
were not executing the loader; they were. Read the counter from a fresh request.

Fix: wrap ALL logic in named functions (`es_build_home()`…), upload defines-only, then `require_once`
+ call the function via `execute-php`. Check `.crashed` BEFORE promising a build — `project-context`
step 8 and `es_sandbox_state()` both report it.

Do NOT: delete `.crashed` to "resume". That reloads the file that crashed the site and crashes it
again. Fix or delete the file it names first, then remove it.

## Deterministic element IDs (critical)
Random IDs desync the compiled CSS from the cached HTML → styles silently don't apply.
Use the seeded counter: `es_uid()` (global `$es_id_seed,$es_id_n`) + `es_uid_reset($seed)` per page.

## `_elementor_element_cache` (the "empty page" root cause)
This meta caches rendered HTML for 24h. After API writes the front serves stale/empty
HTML until it's deleted. Always delete it in the deploy step above.

## Missing kit/global CSS after clearing cache
Clearing caches can drop `global.css` → the whole site loses styling. Always regenerate
the active kit CSS (step 4).

## Verify widget/control names before using them
Introspect instead of guessing:
```php
$w=\Elementor\Plugin::instance()->widgets_manager->get_widget_types('<name>');
array_keys($w->get_controls());          // control keys
$w->get_controls()['<ctrl>']['options']; // valid select values
```
Names that bit us: archive widget is `wc-archive-products` (not `archive-products`);
`cart_type` value is `side-cart` (not `side`); button hover bg is `button_background_hover_color`
(not `background_hover_color`).

## es_size responsive JSON
`es_size(70,'%')` serializes to `{"unit":"%","size":70,"sizes":[]}` — the `sizes` key means a
naive `substr_count('"size":70}')` misses it. Grep with a regex or without the closing brace.

## Boxed container: layout goes on `.e-con-inner`, not the container
A container WITHOUT `content_width=full` (boxed) generates an inner wrapper `.e-con-inner`
that is the real flex row. Apply `justify-content` / `align-items` to `selector>.e-con-inner`,
NOT to `selector` — on `selector` they do nothing.

## Targeting containers: `_css_classes` doesn't stick to containers
`_css_classes` renders as a class on WIDGETS but NOT on containers. To target a specific
container, use structural selectors from the parent: `selector>.e-con:first-child`,
`selector>.e-con:last-child`. Don't rely on a class you set on a container.
