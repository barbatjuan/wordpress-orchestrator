---
name: blind-judges
description: "Trigger: juez ciego, jueces ciegos, blind judge, se parece a la anterior, todas las webs iguales, diferenciacion, misma mano, mismo estudio, objective review, judge the mockup, external judge. Two read-only judges, blind to each other and to the brief, decide whether a Plantilla or a client maqueta looks professional and whether it reads as made by the same hand as the library or the last deliveries. The result is a veredicto, sealed."
license: Apache-2.0
metadata:
  author: "juan"
  version: "2.0"
---

# Blind Judges

Two identical sites pass every house rule. These judges stand in for "a human looking at them side
by side", and their result is the **veredicto**.

## Hard Rules
- **Blindness is a toolset, not a promise.** `blind-judge-a` and `blind-judge-b` hold Read alone
  — no shell, no browser, no search — so they cannot write, and cannot reach the ficha, the lienzo
  or the maqueta's source. Hand them image paths, nothing else.
  (no verifier: what a launch prompt carried is decided at run time, and nothing in this repo can read it)
- **Never the family that made the design, or say so.** A model rates its own family's output
  higher, hardest on "does this look professional". With no outside family reachable the run is
  SELF-JUDGED (`autojuzgado: sí`) — declared, never counted as an independent pass.
  (no verifier: which family answered is chosen when the judge is launched, and no file here can read that)
- **Different evidence, never the same prompt twice.** A sees a set but not which is new; B sees
  the subject alone, never the set.
  (no verifier: prompt divergence is a property of the run, not of any file this repo can read)
- **No screenshot enters the main thread.** Judging happens inside the subagent; only text returns.
  (no verifier: image reads live in the session transcript, not in this repo)
- **The orchestrator reconciles, never breaks the tie.** It is the party being judged; a
  contradiction goes to the user verbatim.
  (no verifier: who resolved a disagreement is a property of the conversation, not of a file)
- **A verdict never triggers a patch.** "Same hand" or "not professional" sends the design back to
  the lienzo — art direction is the user's call.
  (no verifier: nothing inspects what happened after a verdict was reported)

## Activation Contract
On **every Plantilla**, before it enters the library and whenever its bytes change, and on **every
client maqueta**, after `html-mockup` publishes it and before client approval. Never on a live site.

## Execution Steps
1. **Capture** with `node assets/capture.mjs <page> --label <name> --out <dir>` (frozen 1280x860);
   the 430 and 768 cells of every page come from `visual-verification`.
2. **Choose the family** or declare `autojuzgado: sí` (`references/judge-independence.md`).
3. **Launch `blind-judge-a` and `blind-judge-b` in parallel**, image paths only; wait for both.
   **B**: the subject alone. **A**, unlabeled and shuffled: for a Plantilla, the home of every
   Plantilla in `web-templates/references/plantillas/`; for a client maqueta, those homes plus the
   last five deliveries (`references/corpus.md`) and the current one.
4. **Reconcile:**

| Judge A | Judge B | Result |
|---|---|---|
| no group includes the subject | profesional | PASS |
| a group of two or more | — | FAIL, naming the tell — unless the user accepts it with a reason |
| — | no-profesional | FAIL |
| the two disagree | — | ESCALATE to the user |

5. **Write the veredicto** in `web-templates/references/veredicto-formato.md`. The library judge A
   result goes to `plantillas/_biblioteca.md`, re-emitted whenever a Plantilla enters or changes.
6. **Seal.** A Plantilla: `veredicto.php --sellar <slug>`, then `--comprobar <slug>`
   (`html-mockup/assets/herramientas/`). A client maqueta: no tool seals it yet — record the date and
   the maqueta's `sha256` in the delivery record, marked not tool-sealed.
7. **Record.** The orchestrator appends B's description and three thumbnails to the corpus.

## Output Contract
Both verdicts verbatim, family and `autojuzgado`, the reconciliation row, sweep counts, the
veredicto path and seal state, and on FAIL the tell. PARCIAL or unsealed never reads as PASS.

## References
- `references/judge-independence.md` — the family rule.
- `references/signature-schema.md` — why the judges are shaped this way.
- `assets/capture.mjs` — the frozen capture geometry.
- `references/corpus.md` — the corpus and its recording.
