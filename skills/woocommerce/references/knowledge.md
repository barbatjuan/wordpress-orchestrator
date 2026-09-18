# WooCommerce widget knowledge (Elementor Pro)

## Elementor only in practice
Every execution step in `SKILL.md` is Elementor Pro Theme Builder, both assets are `es_*` examples,
and the output contract greps for `elementor-<id>`. There is no Divi equivalent in this repo —
`divi-core` is a scaffold with no helpers. On a Divi site, say so and stop; do not improvise a Divi
path and do not present one as supported.

## Pages of an ecommerce Plantilla
An ecommerce Plantilla carries the fourteen pages of
`web-templates/references/paginas-obligatorias.md`. Two groups, built differently:

- **WooCommerce's own pages** — carro, pago, pedido recibido, mi cuenta. Map them to the pages
  WooCommerce registers (its Cart, Checkout and My account pages) and the native widgets that list
  names: `woocommerce-cart`, `woocommerce-checkout-page`, `woocommerce-my-account`. Pedido recibido is
  drawn by WooCommerce inside the checkout page when an order completes; the list gives it no widget
  of its own, so it is dressed by the global Site Settings. Those pages are created as drafts and two
  of them are legal (`references/gotchas.md`); publish and link them, never rebuild them as ordinary
  Elementor pages.
- **The catalogue and the product page** — portada products, categoría and ficha de producto. They
  follow the Plantilla ficha's Mapeo nativo section by section, built as the Theme Builder archive
  and single-product templates of the execution steps. Portada products use `woocommerce-products`
  so each card is a real product linking to its own page.

**Check the list against this file before building.** `paginas-obligatorias.md` calls its widget ids
proposed. The category archive loop above is `wc-archive-products`, while that list names
`woocommerce-products` for categoría — the second shows a fixed query, not the archive being viewed.
Use the archive loop on the archive template and say so in the report. Whether a Purchase Summary
widget exists for pedido recibido is unconfirmed here: check the installed Elementor Pro's widget list
before claiming either way.

**The native ceiling counts this skill's CSS.** The accent CTA rule, `es_products_css()` and the
full-screen side cart below all write `custom_css`, and `qa-review` house-rule row 37 counts every
rule against the ficha's `css_custom_max` (default 0). A Plantilla build either replaces them with
native controls or declares the ceiling in the ficha with its reason; never ships over it silently.

## Widgets (native)
- Archive loop: `wc-archive-products` (columns / columns_tablet / columns_mobile, rows,
  paginate, product_title_color, price_color, button_background_color, pagination_color…).
- Arbitrary products: `woocommerce-products` (columns, rows, paginate, query_orderby='date',
  query_order='desc') — use on the home to show REAL products that link to `/producto/…`,
  not decorative cards.
- Single product: `woocommerce-breadcrumb`, `woocommerce-product-images`,
  `woocommerce-product-title`, `woocommerce-product-rating`, `woocommerce-product-price`,
  `woocommerce-product-short-description`, `woocommerce-product-add-to-cart`,
  `woocommerce-product-meta`, `woocommerce-product-data-tabs`, `woocommerce-product-related`.
- Header cart: `woocommerce-menu-cart`. Cart/checkout/account:
  `woocommerce-cart`, `woocommerce-checkout-page`, `woocommerce-my-account`.

## Menu cart controls
`cart_type:'side-cart'`, `side_cart_alignment:'right'`, `automatically_open_cart:'yes'`,
`automatically_update_cart:'yes'`, `items_indicator:'bubble'` (+ `items_indicator_background_color`),
`toggle_button_icon_color`, `toggle_button_border_width` (0 → icon only),
`product_title_color` / `product_price_color` / `product_quantity_color` / `subtotal_color`.
Full-screen on phones: `@media(max-width:767px){selector .elementor-menu-cart__container{width:100vw!important}}`.

## Shared products CSS
`es_products_css()` in `elementor-core/assets/es-builder.php` gives hover lift+zoom, accent
button, equal-height cards, hidden inline "view cart", and the added-state relabel. Reuse it
for archive, related, and home product grids. Add pagination-palette CSS on the archive only.

## WC-native limits to report
Single-product add-to-cart is a form submit unless AJAX add-to-cart is enabled; say so in the report
rather than presenting it as a bug.

## Demo catalog
Simple products via `WC_Product_Simple` (idempotent by title), assign a category image as the
thumbnail so galleries are never blank. Mark fictional testimonials as design-only.
