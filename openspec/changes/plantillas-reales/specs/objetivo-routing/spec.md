# Objetivo Routing Specification

## Purpose

Routing picks a Plantilla by business Objetivo, not by browsing a gallery,
and states explicitly why every rejected candidate does not fit. When
nothing fits, Ruta a medida is a named step under the same gates, not a
silent escape hatch.

## Requirements

### Requirement: Objetivo Catalog

`recomendador.md` MUST declare the Objetivo ids used for routing
(`cartera-curada`, `stock-ocasion`, `ritual-bono`, `plan-fases`,
`tienda-talla`, `tienda-lote`, `suscripcion`, `a-medida`), each tied to one
business-goal sentence. Every Ficha's `objetivo` field MUST reference one of
these ids.

#### Scenario: Ficha references an undeclared Objetivo
- GIVEN a `ficha.md` declares `objetivo: promo-relampago`, absent from
  `recomendador.md`
- WHEN `framework-audit.php` runs
- THEN `RT_PLANTILLA_SIN_FICHA` FAILs

### Requirement: Negative Reasoning Per Rejected Candidate

When `recomendador.md`'s routing step resolves a client to a Plantilla, it
MUST record, for every candidate Plantilla considered and rejected, an
explicit reason it does not fit — not only the winner's reason for fitting.

#### Scenario: Routing without rejection reasons
- GIVEN a routing record names the chosen Plantilla but not why 2 other
  candidates were rejected
- WHEN a human reviews the routing record (named gate: `wordpress-orchestrator`
  route-map review; the conversational routing step itself has no automated
  verifier, the same honest limit `art-direction-ledger` already accepted
  for intake)
- THEN the record is incomplete and MUST be completed before the client
  proceeds to design

### Requirement: An Explicit Ruta a Medida Step When Nothing Fits

`recomendador.md` MUST contain a step reading "ningún Objetivo encaja →
ruta a medida", entered with the same negative reasoning as above, and
routing the client into a from-scratch Canvas under the same Enfoque,
Veredicto and native-ceiling gates as a library Plantilla.

#### Scenario: Missing step fails
- GIVEN `recomendador.md` has no "ningún Objetivo encaja" step
- WHEN `framework-audit.php` runs
- THEN `RT_RECOMENDADOR_SIN_RUTA_A_MEDIDA` FAILs

### Requirement: A Ruta a Medida Declaration Names Objetivo, Enfoque and Sections

Every Ruta a medida engagement MUST declare its Objetivo, its Enfoque and
its section list before design begins.

#### Scenario: Undeclared bespoke engagement fails
- GIVEN a Ruta a medida record has an Objetivo but no Enfoque and no
  section list
- WHEN `framework-audit.php` runs
- THEN `RT_BESPOKE_UNDECLARED` FAILs

### Requirement: Promotion Path Into The Library

A Ruta a medida result that passes its Veredicto and has a complete Mapeo
nativo MAY be promoted into `plantillas/` as a new Plantilla with its own
`ficha.md`, `objetivo` and `enfoque`.

#### Scenario: Promotion produces a conforming Plantilla
- GIVEN a Ruta a medida engagement records `juez_b: profesional` and has no
  `RT_PLANTILLA_SIN_MAPEO` gaps
- WHEN it is promoted with a new `ficha.md` under `plantillas/<slug>/`
- THEN it MUST satisfy every `plantilla-library` requirement like any other
  Plantilla

## Out of Scope

The Veredicto's own pass/fail content (`veredicto-gate`); the Enfoque
catalog's descriptive content (`enfoque-vocabulary`); the Plantilla folder
contract itself (`plantilla-library`).
