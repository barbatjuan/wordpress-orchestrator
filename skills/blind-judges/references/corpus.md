# The corpus — what Judge A is shown, and who writes it

The corpus is the memory this framework never had: what previous projects actually LOOKED like,
rather than which label they were filed under. Without it Judge A has nothing to recognise against
and the differentiation question cannot be asked at all.

It lives in `corpus/`, beside this file. One entry per delivered project.

## The judges never write here

Both judges are read-only. Judge A reads `corpus/`; Judge B does not open it. Neither one adds an
entry, renames a file, or edits the index below. **The orchestrator records the entry after both
verdicts have landed**, so nothing a judge saw can be altered by the same pass that judged it.

## What one entry holds

| Part | File | Notes |
|---|---|---|
| Home hero | `corpus/<yyyy-mm-dd>-<project>-hero.jpg` | 1280 viewport, above the fold only |
| One interior band | `corpus/<yyyy-mm-dd>-<project>-band.jpg` | any content section, same width |
| The tail | `corpus/<yyyy-mm-dd>-<project>-tail.jpg` | the last frame — this is where the footer is |
| Judge B's description | a row in the index below | the eight attributes, verbatim |

**Capture recipe.** Never by hand and never by eye: `../assets/capture.mjs` owns it, at a frozen
1280x860 viewport, one frame per screen capped at nine, JPEG quality 72. Three shots per delivery
keeps five deliveries comfortably under a megabyte,
which is the whole reason the delivered mockup HTML is not stored here: a delivered maqueta
measures hundreds of KB, and client work does not belong in this repository.

**Fold before decoration.** The hero shot is the one that matters. Sameness in this framework has
always announced itself above the fold — the same dark ground, the same centred two-line headline
— and an interior band is there to catch the case where two heroes differ and everything under
them does not.

## Retention

Judge A is shown the **last five** entries plus the current mockup. Older entries stay on disk;
they are simply not shown.

## Index

One row per delivery, newest last. Judge B's description is stored as the eight attributes in
schema order, so two rows can be read against each other directly.

| Date | Project | Ground | Headline voice | Air | Composition | Ornament | Accent | Silhouette | Lift |
|------|---------|--------|----------------|-----|-------------|----------|--------|------------|------|
| _(empty — no delivery recorded yet)_ | | | | | | | | | |
