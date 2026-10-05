---
name: elementor-theme-parts
description: "Trigger: header, footer, cabecera, pie de pagina, theme builder, plantilla global, elementor_library, _elementor_conditions, global header, site header, sticky header, menu principal, burger. Build and register Elementor Pro Theme Builder parts — the ones reused on every page."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.0"
---

# Elementor Theme Parts

Header, footer and Theme Builder templates: built once, shown on every page.

## Activation Contract
Split from `elementor-core` because this is a different WRITE, not a different look. Theme parts
live in the `elementor_library` post type, register through `_elementor_conditions` and a cached
option, and need Elementor **Pro** — none of which applies to a page. Their failures are their own
too, and they ship site-wide instead of on one URL.

The LOOK is not yours. Tokens and layout come from `ux-design-system`, approved in `html-mockup`,
exactly as for pages. A header that does not match the pages is the most visible drift there is.

Needs `project-context` first (is Elementor Pro active?). Run BEFORE the pages, so they inherit a
header that already exists.

**Build gate — blocking.** This skill writes to a live WordPress site. Do not run until the user
has given an explicit **yes** for THIS build; reached directly rather than routed, ask for that yes
yourself and stop until you get it.

## Hard Rules
- **Registered is not rendering.** Elementor resolves ONE template per location, so a rival already
  claiming `header` means yours can be saved, conditioned, cached and never appear. A rival you
  looked at and chose to keep goes in `$known_rivals` — by ID, so one appearing later still warns.
  (verifier: `es_theme_location_rivals()` names every other template registered at the same location, and `es_save_theme_part()` warns for each one the caller has not acknowledged.)
- **ONE header and ONE footer, byte-identical on every page.** A per-page copy is how they drift.
  (verifier: `qa-review` house-rule row 7 hashes the header and footer fragment of every page and requires all hashes to match.)
- **The header is real navigation**: exactly one menu, the logo links home, no dead links, present
  everywhere.
  (verifier: `qa-review` house-rule row 5 counts nav-menu widget instances; rows 4, 6 and 7 cover the logo href, dead links and header presence per page.)
- **Sticky is proven by config, never by looking.** The class is applied by JS at runtime.
  (verifier: `qa-review` house-rule row 8 reads the stored sticky setting; the behaviour itself is explicitly eyes, not auto.)
- **Read the mobile 3-zone recipe before building a header.** It is the hardest pattern here and it
  has bitten every time it was improvised.
  (verifier: `qa-review` house-rule row 10 counts the mobile rules in the compiled CSS and then measures the three zones.)
- **Never leave the part unverified after saving.** Conditions written is not conditions cached.
  (verifier: `es_theme_conditions_registered()` re-reads the cache option after regeneration, which is the only proof the runtime can see the template.)

## Execution Steps
1. Confirm Elementor **Pro**. Without it there is no Theme Builder: say so and stop.
2. Upload `assets/es-theme-parts.example.php` as `es-theme-parts.php` AFTER `es-builder.php` — it
   depends on it, and its own guard reports a missing dependency instead of fatalling.
3. Build header and footer from the approved tokens. Read `references/gotchas.md` first.
4. Run `es_theme_part_preflight()` for every part and show the block (create or overwrite, current
   vs new conditions, rivals). Save with `es_save_theme_part()`, which refuses a part it did not
   list, then check `$action` and the rival list before believing it.
5. Regenerate the conditions cache and VERIFY the template is in it. Then load a page and look.

## Output Contract
Per part: slug, id, `$action`, the conditions written, whether the cache contains it, and any rival
at the same location. Anything unconfirmed is UNVERIFIED and named.

## References
- `references/gotchas.md` — the mobile 3-zone header and the conditions-cache traps.
- Pairs with `elementor-core` (pages, and `es-builder.php` which this depends on) and `woocommerce`
  (commerce templates use the same save path).
