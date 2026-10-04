# Render defects — what only showed up by looking

Every entry here was invisible to an automated pass that had already reported green. Each one is
written as **what was seen → the measurement that proved it → the rule it produced**, so the next
sweep checks it by name instead of rediscovering it.

Append to this file whenever looking finds something a checker missed. That is the whole point of
the skill.

---

## Grid and proportion

### `aspect-ratio` on an `<img>` yields to any height
**Seen.** Gallery tiles rendered at the wrong shape while the CSS clearly declared a ratio.

**Measured.** `aspect-ratio` on an `<img>` only governs while nothing gives that image a height.
A grid item stretched by its row, or any inherited `height`, silently wins — the declaration stays
in the stylesheet and stops describing the render.

**Rule.** When a ratio matters, prove the element has no competing height. A declared
`aspect-ratio` is not evidence that the ratio is what shipped.

### Columns that fall to a second row
**Seen.** Two column pairs broke onto a new line — the product sheet and the Contact page, the
same pattern twice.

**Measured.** The track definition plus gaps exceeded the container at that breakpoint. Nothing
overflowed, so no overflow check fired; the grid simply reflowed, which is exactly what grid is
supposed to do.

**Rule.** A wrapped grid is a layout defect, not a graceful degradation. Check the intended column
count at every breakpoint, not just that content is visible.

---

## Typography

### A scale that is a constant wearing a curve's clothes
**Seen.** Body copy looked identical across wildly different viewport widths.

**Measured.** `--fs-body: clamp(1rem, 1.2vw, 1.25rem)` in `:root`. The middle term only exceeds
`1rem` above **1333px** of viewport, and only reaches the `1.25rem` ceiling far past any real
screen. Below 1333px the clamp is pinned to its floor — a fluid scale that is a fixed value
everywhere it is actually read.

**Rule.** For any `clamp()`, compute where the middle term overtakes the floor and where it hits
the ceiling. If that window sits outside real viewports, it is not a scale.

### Hierarchy that measures fine and reads flat
**Seen.** Sections judged "too similar" by eye while every automated similarity gate passed.

**Measured.** Distinct inventories per section satisfied the gate; the rendered result still read
as one repeating band because the chassis around each section never varied.

**Rule.** Type and spacing hierarchy is judged per role at the rendered size. Passing a
difference gate is not evidence of visible difference.

---

## Colour and contrast

### Muted text below AA on every alternate section
**Seen.** Secondary copy looked washed out on the alternating bands.

**Measured.** `--c-text-muted` was derived by mixing `--c-text` 63.4% toward **`--c-bg`**, and its
contrast was verified against **`--c-bg`** too. But alternate sections paint `--c-bg-alt`. The
token was checked against a surface it was never rendered on, and fell under AA on every
`.bg-alt` section. **This one reached real client sites.**

**Rule.** Verify a colour against every surface it actually renders on, not against the one it
was derived from. Derivation background and verification background are two different questions.

---

## Composition — the category measurement cannot reach

**Seen.** An automated sweep reported "47/47 clean". Looking at two captures found three real
defects: alignment, typographic scale per role, and proportion.

**Measured.** The sweep checked contrast, hierarchy and overflow. None of those is composition.

**Rule.** Contrast + hierarchy + overflow ≠ composition. Alignment, rhythm, proportion and
balance are judged by eye or not at all — and a report that omits them is incomplete, not clean.

---

## Navigation

### Buttons that all lead to the same place
**Seen.** A stock listing offered many entry points that behaved as one.

**Measured.** An internal-navigation sweep over 217 gallery samples found `TPL-C-07` carrying
**8 "Ver ficha" buttons pointing at the same target**. Every link resolved, so no link checker
complained.

**Rule.** Resolving is not routing. Check link *destinations* for distinctness, not just that
each href is reachable.

---

## Method defects — how the verification itself failed

### A sample reported as a sweep
**Seen.** Four archetypes were converted to the new section chassis, **four strips of twenty-one
were looked at**, and the result was declared green.

**Measured.** The full sweep across all anchors afterwards found the conversion incomplete.

**Rule.** Sampling invalidates a visual verdict. Report the count seen and the count skipped, and
call a partial sweep PARTIAL.

### A rule that never looked where the work grew
**Seen.** Internal pages converged on one architecture per role while a similarity rule was
supposedly preventing exactly that.

**Measured.** The rule pointed only at the two home-page families. Everything in the inner-pages
folder beside them grew outside its reach for a long time.

**Rule.** When a gate reports green, confirm its scope covers the directory the work is actually
in. A gate that cannot see the files is not passing them.

### Green mechanics over unseen output
**Seen.** Five internal pages rendered. Audit 0 FAIL, 1164 tests OK, `run1 == run2`, 56/56 images
— and two real defects sat in the render.

**Measured.** Every green signal described the *generator*, none described the *page*.

**Rule.** Determinism and test counts prove the build reproduces. They say nothing about whether
what it produced is right. Those are separate verdicts and are reported separately.
