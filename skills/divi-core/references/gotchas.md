# Divi gotchas — to be discovered

This file starts almost empty ON PURPOSE. Divi is not yet battle-tested here. Every time a
Divi build surprises you, add a confirmed entry below in this shape:

```
## <short title>
Symptom: <what you saw>
Cause: <verified reason>
Fix: <what worked>
Do NOT: <the trap>
```

## Known starting points (verify before trusting)
- Layouts store as `[et_pb_section]…[/et_pb_section]` shortcodes in `post_content`, gated by
  meta `_et_pb_use_builder = 'on'`. Writing raw shortcodes to `post_content` is the emit path.
- Divi caches compiled CSS per page (`et_core_page_resource`) — expect to clear it after writes,
  analogous to Elementor's `_elementor_css` / `_elementor_element_cache`.
- Theme Builder (header/footer/templates) is separate from page layouts.
- Global colors/fonts live in the Divi Theme Options / Theme Builder, analogous to the Elementor kit.

Confirm each of the above by introspection on the real site; move proven ones out of
"starting points" into real entries above.

## Divi 5 renders D4 shortcodes, but the style accumulator is lazy-loaded
Confirmed on Divi 5.12.1 / WP 7.1 / PHP 8.3, `et_builder_d5_enabled() === true`.
Symptom: a page built from `[et_pb_*]` shortcodes renders correct, well-formed HTML
(`et_d4_element` classes, real columns, real accordion), but a server-side check finds ZERO
generated CSS — no brand colours, no `.et_pb_section_0` rule, no hero `background-image`. It
looks like Divi silently dropped every design attribute.
Cause: `ET_Builder_Element` does not exist until
`ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework()` has run. Divi
calls it on the front end for any content containing `[et_pb_` (see
`includes/builder-5/server/FrontEnd/BlockParser/BlockParserStore.php:1244-1252`, whose own
comment states "the rest of the rendering pipeline handles D4-on-the-fly for regular page
content"). A REST / `execute-php` context has no queried post, so the framework is never
initialized and `do_shortcode()` emits structure with no styles.
Fix: call `Conversion::initialize_shortcode_framework()` (idempotent) BEFORE verifying, then
read `ET_Builder_Element::get_style()`. On this build that returned 21,223 bytes containing the
hero `linear-gradient(...) , url(...)`, the section padding and every brand colour.
Do NOT: conclude from a bare `do_shortcode()` in a sandbox/REST call that Divi dropped the
styling. That is a FALSE NEGATIVE and will send you rebuilding a page that was already correct.

## Page-scoped `_et_pb_custom_css` is not emitted by Divi 5
Symptom: writing `_et_pb_custom_css` on the post (the D4 "Page Settings > Custom CSS" key) has
no effect; the CSS never appears in `wp_head`, `wp_footer` or the content.
Cause: verified by writing a marker rule and rendering the full lifecycle — the marker never
appears. Divi 5 does not read that D4 meta key on the front end.
Fix: express the design through the module attributes themselves (they DO compile, see above).
Do NOT: rely on that meta as an escape hatch for styling a D4 shortcode page on Divi 5.

## `Conversion::maybeConvertContent()` does not produce native D5 blocks for these modules
Symptom: running the converter over a D4 shortcode page returns block markup, but every
top-level section comes back as `<!-- wp:divi/shortcode-module -->` with the raw shortcode
preserved in the block's `content` attribute — 7 blocks, 0 `wp:divi/section`.
Cause: unmapped modules fall back to the generic shortcode-module wrapper.
Fix: none needed — the wrapper renders through the same D4 compat path. Treat the shortcode
tree as the source of truth and do not chase a native-D5 conversion expecting styling gains.
Do NOT: store the converted output believing it is a "real" D5 layout. It is not.

## A restricted bridge user means kses strips `<style>` out of post_content
Symptom: injecting a scoped `<style>` block into `post_content` as a styling escape hatch
leaves the CSS text rendered as visible body copy on the page.
Cause: the write runs as a low-privilege user (`unfiltered_html === false`), so
`wp_filter_post_kses` is attached to `content_save_pre` and `style` is not in
`wp_kses_allowed_html('post')`. kses removes the tags and keeps the text between them.
Fix: verify with `has_filter('content_save_pre','wp_filter_post_kses')` before planning any
raw-HTML styling. Style through module attributes instead.
Do NOT: reach around it by writing the theme's global CSS option — that is a global stylesheet
(against this skill's hard rules) and on a least-privilege bridge it is deliberately off the
writable-options allowlist.
