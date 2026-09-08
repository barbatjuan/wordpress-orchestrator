---
name: qa-review
description: "Trigger: verify, QA, review before handoff, did it work, check the build, responsive check, accessibility check. Verify a NovaMira build against intent before the orchestrator reports done."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.4"
---

# QA Review

The gate before hand-off. Confirm the change actually landed and matches intent. Evidence
before assertions — never claim "done" without a check.

## Activation Contract
After any build/deploy skill, before the orchestrator reports completion. Also on "does it
work?" / "verify".

## Hard Rules
- No success claim without evidence. If a check couldn't run, say so — don't assume PASS.
- The sandbox domain is usually browser-blocked; verify SERVER-SIDE (fetch compiled CSS/HTML,
  `substr_count` the expected selectors). State plainly that the final visual sign-off is the user's.
- Report failures with the actual output, not a summary that hides them.

## Execution Steps
1. **Applied?** — builder-aware; know the builder before counting.
   - *Elementor*: fetch `post-<id>.css` + front HTML; count the selectors/values the change added
     (hover transforms, `100vw`, accent `!important`, template `elementor-<id>`). Zero counts =
     not applied → investigate cache/conditions, don't report done.
   - *Divi*: emits neither artifact — zero counts prove nothing. Check `post_content` (`et_pb_*`)
     + front HTML. Divi's compiled-CSS artifact name is unconfirmed here (`divi-core` is a
     scaffold) → report UNVERIFIED, never PASS or FAIL; record the real name in
     `divi-core/references/gotchas.md`.
2. **House rules**: run `references/house-rules.md` end to end (currency, cart icon + badge, theme,
   logo → home, ONE menu, no dead links, header everywhere, sticky, header/footer verbatim, mobile
   3-zone, the approved mockup's axis positions). One verdict per row, skip none silently.
   The approved `html-mockup` output is the visual contract: row 31 compares its `:root` axis
   positions against what the build resolved, and states plainly which axes it proved — scale and
   elevation twice, ground and density once, composition never, that one being the user's eyes.
3. **Responsive**: the per-device rules exist (mobile centering, 2-col grids, header one row on
   desktop, full-width mobile CTA). Ask the user to eyeball ~430 / 768 / 1280 — you can't see it.
4. **Measure a11y, best practices, SEO and performance** — do not eyeball them:
   `node assets/lighthouse-audit.mjs <url…>` (mobile by default, `--desktop` for a second pass).
   It reports the four category scores plus the failing audits BY NAME, and blocks under 50 on
   a11y / best-practices / SEO. Performance is recorded, never the sole blocker — house-rule
   row 15. Then judge what Lighthouse cannot: is the alt text *meaningful*, is a ghost button
   legible in BOTH states, are tap targets comfortable and not merely ≥ 44px.
5. **Regression**: nothing adjacent broke (header not wrapping, no leftover template hijack,
   kit/global CSS intact).
6. **Built locally?** Name the world per row; some have no production arm. Run the pass locally
   first and compose both. `references/house-rules.md` → "The three worlds".

## Output Contract
Return a short checklist with PASS / FAIL / UNVERIFIED / N/A + the evidence (grep counts, the four
Lighthouse scores per page, the container-audit counts) per item, house-rule rows included, then
what only the user can confirm visually, then follow-ups. UNVERIFIED is not a pass. If anything
failed, the orchestrator must NOT report done. A row with no arm in your world is UNVERIFIED.

## References
- `references/house-rules.md` — per house rule: the server-side method, and whether it is
  automatable, needs the user's eyes, or is Divi-unverified.
