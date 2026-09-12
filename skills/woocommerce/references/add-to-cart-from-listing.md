# Add to cart from the product listing

How to make a shop grid add products to the cart without sending the customer to the
product page — including variable products.

## Start here: is there anything to fix?

**On a stock WooCommerce loop, there is not.** WooCommerce renders AJAX add-to-cart
buttons on archives on its own, and Variation Swatches Pro hooks
`woocommerce_after_shop_loop_item` to print archive swatches. Both work out of the box.

Everything below is compensation for **JetWooBuilder replacing the loop** with its own
card markup, which skips those hooks. Confirm the stack before doing any work.

```
Does the grid use the jet-woo-products widget?
├── No  → Native Woo loop. Check the two settings below and stop. Nothing to build.
└── Yes → Install github.com/barbatjuan/woo-jetwoo-quick-add
          ├── Variants from the listing needed?
          │   ├── No  → Feature A only (card stops navigating). Free.
          │   └── Yes → Needs Variation Swatches PRO. Licensed PER SITE.
          │             No licence, no feature. This is commercial, not technical.
          └── Apply the per-site settings below (the plugin ships code, not settings).
```

Divi: there is no equivalent path in this repo. Say so and stop.

## Diagnose before you touch anything

Never assume the symptom. Two failures look identical to the user and have nothing in
common.

**Settings first** — most "it redirects" reports are just this:

```php
get_option( 'woocommerce_enable_ajax_add_to_cart' );   // must be 'yes'
get_option( 'woocommerce_cart_redirect_after_add' );   // must be 'no'
```

**Then read the rendered button.** Fetch the shop page as a guest and look at the class
list. A simple product should carry `ajax_add_to_cart`. If it does, the markup is fine
and the problem is behavioural, not structural.

**Then click it in a real browser.** This is not optional. Static HTML tells you what
renders, not what happens on click. The JetWooBuilder navigation bug is invisible to
any amount of markup reading — the markup is correct and a JS handler wins anyway.

```js
// After a click, this should log the request AND the URL must not change
performance.getEntriesByType( 'resource' ).filter( r => r.name.includes( 'wc-ajax=add_to_cart' ) )
```

## Per-site settings the plugin does not carry

**WooCommerce → Settings → Products**: AJAX add to cart **on**, redirect after add **off**.

**WooCommerce → Variation Swatches → Archive** (only for the variants feature):

| Setting | Value | Why |
|---|---|---|
| Show on archive | on | Prints the swatches at all |
| Product wrapper selector | `.jet-woo-products__item, .wvs-archive-product-wrapper` | The script runs `.closest()` with this; Jet cards lack the default class |
| Default selected | **off** | With it on, a distracted customer buys a variant they never picked |
| AJAX variation threshold | ≥ largest variation count | At `0` every card fetches variations over AJAX instead of embedding them |
| Alignment | center | Matches a centred card |

## Gotchas that cost real time

**Attribute type is resolved product-first.** A product carries its own
`_woo_variation_swatches_product_settings`, and it beats the global setting. Worse, a
separate `default_to_button` flag reconverts a `select` attribute back into buttons:

```php
if ( $default_to_button && 'select' === $attribute_type ) { $attribute_type = 'button'; }
```

So changing the global attribute type can appear to do nothing at all. Check the product.

**Variable products going to the product page is not always a bug.** WooCommerce cannot
add a variable product without a variant. Before "fixing" it, count how many products are
variable — if it is 15% of the catalogue, the redirect the client is complaining about may
be coming from somewhere else entirely.

**Specificity fights you will lose without `!important`.** Measured, not guessed:

| Rule | Specificity | Source |
|---|---|---|
| `.woocommerce div.product form.cart div.quantity { float: left }` | (0,4,3) | woocommerce.css |
| `.jet-woo-products .jet-woo-product-button .quantity .qty { width: 100% !important }` | (0,4,0) | jet-woo-products.css |
| `.elementor-<id> … .jet-woo-product-button .button { width: 100% }` | (0,6,0) | Elementor per-widget CSS |

That last one comes from the widget's own button-width control. If a client changes it in
the editor and sees no effect, an override is why — say so instead of letting them hunt.

**A floated element ignores `margin: auto` and drops out of flex centring.** Remove the
float first; centring rules applied on top of a float leave no trace of the fight.

**Sub-pixel rounding breaks `flex-wrap: wrap` rows.** A pair measuring 191px in a 194px
container still wrapped. If a row must stay together, say `nowrap` and give it an escape
hatch at narrow widths, rather than trusting the arithmetic.

**Prefer the vendor's own CSS custom properties over overriding its rules.** Variation
Swatches sizes archive swatches from `var( --wvs-archive-product-item-height, 30px )`.
Redefining that variable needs no `!important`, survives plugin updates, and still lets
the plugin's own settings win if they are ever filled in. Look for this pattern before
writing a single override.

## On the no-custom-JS rule

This skill's Hard Rules say native widgets only, no custom JS, and that rule stands for
layout and styling. The JetWooBuilder bridge is a **documented, narrow exception**: it
exists only to reconnect two plugins whose shipped integration is broken by a third, and
it lives in a versioned plugin with dependency guards rather than as a snippet pasted into
a site. If you find yourself writing custom JS for anything else on a storefront, the rule
applies and you are on the wrong path.

## Verification battery

As a guest, private window. Do not report success without running these.

1. Simple product → URL unchanged, cart counter rises.
2. Card image or title → still opens the product page.
3. Variable card → swatches visible, none preselected.
4. Add to cart without choosing → goes to the product page. **Correct**, not a bug.
5. Pick a variant → label changes, and `data-product_id` is the **variation** ID.
6. Quantity + add → URL unchanged, counter rises by that quantity.
7. Cart line shows the right variant and quantity.
8. Apply a JetSmartFilters filter, repeat 5–6 → still works after the AJAX re-render.
9. Phone width → tap targets ≥ 44px, no horizontal overflow (`scrollWidth - clientWidth`).
10. Console → no new errors.

Step 8 is the one people skip and it is the one that breaks: filters replace the whole
grid, so anything bound to specific nodes rather than delegated dies silently.

## Reference implementation

`https://github.com/barbatjuan/woo-jetwoo-quick-add` (private). Its README carries the
full explanation of each of the four missing links, the filters, and the CSS variables.
