# Veredicto Gate Specification

## Purpose

Every Plantilla and every client Maqueta MUST pass a visual gate a FORMAT
rule cannot pass by accident: a per-Plantilla Veredicto (professional
judgment + a 430/768/1280 sweep) and a per-library Veredicto ("same hand"
over all covers), both bound to a committed, LF-normalised hash of the bytes
they judged.

## Requirements

### Requirement: Per-Plantilla Veredicto Content

`plantillas/<slug>/veredicto.md` MUST record: `hash` (sha256 over
`canvas/`+`maqueta/`+`img/`+`ficha.md`, LF-normalised via
`herramientas/huella.php`), `fecha`, `juez_b` (`profesional`|
`no-profesional`), `autojuzgado` (`sí`|`no`), a `barrido` table of every
page × {430,768,1280}, and `vistas`/`saltadas` counts.

#### Scenario: Complete Veredicto passes
- GIVEN `veredicto.md` holds all six fields with 7 pages swept and 0 skipped
- WHEN `framework-audit.php` runs
- THEN `RT_VEREDICTO_INCOMPLETO` and `RT_VEREDICTO_AUSENTE` stay silent

#### Scenario: Missing juez_b fails
- GIVEN `veredicto.md` has no `juez_b` field
- WHEN `framework-audit.php` runs
- THEN `RT_VEREDICTO_INCOMPLETO` FAILs

### Requirement: A Stale Hash FAILs

`RT_VEREDICTO_OBSOLETO` MUST FAIL when the recorded `hash` differs from
`herramientas/huella.php`'s current fingerprint of
`canvas/`+`maqueta/`+`img/`+`ficha.md` — the same discipline
`RT_GALLERY_STALE` already enforced.

#### Scenario: Edit after sealing goes stale
- GIVEN `veredicto.md` is sealed, then `maqueta/index.html` is edited
- WHEN `framework-audit.php` runs
- THEN `RT_VEREDICTO_OBSOLETO` FAILs until `veredicto.php --sellar delao`
  reseals it

### Requirement: A Partial Sweep Records PARCIAL, Never PASS

When any página × breakpoint cell cannot be captured (no browser reachable),
that cell MUST record a typed "no disponible" result, `vistas`/`saltadas`
MUST reflect the split, and the overall barrido MUST read PARCIAL — it MUST
NOT read PASS while any cell is unresolved.

#### Scenario: Unreachable browser yields PARCIAL
- GIVEN `capture.mjs` cannot reach a browser for 2 of 7 pages
- WHEN the sweep is recorded
- THEN `veredicto.md` shows `vistas: 5 / saltadas: 2` and those two pages'
  cells read "no disponible", never PASS

### Requirement: Self-Judgment Is Declared, Never Laundered

Because no outside model family is reachable, every Veredicto MUST record
`autojuzgado: sí`, and downstream reporting (Ficha, `_indice.md`, hand-off)
MUST NOT drop that field or present the result as independently judged.

#### Scenario: autojuzgado survives to the index
- GIVEN `delao`'s Veredicto records `autojuzgado: sí` and
  `juez_b: profesional`
- WHEN `_indice.md` is generated
- THEN it does not report `delao` as PASS without the self-judged qualifier
  remaining retrievable from `veredicto.md`

### Requirement: Library Veredicto and the "Same Hand" Gate

`plantillas/_biblioteca.md` MUST hold a `huella` (sha256 of every current
per-Plantilla `hash`) and `juez_a`'s "same hand" groups over all covers,
re-emitted whenever a Plantilla enters or changes. `RT_BIBLIOTECA_MISMA_MANO`
MUST FAIL any group of ≥2 Plantillas unless it carries an explicit
`aceptado por el usuario: <razón>` line — the perceptual half of
distinctness (mechanical half: `plantilla-library`'s `RT_PLANTILLA_PAR_IGUAL`).

#### Scenario: Unaccepted group fails
- GIVEN juez_a groups `terrazza` and `lumiere` as "same hand" with no
  acceptance line
- WHEN `framework-audit.php` runs
- THEN `RT_BIBLIOTECA_MISMA_MANO` FAILs

#### Scenario: Stale library fingerprint fails
- GIVEN a Plantilla's hash changes but `_biblioteca.md`'s `huella` is not
  recomputed
- WHEN `framework-audit.php` runs
- THEN `RT_BIBLIOTECA_VEREDICTO_OBSOLETO` FAILs

### Requirement: The Sweep Checks the Measured Baseline Defect Classes

At every página × breakpoint, the barrido's hallazgos MUST cover: each
grid's LAST ROW for phantom tracks or orphaned children (`capture.mjs`
reading computed `grid-template-columns` against child count); a non-empty
`<title>`; any price rendered as `NN,NN €` (es-ES, never symbol-first); and
copy in peninsular Spanish, not Rioplatense — all measured defects of the
pre-existing generated catalogue
(`docs/plantillas-reales/2026-09-13-barrido-base.md`).

#### Scenario: Phantom track recorded as a hallazgo
- GIVEN a grid section computes 4 tracks for 3 children
- WHEN `capture.mjs` reads the computed style at any swept breakpoint
- THEN the barrido's hallazgos names the section, the expected track count
  and the observed one — never silently PASS

#### Scenario: Locale defect blocks the professional verdict
- GIVEN a page renders `€68,00` or Rioplatense copy
- WHEN `herramientas/comprobar-maqueta.php` runs against the Maqueta
- THEN the defect is reported and `juez_b` cannot record `profesional` while
  it stands

### Requirement: The Client-Side Half — `RT_LEDGER_SIN_VEREDICTO`

Every `shipped-log.md` row (a delivered client Maqueta) MUST carry a
Veredicto hash and date. A row missing either MUST FAIL
`RT_LEDGER_SIN_VEREDICTO`.

#### Scenario: Ledger row without a Veredicto fails
- GIVEN a `shipped-log.md` row for a delivered client has no Veredicto hash
- WHEN `framework-audit.php` runs
- THEN `RT_LEDGER_SIN_VEREDICTO` FAILs

## Out of Scope

The Ficha/Canvas/Maqueta folder contract (`plantilla-library`);
Elementor/Divi native mapping (`native-build-ceiling`); which Objetivo/
Enfoque a Plantilla serves (`enfoque-vocabulary`, `objetivo-routing`).
