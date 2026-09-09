# ROUTE-BESPOKE — the from-scratch escape hatch

`ROUTE-BESPOKE` exists for a project the catalog cannot express. It is deliberately the MORE
expensive route, not the escape from rules: `web-templates/SKILL.md` says "never assemble
sections ad-hoc," and `recommender.md:249-254` already recorded once what happens with no
archetype at all — every site shipped the same Nosotros and the same Contacto. Unstructured
never meant original. It meant defaulted. Zero precharge pays for the difference.

## Zero precharge

A `ROUTE-BESPOKE` project starts with no toggle precharge and no `STY-*` resolution — every
art-direction decision answered from scratch, none inherited from an industry, a gallery card, or
a neighbouring project. `ux-design-system/SKILL.md`'s own axis step already names the risk it
exists to avoid: "an axis nobody sets, or one inherited from a card and never questioned, falls to
the same value on every project." `ROUTE-BESPOKE` is that step run with the precharge switched
off, not a different question.

## The declaration: `BSP-*.md`

Before builder-core runs, write `BSP-<project-id>.md` here, beside the catalog it may feed —
same directory as `STY-*.md`, a different glob prefix, so it never enters `RT_STYLE_TOO_SIMILAR`'s
own comparison. Two parts, reusing the exact two parsers the catalog and the templates already
have — no third document shape:

    # `BSP-<project-id>` — Bespoke declaration

    **Axes:** scale `<pos>` · ground `<pos>` · density `<pos>` · composition `<pos>` ·
    elevation `<pos>` · accent `<pos>` · chassis `<pos>` · ornament `<pos>`

    ## 2. Wireframe

    ```
    COMP-HERO
    COMP-...
    ```

`**Axes:**` is `STY-*.md`'s own paragraph shape (`pers_axes()`); `## 2. Wireframe` is `TPL-*.md`'s
own fenced-block shape (`tpl_wireframe_comps()`, the same parser `RT_TPL_NO_WIREFRAME` uses) — a
declared inventory and order up front, so QA has something to verify the build against. Axis
positions are still drawn from `design-system.md`'s own nine ground families and the rest of
`nm_axes()`'s vocabulary: the route buys freedom to COMBINE any position on any axis without
inheriting a pre-bundled `STY-*`, not freedom to invent an unvetted colour outside the AAA/4.5:1
math Slice 3 already cleared for every ground family. `RT_BESPOKE_UNDECLARED` FAILs a `BSP-*.md`
missing any of the 8 axes — naming the missing one — or missing a declared wireframe; it does not
FAIL a complete one. A position outside the vocabulary is `RT_PERS_BAD_AXIS`, the same row a
`STY-*.md` answers to, since the axes line is the same line read by the same parser.

## The `BSP-*` id is the anchor a mockup is stamped with

Head the file `# \`BSP-<PROJECT-ID>\`` in caps, and stamp that exact id into the mockup's `:root`
as `/* Anchor: BSP-<PROJECT-ID> */`. It is the heading that decides the id — `BSP-` on the
FILENAME only puts the file in the glob — so a heading in lower case registers no anchor at all,
and the mockup that points at it FAILs as an id nothing declares.

`RT_MOCKUP_AXES_MISMATCH` then reads a bespoke anchor exactly as it reads a catalog one: every
axis label in the mockup must be the position this file holds, and every token in its `:root` must
be the value `design-system.md` gives that position. Nothing is relaxed. Before the row could see
`BSP-*.md`, a bespoke project's only moves were to stamp a `STY-*` it does not hold — the exact
defect that row exists to catch — or to leave the marker off, so this is what makes the route
conforming rather than merely tolerated.

Two ids can never collide: a `BSP-*.md` heading that claims an id a `STY-*.md` already claims is
`RT_PERS_DUPLICATE_ID`, and the catalog entry stays authoritative.

## No accessibility exemption

A bespoke build passes every gate a catalog build passes, unchanged: AA contrast and the AAA 7:1
ground gate (both properties of the ground family chosen, proven once at the catalog level), 4.5:1
accent-as-eyebrow, exactly one `<h1>`, Lighthouse accessibility ≥ 90 (`qa-review/references/house-
rules.md` row 13), touch targets (the same Lighthouse run's `target-size` audit), and
`RT_MOCKUP_DISCLOSURE_STATE`. None of these read a route or a `STY-*` id: the Lighthouse/H1/
disclosure gates read whatever HTML was actually delivered, and the ground-family contrast math is
a property of the chosen ground, inherited by construction whichever axis path reached it. Route
selection changes which gate runs on nothing.

## Ledger registration

A completed `ROUTE-BESPOKE` delivery gets a `shipped-log.md` row identical in shape to a catalog
delivery's — same columns, `Style` left blank (nothing was ever resolved from the catalog) and
`Route` set to `bespoke`. `Route` is what keeps `RT_STYLE_UNRESOLVED_DEFAULT` from mistaking a
deliberate zero-precharge build for the untended default it exists to catch, and it keeps the
delivery inside `RT_STYLE_REPEATS_RECENT`'s 5-row window like any other.

## Promotion feeds the catalog, never bypasses it

A bespoke build that works may be written up as a new `STY-*.md` here, carrying the same 8 axis
answers `BSP-*.md` already declared plus the catalog's other required fields (Fits, Typography,
Motion intensity, Imagery, Card recipe, Toggle precharge). It is then just another catalog entry:
`RT_STYLE_TOO_SIMILAR` runs over it exactly as it runs over the original 8, sharing at most two of
the eight axis positions with any existing entry. Promotion grants no exemption from distinctness —
the route feeds the catalog instead of bypassing it, which is the whole reason it is documented
here rather than left as a permanent side door.
