# live-build-findings

## Objective
Fix every framework defect the first real build after the cleanup exposed (prueba1.local,
2026-10-05, Plantilla `terrazza`, WordPress 7.1, Elementor 4.2.4 without Pro, built through the PHP
CLI, not a connector).

## Why
Everything before this was proven against a fake WordPress. The build rendered and the gate held in
seven live cases, but the result does not look like the maqueta and QA said PASS where it should not.
Owner (2026-10-05): "si todos los defectos"; anything may be done on prueba1.local.

## Constraints
- Branch `fix/live-build-findings` from main 2589239. One writer, no push/merge without the owner.
- Strict TDD (owner global instructions), runner `php tests/<file>.php`. Gate: framework audit
  0 FAIL, every `tests/test-*.php` 0 FAIL, golden fixture regenerated only with its own script.
- KISS. Sealed Plantilla folders are not edited (a ficha defect is reported for re-sealing).
- Each fix is confirmed on prueba1.local, not only in the fake.

## Tasks
- [x] D1 Fonts: qa-review row 36 reports PASS while the Plantilla's fonts are served from nowhere
  (browser falls back to system fonts). The row must FAIL a declared family that is neither served
  nor a system font, and the build flow must say how the Plantilla's fonts reach the site.
- [x] D2 `terrazza/ficha.md` asks for an empty container (dotted leader); the container audit counts
  every empty container as an offender. Resolve without editing the sealed ficha.
- [x] D3 The control-introspection recipe in `elementor-core/references/gotchas.md` returns an
  incomplete list outside the editor on Elementor 4.2.4.
- [x] D4 `es_manifest_verify()` reports a trashed page as "maybe renamed outside the framework".
- [x] D5 Tokens: no heading colour separate from body text; H1 capped below the maqueta's size;
  Elementor's default widget spacing not controlled by the kit.
- [x] D6 `es_grid()` always marks the grid as inner; a root-level boxed grid works on a real site and
  `knowledge.md` still calls it unconfirmed.
- [x] D7 Rebuild the test site with the fixes and compare against the maqueta at desktop and mobile.

## Acceptance
- prueba1.local serves the Plantilla's typefaces; row 36 fails when they are removed
- audit and tests green; each defect has a test or a recorded live observation

## Progress / evidence
Live build 2026-10-05: pages 76/77/78/82 on prueba1.local; gate cases (a)–(g) all passed across
separate PHP processes; QA 11 PASS, 1 FAIL (sitemap answers 404 with a valid body — a site quirk,
present with the security plugin off). Security plugin installed there afterwards: headers present,
anonymous users endpoint 404, `?author=` 404, pingback methods gone, oEmbed author removed,
authenticated REST users still 200.

Fixes (2026-10-05, delegated writer, RED first per defect; parent re-ran audit, all tests, and
checked the served font files and the comparison capture):
- D1 `es_front_font_probe` now returns `sin-servir` when a family the kit declares has no reachable
  `@font-face` with a woff2 answering 200; `es_font_host()` writes the woff2 to `uploads/es-fonts/`
  and the `@font-face` to Additional CSS (no Pro, no PHP file, no Google). Live: `sin-servir` before,
  `limpio` after; Fraunces and Inter Tight render. New token `font_fallback`.
- D2 audit is right; Divider and inline-span recipes recorded in gotchas. Sealed fichas to fix and
  re-seal (owner): `terrazza/ficha.md` lines 164 and 166, `delao/ficha.md` line 94.
- D3 cause verified in Elementor source: outside admin/preview/REST, style controls live in
  `$stack['style_controls']`, which `get_controls()` omits; `es_owns_control()` reads `get_stack()`.
- D4 trashed pages reported by status; the fake WordPress now renames slugs on trash like the real one.
- D5 heading ink and H1 size were documentation gaps (mapping table added to design-tokens.md);
  widget spacing was a real gap (token `sp_widget`, default unchanged).
- D6 `es_grid( ..., $inner = true )`; root boxed grid confirmed on Elementor 4.2.4.
- D7 rebuilt on prueba1; reservation sentence was misuse (one Text Editor with inline spans); the
  "empty band" was a screenshot artefact. Captures in the session scratch dir.
Gate: audit `0 FAIL / 3 WARN`; tests 22 / 128 / 455 / 320 / 11 / 43 / 636; golden fixture identical;
library 16 of 17.
Remaining visible differences from the maqueta (home): uppercase tight headings (the site's own child
theme CSS), rounded buttons, inset photo strip, missing column divider / highlighted row / dish link,
cocktail list in 2 columns instead of 4, larger mobile hero sentence. Header/footer are the theme's.
Open: `/wp-sitemap.xml` answers 404 with a valid body on prueba1 (cause not confirmed).

Independent verification of 976bc8e (2026-10-05, opus, read-only on the site): pass with findings.
One correction round applied (RED first): `es_font_host()` runs after the build's yes, records the
previous Additional CSS and `elementor_google_font` once in `es_font_host_previous`, prints a notice
of the site-wide effect, validates family / weight / style / woff2 magic / uploads dir and writes
nothing on refusal, places its block after `@charset`/`@import`, and can write the OFL text beside
the font; `es_font_unhost()` undoes it. The probe also reads `font-family` declarations in the CSS it
already fetched (plugin and core stylesheets skipped, or icon fonts fail every site), no longer falls
back to the caller's default tokens, downloads only declared families, caps size and time.
`es_kit_settings()` writes the generic fallback and widget spacing only for tokens the build set.
Live: unhost → probe `sin-servir`, host → `limpio`; one marker pair; four files in `uploads/es-fonts/`.
Gate: audit `0 FAIL / 3 WARN`; tests 22 / 128 / 455 / 320 / 11 / 43 / 669; golden identical; 16 of 17.
Accepted limits: a plugin styling real text with an unserved family is not caught; a probe that runs
out of its 30 s budget says `sin-servir`; unhost strips its block rather than restoring a snapshot.
