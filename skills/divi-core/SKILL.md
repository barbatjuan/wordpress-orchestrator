---
name: divi-core
description: "Trigger: Divi builder, Divi theme, Divi via PHP, build Divi page, Divi theme builder, et_pb. Generate and deploy Divi layouts via WordPress Orchestrator. SCAFFOLD — validate each step and record gotchas as you learn them."
license: Apache-2.0
metadata:
  author: "juan"
  version: "0.3"
---

# Divi Core (execution) — scaffold

Parallel of `elementor-core` for sites where `project-context` reports builder = `divi`.
The visual spec still comes from `ux-design-system`; only the emit/deploy mechanics differ.

> STATUS: scaffold, NOT battle-tested like Elementor. Verify every step on the real site and
> append confirmed findings to `references/gotchas.md`. Do not present unverified Divi behavior
> as proven — say "unverified" to the orchestrator.

## Activation Contract
Use only when the active builder is Divi. Otherwise route to `elementor-core`.

**Build gate — blocking.** This skill writes to a live WordPress site. Do not run until the user
has given an explicit **yes** for THIS build. Reached directly instead of routed by the
orchestrator? Ask for that yes yourself before the first write and stop until you get it.
On an existing site, confirm every page/template you would overwrite by name first.

## Hard Rules
- Native Divi modules only. No custom JS. Custom CSS via the module's built-in Custom CSS
  fields or the page/section advanced CSS — scoped, never a global stylesheet.
  (no verifier: Divi's compiled-CSS artifact name is unconfirmed in this repo, so there is
  nothing to grep yet. Confirm it on the first real build and record it in `references/gotchas.md`.)
- **Fewest containers that do the job** (house rule). `section → row → column → module` is Divi's
  *mandatory* grammar, not padding you can trim. So the rule is not "flatten it" — it is **never
  nest beyond that grammar**. No section inside a section, no row inside a row, no extra
  one-column row whose only job is padding the module already supports. Report the count as
  **UNVERIFIED**, never as a measured PASS.
  (no verifier: divi-core has no helper library yet, so the container count is a manual read of the shortcode tree.)
- **The axis positions arrive here, and nothing holds them.** `html-mockup` hands over the scale,
  density, ground, elevation and composition its approved `:root` declares. There is no
  `di_tokens()`: carry each into every module by hand until the `di_*` library below exists, and
  say so in the hand-off.
  (no verifier: this skill has no assets/ and no token layer, so nothing can tell an axis position that was carried across from one that was silently dropped.)
- Wrap all build logic in named functions; the NovaMira sandbox auto-runs any uploaded `.php`.
  Self-verifying: top-level logic fatals the site on upload, before `execute-php` is reached.
  (no verifier: self-verifying at upload — top-level logic fatals the site before the call is ever reached, so a violation cannot ship quietly.)
- Verify server-side (fetch compiled HTML/CSS, grep). The sandbox domain is usually
  browser-blocked; state that visual confirmation needs the user.
  (no verifier: nothing proves the model actually fetched and grepped; a skipped verification leaves no artifact behind.)

## Execution Steps (validate each)
1. **Detect storage**: Divi layouts live as `[et_pb_*]` shortcodes in `post_content` (meta
   `_et_pb_use_builder = 'on'`, `_et_pb_page_layout`). Confirm on the real post before writing.
2. **Emit**: build the shortcode tree in PHP (section → row → column → module) and write it to
   `post_content` via `wp_update_post`. Keep a helper library mirroring `es_*` (`di_section`,
   `di_row`, `di_module`) under `assets/`.
3. **Header/footer & templates**: use the Divi Theme Builder
   (`et_theme_builder_*` — templates + body/header/footer layouts + assignment rules). Confirm
   the exact option/post-type names by introspection before relying on them.
4. **Deploy**: update the post, clear Divi's static CSS cache
   (`et_core_page_resource_*` / Divi > cache), and verify.

## Output Contract
Report what was built, what was VERIFIED vs assumed, and every new Divi fact discovered.
Flag unverified assumptions explicitly.

## References
- `references/gotchas.md` — Divi findings (starts near-empty; grow it).
- Mirror the concepts in `elementor-core/references/knowledge.md`, translated to Divi.
