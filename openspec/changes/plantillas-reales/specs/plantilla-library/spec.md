# Plantilla Library Specification

## Purpose

A Plantilla is a real, hand-designed starting point — never a generated demo.
This spec defines the folder contract every entry under
`skills/web-templates/references/plantillas/<slug>/` MUST satisfy: Canvas,
Maqueta, images, Ficha and the library index.

## Requirements

### Requirement: Plantilla Folder Contract

Every `plantillas/<slug>/` folder MUST contain `canvas/` (≥1 `.dc.html`, plus
`canvas.json` when there is more than one artboard), `maqueta/index.html`,
`img/`, `manifiesto-imagenes.md`, `ficha.md` and `veredicto.md`. `<slug>`
MUST be a fictional brand, never a real client.

#### Scenario: Complete folder passes
- GIVEN `plantillas/delao/` holds all six required paths
- WHEN `framework-audit.php` runs
- THEN `RT_PLANTILLA_SIN_FICHA`, `RT_PLANTILLA_SIN_CANVAS` and
  `RT_PLANTILLA_SIN_MAQUETA` stay silent

#### Scenario: Missing Maqueta fails
- GIVEN `plantillas/lumiere/` has no `maqueta/index.html`
- WHEN `framework-audit.php` runs
- THEN `RT_PLANTILLA_SIN_MAQUETA` FAILs

### Requirement: The Canvas Authors Mobile, Not Just Desktop

Every Canvas MUST include an artboard authored at a mobile width (≤430px),
with navigation collapsing to a burger pattern there, in addition to its
desktop artboard(s). The Maqueta's responsive behaviour at 430/599/768/900/
1024 — the house's already-audited breakpoints — MUST be authored from that
mobile artboard, not produced by mechanically reflowing a fixed-width layout.

#### Scenario: Desktop-only Canvas fails
- GIVEN a Canvas declares one 1440px artboard and no mobile artboard
- WHEN `framework-audit.php` runs
- THEN `RT_PLANTILLA_SIN_CANVAS` FAILs

#### Scenario: Authored mobile nav sweeps clean
- GIVEN a Canvas mobile artboard shows a burger menu, derived into the Maqueta
- WHEN `RT_PLANTILLA_DESBORDE` sweeps 430/768/1280
- THEN no `scrollWidth > clientWidth` is found and the header CTA does not wrap

### Requirement: No Two Plantillas Share tipo + objetivo + enfoque

The library MUST NOT contain two Plantillas whose `ficha.md` declares the
same `tipo`, `objetivo` and `enfoque` triple. This is the mechanical half of
distinctness; the perceptual half is `veredicto-gate`'s
`RT_BIBLIOTECA_MISMA_MANO`.

#### Scenario: Duplicate triple fails
- GIVEN `aranda` and `corte` both declare `ecommerce` + `tienda-lote` +
  `directo`
- WHEN `framework-audit.php` runs
- THEN `RT_PLANTILLA_PAR_IGUAL` FAILs, naming both slugs

### Requirement: Ficha Frontmatter Schema

`ficha.md` frontmatter MUST declare `slug`, `nombre`, `tipo`
(corporate|ecommerce), `sector`, `objetivo` (an id in `recomendador.md`),
`enfoque` (an id in `enfoques.md`), `paginas`, `fuentes`, `canvas_url`,
`html_widgets_max` and `css_custom_max` (both default `0`); the body MUST
include a "Mapeo nativo" table.

#### Scenario: Unknown objetivo id fails
- GIVEN a `ficha.md` declares `objetivo: promo-flash`, absent from
  `recomendador.md`
- WHEN `framework-audit.php` runs
- THEN `RT_PLANTILLA_SIN_FICHA` FAILs

### Requirement: Image Manifest, One-Shoot Rule, LF Fingerprints

Every `img/` file MUST have a row in `manifiesto-imagenes.md` (slug, rol,
origen, sesión, licencia, alt) and MUST come from one photo session;
filenames MUST NOT change after the shoot. Text under `plantillas/**` MUST
commit as LF (`.gitattributes` `eol=lf`) so `herramientas/huella.php`'s
sha256 is platform-stable.

#### Scenario: Orphan image fails
- GIVEN `img/delao-12.webp` has no row in `manifiesto-imagenes.md`
- WHEN `framework-audit.php` runs
- THEN `RT_PLANTILLA_IMAGEN_SIN_MANIFIESTO` FAILs

#### Scenario: Fingerprint is platform-stable
- GIVEN a Windows contributor with `core.autocrlf=true` commits `ficha.md`
- WHEN `.gitattributes` normalises it to LF and `herramientas/huella.php`
  hashes it
- THEN the fingerprint matches the one produced from identical content on
  Linux

### Requirement: `_indice.md` Reflects the Library

`_indice.md` MUST list every Plantilla with slug, objetivo, enfoque, tipo,
páginas and veredicto date/hash, regenerated whenever a Plantilla is added or
its Ficha/Veredicto changes.

#### Scenario: Stale index fails
- GIVEN `plantillas/tueste/` exists with no row in `_indice.md`
- WHEN `framework-audit.php` runs
- THEN `RT_INDICE_DESACTUALIZADO` FAILs

## Out of Scope

Veredicto content and staleness (`veredicto-gate`); Enfoque semantics
(`enfoque-vocabulary`); Mapeo nativo completeness and the native-widget
ceiling (`native-build-ceiling`); Divi mapping validation (declared "no
validado" elsewhere).
