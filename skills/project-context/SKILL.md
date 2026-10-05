---
name: project-context
description: "Trigger: detect stack, which builder, Elementor or Divi, read project, WordPress plugins, project constraints, brand. Inspect a WordPress site via its connector before building anything."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.0"
---

# Project Context

Read-only reconnaissance. Determine WHAT you are building on before any skill writes
anything. Modifies nothing.

## Activation Contract
**Existing site**: run FIRST, before `web-templates` / `ux-design-system` / builder-core — the
build routes on what it reports. **New site (greenfield)**: do NOT run during the design phase
(nothing to inspect, wastes the connector round-trip); run at the **build gate** instead, once a
WordPress target + connector exist, to confirm connector/builder/theme before writing.
Re-run if the target site changes.

## Hard Rules
- Never write, create, or delete. Detection only.
- Report the builder explicitly (`elementor` | `divi` | `unknown`); the orchestrator routes on it.
- If the builder is `unknown` or ambiguous, say so — do not guess; let the orchestrator ask.

## Execution Steps (via the connector; step 7 names it)
1. **Builder**: active plugins (`get_option('active_plugins')`) — `elementor/elementor.php`,
   `elementor-pro`, or Divi (`et_divi` theme / Divi Builder). Version from `ELEMENTOR_VERSION`.
2. **Commerce**: is `woocommerce/woocommerce.php` active? note WC version.
3. **Theme**: `wp_get_theme()` name + child theme. On a NEW Elementor build recommend **Hello Elementor** (minimal, no conflicts with global tokens/Theme
   Builder); if a lightweight theme is already active (Astra / GeneratePress), keep it and neutralize
   its defaults rather than swapping. Divi builds keep the Divi theme.
4. **Existing structure**: pages (`post_type=page`), which use the builder
   (`_elementor_edit_mode=builder` / Divi `_et_pb_use_builder`), Theme Builder templates
   (`elementor_library` types + `_elementor_conditions`), the active kit/global styles.
5. **Front page**: `show_on_front` and `page_on_front`. Report `front_page_id` (0 when the site
   serves the blog at `/`) and the slug it resolves to. BOTH options — `page_on_front` alone is not
   a front page. Never infer the home from a slug: on an install whose front page is `/`, a link to
   `/inicio/` is dead. This is what a build must not silently repoint.
6. **Constraints**: menu (`menu-principal` etc), brand palette/logo if present, NAP
   (phone/email/address), language.
7. **Connector**: name it from the session's tools (NovaMira `execute-php`, bridge
   `amb-execute-php`; `references/connector.md`) and confirm PHP execution responds (retry on
   transient "requires additional permissions").
8. **Sandbox state (NovaMira) — check this before promising any build.** Its loader `require_once`s
   every `*.php` in `wp-content/novamira-sandbox/` on EVERY request, but returns early when a
   `.crashed` file exists, disabling ALL of them. One file's fatal switches the whole sandbox off,
   and the only notice is a wp-admin banner an agent never sees. Report: whether `.crashed`
   exists, what it records, and how many `.php` are sitting there. In safe mode nothing you
   upload will run and every `es_*` call dies as an undefined function. Never delete `.crashed`
   to "fix" it — that reloads the file that crashed the site.

## Output Contract
Return a compact block: `builder`, `builder_version`, `woocommerce` (y/n + version),
`theme`, `pages` (id · slug · builder?), `front_page_id` (+ its slug, or `0` = blog),
`theme_templates`, `sandbox` (safe-mode y/n + file count), `constraints`, `open_questions`.
The orchestrator routes on it.

## References
- `references/connector.md`: what each connector provides.
- Pairs with `elementor-core` / `divi-core` (builder execution) and `ux-design-system` (look).
