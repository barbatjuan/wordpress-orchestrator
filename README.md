# WordPress Orchestrator

Modular system for building **premium WordPress sites**, reusable across
projects (workshop, clinic, real-estate, ecommerce…) by swapping brand config and adding
domain skills. The orchestrator and bases stay the same. Two builder paths exist —
**Elementor is the proven path; Divi page builds are tested on a real Divi 5 site, with no helper
library yet** (see "Use" below).

**Principle: the agent thinks, the skills execute.** A tiny orchestrator agent decides
*which* skill runs, in what order, with what context. It holds no CSS/HTML/PHP — those live
in skills and their `assets/`.

## What's inside
```
agents/
  wordpress-orchestrator.md           # tiny router (thinks, asks, routes)
  wordpress-copywriter.md             # the real copy, in its own context window (explicit delegation only)
  blind-judge-a.md  blind-judge-b.md  # the two blind judges used by blind-judges
skills/
  _wordpress-orchestrator-framework.md # architecture overview
  project-context/                    # detect builder (elementor|divi), plugins, brand
  web-templates/                      # the library of real Plantillas, picked by Objetivo
  ux-design-system/                   # builder-agnostic visual language (tokens, motion, layout)
  html-mockup/                        # the client's maqueta, one Artifact, for approval before the build
  blind-judges/                       # two blind judges write the veredicto: professional? same hand?
  visual-verification/                # judge a rendered page by eye, in a subagent, on a capture budget
  elementor-core/                     # Elementor execution, battle-tested (+ es-builder.php)
  divi-core/                          # Divi execution: page builds tested on Divi 5, no assets/ yet
  elementor-theme-parts/              # header, footer, Theme Builder parts (Elementor Pro)
  woocommerce/                        # shop / product / side cart / templates (Elementor path only)
  wordpress-forms/                    # contact/lead forms + a PROVEN delivery, not a rendered form
  wordpress-legal/                    # legal pages from the client's real data + a banner that blocks
  wordpress-performance/  wordpress-seo/  qa-review/
  wordpress-security/                 # one mu-plugin (xmlrpc, user enumeration, headers) + what is reported
  framework-audit/                    # audits this repo itself, not a site
```

## Build flow
The agent's **first question is "new site or existing site?"** — it decides whether
WordPress is inspected at all. The design phase is builder-agnostic and needs no WordPress.

**New site (greenfield)** — nothing is written to WordPress until the build gate:
```
web-templates → ux-design-system → Claude Design (the client's lienzo) → html-mockup
→ blind-judges + visual-verification (veredicto) → client approval → [BUILD GATE]
→ project-context → elementor-theme-parts → elementor-core | divi-core → woocommerce
→ wordpress-legal → wordpress-forms → wordpress-performance / wordpress-seo
→ wordpress-security → qa-review → visual-verification
```
**Existing site** — inspect first, then route on what was actually found:
```
project-context → web-templates → ux-design-system → Claude Design → html-mockup
→ blind-judges + visual-verification → client approval → [BUILD GATE]
→ elementor-theme-parts → elementor-core | divi-core → woocommerce → wordpress-legal
→ wordpress-forms → wordpress-performance / wordpress-seo → wordpress-security
→ qa-review → visual-verification
```
Claude Design is the design skill, outside this repo. `elementor-theme-parts` is Elementor only,
`woocommerce` runs only for commerce and `wordpress-forms` only when the site takes enquiries.
`web-templates` picks a real Plantilla by Objetivo and asks you for references; `ux-design-system`
places your brand inside its Enfoque; `html-mockup` derives the maqueta from the approved lienzo; the
judges give it a veredicto; builder-core reproduces it natively; `qa-review` diffs the native build
against it.

Each phase runs in a fresh agent, and the orchestrator thread only coordinates. State lives in the
client folder's `diseno/estado.md`: every phase agent rewrites it when it closes and returns a short
summary. A resumed or new session reads that file and nothing else to know the next step and what is
pending from you.

**The build gate is a hard stop.** After the mockup is approved and before ANY write to
WordPress, the agent stops and asks for an explicit yes for that build — expect it to block
there. No mockup approval + no explicit yes → no native build. On an existing site it also
confirms each page overwrite by name. The mockup itself is the approval gate and the visual
contract; it is never imported into the builder.

Three kinds of skill: **knowledge** (`web-templates`, `ux-design-system` — decide, touch
nothing), **read-only** (`project-context`, `qa-review`, `visual-verification`, `framework-audit` —
inspect and report, never write), and **operative** (`html-mockup` produces an Artifact;
`blind-judges` writes and seals the veredicto, its judges being read-only; the rest write to the live
site behind the gate). Which skills have `references/` and what each holds:
`skills/_wordpress-orchestrator-framework.md`.

## Install

### Prerequisites

