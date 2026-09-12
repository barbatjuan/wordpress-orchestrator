---
name: woocommerce
description: "Trigger: WooCommerce, shop page, product page, side cart, mini cart, checkout, my account, product archive, add to cart. Build premium WooCommerce storefront pieces via WordPress Orchestrator with native widgets."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.2"
---

# WooCommerce

Build the storefront: shop archive, single product, side cart, and the account/checkout
flows. Native widgets only. Deploys through the active builder's core skill; this skill owns
the commerce-specific structure and gotchas.

**Elementor-only in practice.** Every execution step here is Elementor Pro Theme Builder, both
assets are `es_*` examples, and the output contract greps for `elementor-<id>`. There is no Divi
equivalent in this repo — `divi-core` is a scaffold with no helpers. On a Divi site, say so and
stop; do not improvise a Divi path and do not present one as supported.

## Activation Contract
Use when `project-context` reports WooCommerce active and the task touches shop, product,
cart, checkout, or my-account. Deploy through the builder-core pipeline.

**Build gate — blocking.** This skill writes to a live WordPress site. Do not run until the user
has given an explicit **yes** for THIS build. Reached directly instead of routed by the
orchestrator? Ask for that yes yourself before the first write and stop until you get it.
On an existing site, confirm every page/template you would overwrite by name first.

## Hard Rules
- Native commerce widgets only; no custom JS. Style the CTA to the accent color with
  `!important` (the theme's button color otherwise wins) — verify by grepping the compiled
  `post-<id>.css` for the accent rule, since a losing specificity war leaves no trace in HTML.
  (verifier: step 5's server-side verification is where this bullet's compiled-CSS grep for the accent rule is carried out.)
- Product-card grids must be equal height — do not hand-roll it: `es_products_css()` is the
  single source of truth (see `ux-design-system/references/motion.md` for the why).
  (no verifier: nothing greps for a hand-rolled equal-height rule; one source of truth is a convention here, not a gate.)
- A leftover Theme Builder template can hijack a page and look "broken". Before blaming layout,
  list `elementor_library` templates and their conditions and disable the stale ones; `qa-review`
  row 7 catches the same class of failure from the other side.
  (verifier: `qa-review` house-rule row 7 catches the same class of failure from the other side.)
- **Fewest containers that do the job** applies here HARDER than anywhere else, because a
  commerce template is reused on every product / every archive page — one wasted level is paid
  site-wide, forever. `es_save_theme_part()` audits and prints the count; end the build with
  `es_audit_summary()` and deploy only on `VEREDICTO LIMPIO`. Use `es_split()` for the
  gallery + buy-box split (the template IS the row) and `es_photo()` over container background
  images. Full rules: `elementor-core/references/gotchas.md` → "Container hygiene".
  (verifier: es_save_theme_part() audits and prints the container count for every template it writes.)
- **Read `references/gotchas.md` before building templates** (Theme Builder conditions are subtle).
  (no verifier: nothing can prove a reference file was read; only a subtle condition bug surfaces it later.)

## Execution Steps
1. **Shop archive**: a `product-archive` Theme Builder template using the archive-products
   widget (columns, pagination in palette). Model on `assets/es-shop-template.example.php`.
2. **Single product**: a `product` Theme Builder template (breadcrumb → gallery + buy box →
   tabs → related), condition `include/product`. Model on `assets/es-product-single.example.php`.
3. **Side cart**: the native menu-cart widget in the header — `cart_type:'side-cart'`,
   `automatically_open_cart:'yes'`; full-screen on phones via scoped custom CSS; fix item-name
   contrast with `product_title_color` etc.
4. **Relabel added state / hide inline "view cart"** via the shared products CSS
   (`es_products_css()` in `elementor-core/assets/es-builder.php`).
5. Register + regenerate Theme Builder conditions, then verify server-side.

## Output Contract
Report templates built (ids), conditions registered, the container-audit verdict line, and the
server-side checks (front HTML uses `elementor-<id>`, gallery/tabs/related present). Note
WC-native limits (e.g. single-product add-to-cart is a form submit unless AJAX-add is enabled).
A missing conditions registration is reported by `es_warn()` on stdout — if you see it, the
template exists in the library and renders NOWHERE. That is a failure, not a warning to skim.

## References
- `references/knowledge.md` — widget names, template types, cart controls, products CSS.
- `references/gotchas.md` — Theme Builder condition cache, leftover templates, cart trapping.
- `assets/es-shop-template.example.php`, `assets/es-product-single.example.php`.
