# Enfoque Vocabulary Specification

## Purpose

The 8 Enfoques survive only as descriptive Spanish vocabulary for brief,
ledger and judges — never as a token engine that repaints a chassis. This
spec defines what `enfoques.md` MUST declare and the one rule that keeps an
Enfoque tied to a real Plantilla.

## Requirements

### Requirement: Eight Enfoques, Described By The Eight Axes

`enfoques.md` MUST describe the 8 Enfoque ids (`editorial`, `directo`,
`materia`, `vitrina`, `institucional`, `tecnologico`, `lujo-oscuro`,
`brutalista`), each across the 8 axes (escala, densidad, fondo, elevación,
composición, acento, chasis, ornamento) plus a par tipográfico and a
dirección de imagen, in prose.

#### Scenario: An Enfoque missing an axis
- GIVEN `brutalista`'s entry in `enfoques.md` has no `composición`
  description
- WHEN a human reviews `enfoques.md` against this contract (named gate: PR
  review — no automated size/shape check exists for this document, by
  design, as the archived `style-catalog` spec already accepted for its own
  entry count)
- THEN the entry is incomplete and MUST be completed before merge

### Requirement: Each Enfoque Is Embodied Or Marked "sin plantilla"

For every Enfoque id, `enfoques.md` MUST state either "lo encarna:
`<slug>`" naming a Plantilla currently in the library whose `ficha.md`
declares that `enfoque`, or "sin plantilla".

#### Scenario: Embodied Enfoque cross-checks
- GIVEN `enfoques.md` states "editorial — lo encarna: delao"
- WHEN `delao/ficha.md` is read
- THEN its `enfoque` field reads `editorial`

#### Scenario: Unembodied Enfoque warns, never fails
- GIVEN `brutalista` has no Plantilla in the library
- WHEN `framework-audit.php` runs
- THEN `RT_ENFOQUE_SIN_PLANTILLA` WARNs and the audit still exits 0

### Requirement: An Enfoque Emits No Tokens

`enfoques.md` MUST remain descriptive language only: no CSS custom property,
hex colour, `clamp()` value, or programmatic axis→style mapping may appear
in it. The axis-matching engine this vocabulary's language descends from —
`axis_matches()`, `RT_AXIS_*` — retires with the generator (`style-axes`).

#### Scenario: A literal token is caught
- GIVEN a future edit adds `--accent: #1b3a2f` to an Enfoque's description
- WHEN a human reviews the diff (named gate: PR review checklist item "no
  CSS/token literal in enfoques.md")
- THEN the change is rejected before merge

### Requirement: `RT_ENFOQUE_REPETIDO_RECIENTE` Warns Over The Last 5 Deliveries

A new audit rule MUST compare a newly resolved Enfoque against the last 5
rows of `shipped-log.md` and WARN — never FAIL — on a repeat within that
window; same semantics as the retired `RT_STYLE_REPEATS_RECENT`.

#### Scenario: Repeat within the window
- GIVEN `editorial` shipped 2 deliveries ago (row 3 of the last 5)
- WHEN a new project resolves to `editorial` again
- THEN `RT_ENFOQUE_REPETIDO_RECIENTE` WARNs and the audit still exits 0

#### Scenario: Repeat outside the window
- GIVEN `editorial` shipped 6 deliveries ago
- WHEN a new project resolves to `editorial` again
- THEN `RT_ENFOQUE_REPETIDO_RECIENTE` stays silent

## Out of Scope

The Plantilla folder contract and the `objetivo`/`enfoque` uniqueness gate
(`plantilla-library`); the Ruta a medida step (`objetivo-routing`); Mapeo
nativo (`native-build-ceiling`).
