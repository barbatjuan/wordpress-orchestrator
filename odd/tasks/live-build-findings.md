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
- [ ] D1 Fonts: qa-review row 36 reports PASS while the Plantilla's fonts are served from nowhere
  (browser falls back to system fonts). The row must FAIL a declared family that is neither served
  nor a system font, and the build flow must say how the Plantilla's fonts reach the site.
- [ ] D2 `terrazza/ficha.md` asks for an empty container (dotted leader); the container audit counts
  every empty container as an offender. Resolve without editing the sealed ficha.
- [ ] D3 The control-introspection recipe in `elementor-core/references/gotchas.md` returns an
  incomplete list outside the editor on Elementor 4.2.4.
- [ ] D4 `es_manifest_verify()` reports a trashed page as "maybe renamed outside the framework".
- [ ] D5 Tokens: no heading colour separate from body text; H1 capped below the maqueta's size;
  Elementor's default widget spacing not controlled by the kit.
- [ ] D6 `es_grid()` always marks the grid as inner; a root-level boxed grid works on a real site and
  `knowledge.md` still calls it unconfirmed.
- [ ] D7 Rebuild the test site with the fixes and compare against the maqueta at desktop and mobile.

## Acceptance
- prueba1.local serves the Plantilla's typefaces; row 36 fails when they are removed
- audit and tests green; each defect has a test or a recorded live observation

## Progress / evidence
Live build 2026-10-05: pages 76/77/78/82 on prueba1.local; gate cases (a)–(g) all passed across
separate PHP processes; QA 11 PASS, 1 FAIL (sitemap answers 404 with a valid body — a site quirk,
present with the security plugin off). Security plugin installed there afterwards: headers present,
anonymous users endpoint 404, `?author=` 404, pingback methods gone, oEmbed author removed,
authenticated REST users still 200.
