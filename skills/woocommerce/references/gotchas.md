# WooCommerce + Elementor Theme Builder — gotchas

## Equal height: `align-items` does NOTHING on WooCommerce's product list

`ul.products` ships as `display:block` with the `li.product` **floated**, so the framework's own
equal-height recipe — `align-items:stretch` on the list, `display:flex` + `height:100%` on the
item — is inert: a float has no cross axis to align on. Measured on a real archive, 407px beside
393px, with every declaration present and reading correctly in DevTools.

Make the list a real grid FIRST, and switch the float off: Woo also hard-codes a percentage
`width` per `columns-N` class, so `float:none !important` and `width:auto !important` both belong
in the fix. Then stretch, the column direction and `margin-top:auto` on the button do what they say.

## In a grid, the clearfix `::before` is an ITEM — and it eats the first cell

After converting `ul.products` to a grid, the first product still started at **x454 instead of
x64**. WooCommerce and most themes add `ul.products::before/::after { content:"" }` to clear the
floats, and a pseudo-element in a grid container is a grid item.

Nine products laid out **2 + 3 + 3 + 1 across four rows — with every card the same height**, so
the equal-height check passed and saw nothing. `ul.products::before, ::after { content:none }`.

## Woo's own pages are created as DRAFTS, and two of them are legal

`Refund and Returns Policy` (Woo) and `Privacy Policy` (WordPress core) are created with
`post_status = draft`. They sit in the page list looking real, and every link to them 404s —
including a consent checkbox pointing at the privacy page, which `wordpress-forms` says is worse
than omitting the checkbox. Publish both, and request the URL rather than trusting the page exists.

## A fresh install is in COMING SOON mode, and the wizard is the only thing that turns it off

WooCommerce 9.1+ ships Launch Your Store: a new install sets `woocommerce_coming_soon = yes` and
flips it only when a human finishes the onboarding wizard. A build script never finishes that
wizard, so the switch stays on through the entire build and into the hand-off.

Set `woocommerce_coming_soon` to `no` as part of store setup, alongside currency and locale. And
check it over HTTP afterwards rather than trusting the write, because of how this hides: with
`woocommerce_store_pages_only = yes` — also a default — the placeholder covers ONLY the store, so
the home page, the about page and the contact form all render perfectly while `/tienda/` and
`/carrito/` answer **200 with the heading "Great things are on the horizon"**. A status-code probe
passes. A heading probe passes. The signal that does not lie is `woocommerce-coming-soon` on the
`<body>` class. Measured on a live hand-off: twelve of twelve pages correct and the shop invisible.

qa-review row 35 carries the check.

## Leftover template hijack (looks "broken")
A single-product page that renders blank/ugly is often a STALE Theme Builder template from
a previous build overriding the loop — not a layout bug. Find product-type templates
(`_elementor_template_type` in `elementor_library`) and disable the old one: set to draft +
clear `_elementor_conditions` + regenerate the conditions cache. Products already had images.

## Theme Builder conditions cache (writing meta is not enough)
`_elementor_conditions` post-meta alone does NOT register a template. The runtime reads the
cache option `elementor_pro_theme_builder_conditions` (`{location:{post_id:[conds]}}`). After
setting meta, run
`\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_cache()->regenerate()`
then VERIFY the option contains your location. The manager's `save_conditions()` throws
"Cannot unset string offsets" — do not use it; regenerate + verify instead.

## Document types & conditions
- Single product: document type `product`, location `single`, condition string `include/product`.
- Product archive: type `product-archive`, location `archive`, conditions
  `include/product_archive/shop_page` | `/product_cat` | `/product_search`
  (the generic `include/general` does NOT work for archives).

## cart_type value
`cart_type` must be `side-cart` (full-height right drawer) or `mini-cart`. `side` is invalid
and renders a broken small box.

## Side cart trapped in the header
The drawer is `position:fixed`; a header with `backdrop-filter` becomes its containing block
and traps it inside the header box. Move the header glass to a `::before` layer (see
`ux-design-system/references/motion.md`). Also `transform`/`filter`/`perspective`/`will-change`
on any ancestor causes the same trap.

## Added-state + inline "view cart"
After AJAX add, the button gets class `.added`. Relabel with CSS (no JS):
`a.button.added{font-size:0} a.button.added::after{content:"Añadido ✓";font-size:13.5px}`.
Hide the redundant inline link: `a.added_to_cart{display:none!important}`.