- **Claude Code** — skills and agents load from `~/.claude/` (user scope).
- **PHP on your PATH** — this framework's own audit and the Plantilla tools
  (`skills/html-mockup/assets/herramientas/`) are PHP CLI scripts. Developed and tested on 8.2; check yours with `php -v`.
- **git** — to clone, and to pull updates later.

### 1. Clone

```bash
git clone https://github.com/barbatjuan/wordpress-orchestrator.git
cd wordpress-orchestrator
```

### 2. Install the skills and agents

Run one:

**Windows (PowerShell):**
```powershell
./install.ps1
```
**macOS / Linux:**
```bash
./install.sh
```

Both copy `agents/` and `skills/` into `~/.claude/`. Skills reload live — no restart needed.
Re-run after any `git pull` that touches `skills/**` or `agents/**`: merging upstream does
**not** update your `~/.claude/`, and until you re-run it Claude Code keeps executing the older
version of the framework.

> **PowerShell users:** `cp -rf` is a Bash idiom and fails here — `cp` is an alias for
> `Copy-Item`, which has no `-rf`. Use the installer above, or
> `Copy-Item -Path skills\* -Destination "$HOME\.claude\skills" -Recurse -Force`.

The copy overwrites in place and, by default, does **not** delete files that were removed upstream,
so a skill or reference deleted from this repo keeps living in your `~/.claude/` until you remove
it. That is not cosmetic: a retired template left behind can still be offered as a current one.
To list the leftovers:

```bash
diff -rq skills ~/.claude/skills | grep "^Only in"
```

To refresh what this repo ships, run the installer with `--clean` (`./install.sh --clean`,
`./install.ps1 --clean`): for every skill folder, top-level file under `skills/` and agent file the
repo carries, it removes that same-named entry from `~/.claude` and copies the repo's version fresh,
so a file retired *inside* a framework skill stops lingering. Everything the repo does not name — your
other skills and agents — is left untouched. The price: a whole skill or agent the repo retires by name
is **not** removed (the installer keeps no manifest), so delete it by hand.

(Or symlink the folders into `~/.claude/` if you prefer live edits and exact mirroring.)

### 3. Verify the install

```bash
php skills/framework-audit/assets/framework-audit.php
```

Expect **`0 FAIL`** on a fresh clone: there is no build step, every file the audit reads is
committed.

WARN rows are informational and do not block. The full offline test chain lives in
`CONTRIBUTING.md` under "Testing a change".

## Use
In Claude Code, ask the orchestrator to drive a build:
> "Use the **wordpress-orchestrator** to redesign the home and shop of this WordPress site."

It asks whether the site is new or existing first. On an **existing** site it runs
`project-context` up front (detects Elementor vs Divi, WooCommerce, theme, brand) and routes
on what it reports. On a **new** site it skips straight to the builder-agnostic design phase
and runs `project-context` later, at the build gate, once there is a connected WordPress
target to inspect. It asks you anything ambiguous, stops at the build gate for your explicit
yes, then builds and verifies server-side. You can also invoke a skill directly by name
(e.g. `elementor-core`, `woocommerce`) — the operative skills enforce the build gate
themselves.

Requirements: an MCP connector for the target site that can execute PHP on it. Two work today:
the **NovaMira** connector and the agency's own **Agency MCP Bridge** (`amb-execute-php`); the
framework is not tied to either, and the skills name NovaMira only because it came first. One
step still needs NovaMira: moving a large file such as a migration archive, because the bridge has
no file transport (`skills/elementor-core/references/migration.md`). The connector is per-site;
give it to the agent. A new site needs it from the build gate onward; an existing site needs it up
front, because `project-context` inspects the site first. The design phase of a new site needs none.

**Elementor is the proven path.** Divi page builds are tested on a real Divi 5 site (from D4
shortcodes; four confirmed gotchas in `divi-core/references/gotchas.md`). Still true: `divi-core`
has no `assets/` and no helper library, `elementor-theme-parts` is Elementor Pro only, and
`woocommerce` has no Divi path (the skill stops on a Divi site). `qa-review` reports the rows whose
checks read Elementor artefacts as UNVERIFIED on a Divi build. Verify each Divi step on the site and
record what you learn in `divi-core/references/gotchas.md`.

## Contributing (grow the gotchas)
The `references/gotchas.md` files are the real value. When something surprises you on a
build, add a confirmed entry (symptom → cause → fix → "do NOT"). Keep `SKILL.md` bodies
short (the audit enforces the word ceiling); put detail in `references/` and code in `assets/`.
The rules are in `CONTRIBUTING.md`.

1. `git checkout -b gotcha/<short-name>`
2. Edit the relevant skill / reference / asset.
3. `git commit` (conventional commits) and open a PR.

## Per-project extension
Add domain operative skills next to these (`build-home`, `build-product-page`,
`real-estate-listings`, `clinic-booking`…). Reuse the same orchestrator, bases, and assets.

## License
Apache-2.0 (skills). Adapt the palette/brand tokens freely.
