---
name: web-templates
description: "Trigger: plantilla, elegir plantilla, recomendar plantilla, objetivo, ruta a medida, biblioteca de plantillas, wireframe, arquitectura de home, ecommerce template, corporate template, site architecture, template. Choose the client's starting point — a real Plantilla from the library, picked by Objetivo, or an explicit ruta a medida — before any visual or builder work. Site-type aware (ecommerce | corporate)."
license: Apache-2.0
metadata:
  author: "juan"
  version: "2.0"
---

# Web Templates (Plantilla por Objetivo)

Decide where a client site STARTS before `ux-design-system` decides how it looks. The start is a
**Plantilla** — a complete real site for a fictional brand: lienzo, maqueta, photographs, ficha,
veredicto — chosen by **Objetivo**, checked against the client's references by **Enfoque**, or the
**ruta a medida** when nothing fits. Vocabulary: `references/glosario.md`.

## Activation Contract
Runs before `ux-design-system`. Existing site: after `project-context`. New site: first. Modifies
nothing on the site.

## Hard Rules
- **Choose by Objetivo, never by taste or browsing.** Run `references/recomendador.md` in order.
  (no verifier: the routing dialogue is conversational; the person reviewing the orchestrator's routing step reads the decision record)
- **A Plantilla without a current veredicto is never offered as ready.** Read its row in
  `references/plantillas/_indice.md`; if maqueta or veredicto is missing, say so and ask: wait,
  or ruta a medida.
  (no verifier: no audit rule compares the index with the Plantilla folders yet; the reviewer opens the folder and its veredicto)
- **Every rejected candidate gets one sentence of negative reasoning** in the decision record.
  (no verifier: the decision record lives in the conversation, and the reviewer of the routing step reads it)
- **ADN is not negotiable.** A request against the ficha's ADN means another Plantilla or the ruta a
  medida, never a deformed one. What the ficha lists as changeable is adaptation.
  (no verifier: reading a reference against an ADN is judgement; the reviewer reads the record's contradicted-ADN line)
- **Ruta a medida is the expensive path, not a shortcut.** Declare Objetivo, Enfoque and section
  and page list before designing; then the same lienzo, maqueta, veredicto and techo nativo.
  (no verifier: the old bespoke-declaration row still reads the retired format, so today the reviewer reads the declaration in the decision record)
- **The page set is not asked**: the full set for the type in `references/paginas-obligatorias.md`.
  (no verifier: no rule reads a client page set; the veredicto sweep covers every page the ficha lists)
- **The Plantilla's copy and photographs are never the client's.** Only structure, roles and
  framing travel.
  (no verifier: whose words and photos a lienzo carries is read by eye at approval)
- Decide the starting point only: no visual code, no builder data, no deploy.

## Execution Steps
1. **Tipo**, then **Objetivo** — recomendador steps 1–2. A tie goes to the user, never decided alone.
2. **Plantilla** — its `ficha.md` (what it serves, what not) and its index row (step 3).
3. **References** — 2–4, each described on the eight axes of `references/enfoques.md`; check ADN
   and Enfoque (step 4).
4. **Decision record**, confirmed by the user before any design (step 5). Ruta a medida: step 6.
5. **Hand over** `plantillas/<slug>/canvas/`, `ficha.md`, `manifiesto-imagenes.md`, `maqueta/`, the
   record and the page set to `ux-design-system`, then to Claude Design (the design skill), which
   draws the client's lienzo from the Plantilla's. `html-mockup` derives the maqueta from it.

## Output Contract
The confirmed decision record (recomendador step 5 shape), the page set and the paths handed over.
No visual or builder code.

## References
- `references/recomendador.md` — Objetivos, Objetivo → Plantilla, procedure, ruta a medida.
- `references/enfoques.md` — eight Enfoques on eight axes, and who embodies each.
- `references/paginas-obligatorias.md` — page set per type, artboard or derived.
- `references/plantillas/_indice.md` — the library and each Plantilla's veredicto.
- `references/veredicto-formato.md` — what a `veredicto.md` holds and how it is sealed.
- Legacy, not offered to new clients, removal pending: `references/recommender.md`,
  `references/toggles.md`, `references/design-system.md`, `references/templates/ecommerce/`,
  `references/templates/corporate/`, `references/templates/pages/`.
