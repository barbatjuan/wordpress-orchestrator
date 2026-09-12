# Why the judges are shaped the way they are

**The operative briefs are not here.** They live in `agents/blind-judge-a.md` and
`agents/blind-judge-b.md`, because those files ARE the prompt: the agent definition is what the
judge actually reads, and a second copy in this directory would drift from it silently. What is
here is the reasoning behind their shape — including the parts that must never reach a judge.

Both agents carry `Read` as their only tool. No shell, no search, no browser, no way to open a file
they were not handed. That is what makes the blindness real rather than requested: earlier versions
asked a judge not to read the source, which is an honour system, and a WordPress Orchestrator mockup declares its
own axis positions in `:root` comments — exactly how `proof_axis_signature()` reads them. A judge
with a shell is a judge reading the answer.

Capture is therefore a separate step, owned by `assets/capture.mjs`, run before either judge is
launched. It also freezes the geometry, which matters for a second reason: two descriptions taken
at two viewport sizes are not comparable, and comparing them across deliveries is the entire point.

## Judge A — why forced choice

"Rate the similarity from one to ten" is answerable without looking, and the answers drift to the
middle. "Which of these came out of the same studio" is not.

**Empty or single-item corpus.** Judge A does not run, and that is reported as skipped, never as a
pass — there is nothing to recognise against on the first delivery.

## Judge A's fourth question is not a footnote

It was added after the first calibration run, where it produced the most valuable finding of the
exercise. Three pages the axis system rates as far apart turned out to share their section order,
their 1140px measure, the label above every heading, the button pair, and the whole footer
construction.

The axes vary the SKIN. Question four is the only thing in this framework that looks at what sits
underneath it — and a client comparing two deliveries sees the architecture long before they audit
a token.

**Construction constants are not tells.** Every mockup ships the same diagonal-hatch image
placeholder, because the Artifact CSP forbids remote images. Judge A names it, correctly, and it
must be discounted: real photography replaces it in the build. Section order, measure, heading
labels, button pair and footer construction are NOT discounted — those ship.

## Judge B's eight attributes mirror the eight axes, and B is never told

Ground, headline voice, air, composition, ornament, accent, silhouette and lift are the framework's
eight axes restated in perceptual language. The mirroring is what lets a written description sit
beside a measured signature later and mean the same thing.

Naming the axes to Judge B would turn a description into a compliance check — the judge would grade
the page against a system it had just been handed, which is precisely the failure this whole skill
exists to remove. So the schema is given as eight plain questions about what is on the screen, the
order is fixed, and the attribute names never change. A renamed attribute breaks every comparison
behind it.

## What calibration measured

Run on 2026-08-28 against the repo's own falsification pair, plus a third page that was one of them
with the client and copy swapped and the design untouched — the real WordPress Orchestrator failure mode.

- Judge A grouped the two same-design pages together and separated the third. Correct in both
  directions, with concrete tells: the same hatch panel starting at the same x, the same hairline
  above every title, and the same 01/02/03 numeral misalignment reproduced identically.
- Judge B's two descriptions were not interchangeable — they diverged on ground, headline voice,
  air and accent, and converged only on silhouette and lift, which are framework constants.
- Cost, before capture was split out: 97k–117k tokens and 27–71 tool calls per judge. Splitting
  capture into `assets/capture.mjs` is what removes most of that.

## The judge model was calibrated, not assumed

Both agents are pointed at a smaller model than the one that usually generates the mockup. That is
a deliberate partial mitigation of the family rule (`judge-independence.md`), and it was measured
rather than hoped for — an uncalibrated judge model degrades silently, which is the worst failure
this skill could have.

Run on the same three captures as the original calibration:

- **Judge A: correct in both directions.** Same grouping as the larger model, same discounting of
  the hatch placeholder, and the same finding that all three share their page skeleton, margins,
  eyebrow convention, CTA pairing and nav set. It also named a tell the larger model missed — the
  two-card grid reverses its wide/narrow order between the groups.
- **Judge B: eight of eight attributes reproduced** — same cool near-white ground, same
  high-contrast serif, same rust accent doing the same three jobs, same flat surface, same small
  button radius. It read the hero smaller (60–70px against 78–82px) and missed the 01/02/03 numeral
  drift the larger model caught.

So it is a valid instrument, and a slightly blunter one. It separates the pair, which is the bar.
What it does NOT do is satisfy the family rule: a smaller model in the same family is still the
same family, so these runs remain SELF-JUDGED.
