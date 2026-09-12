---
name: wordpress-legal
description: "Trigger: aviso legal, política de privacidad, política de cookies, términos y condiciones, RGPD, GDPR, banner de cookies, consentimiento, legal pages, privacy policy, cookie banner, imprint. Build a NovaMira site's legal pages from the client's REAL identity data, and a consent banner that actually blocks."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.0"
---

# WordPress Legal

Legal notice, privacy, cookies and terms — from the client's real data — plus a banner that blocks
before it asks.

## Activation Contract
After the pages exist, before `qa-review`. Needs `project-context` (which consent plugin, if any)
and the client's identity data. Run before `wordpress-forms`, whose consent box links the privacy
page this skill writes — the other order points it at a 404.

**Build gate — blocking.** This skill writes to a live WordPress site. Do not run until the user
has given an explicit **yes** for THIS build; reached directly rather than routed, ask for that yes
yourself and stop until you get it.

## Hard Rules
- **Never invent identity data** — company name, tax id, registered address, registry entry, DPO,
  jurisdiction. Missing data means the page does NOT ship: one naming the wrong entity is worse
  than an absent one, because it looks done and nobody re-reads it.
  (no verifier: nothing here can tell a real tax id from a well-formed one — only the client can, which is why the gap is handed back rather than filled.)
- **Never copy another site's legal text.** It names their company, their jurisdiction and their
  processors. It is both wrong and someone else's work.
  (no verifier: nothing in this repo compares the text against a source; this one is on the person writing it.)
- **The cookie policy lists what the site ACTUALLY loads** — read the real third-party requests.
  Naming services the site does not use is as false as omitting the ones it does.
  (verifier: `qa-review` house-rule row 20 lists the third-party hosts the built pages request and requires each one to appear in the cookie policy.)
- **A banner that does not block is theatre.** Styling a notice while the analytics fires
  underneath it is the failure this rule exists for, and it is the normal state of most installs.
  (verifier: `qa-review` house-rule row 21 loads a page with no cookies set and FAILs if any non-essential third-party request fires before consent.)
- **Every legal page is reachable from every footer.** One nobody can reach is a file, not
  compliance.
  (verifier: `qa-review` house-rule row 20 requires each legal page to be linked from the footer fragment of every page in scope, and requests each link.)
- **Fonts are served from the client's own domain, never Google's CDN.** A page requesting
  `fonts.googleapis.com` sends every visitor's IP to a third country before any banner appears —
  no consent, no legal basis, and EU courts have fined the site owner for it. The procedure is in
  `elementor-core/references/knowledge.md`.
  (verifier: `es_font_serving_check()` warns from `es_audit_summary()`, naming the Google stylesheet when this request ENQUEUED one; a registration is not proof, since core registers `open-sans` against Google everywhere and enqueues it nowhere.)
- **Show the overwrite preflight before writing.** Existing legal pages have the longest history
  and the least appetite for a silent rebuild.
  (verifier: `es_overwrite_preflight()` prints the slugs that would be overwritten, marking the front page and any page not currently built with the builder.)

## Execution Steps
1. **Collect** the identity data, jurisdiction and real processor list. Anything missing is a
   question, not a placeholder. One at a time, then stop.
2. **Preflight** the legal slugs with `es_overwrite_preflight()` and show the block.
3. **Write** legal notice, privacy and cookies using the site tokens. Terms only when the site
   sells or contracts something — never an empty one for symmetry.
4. **Banner**: block non-essential categories until accepted, with reject as reachable as accept.
5. **Verify**: request each page and each footer link, then load one page cookie-less and see what
   fires before consent.

## Output Contract
Pages written with URLs, identity fields and their source, the processor list, the consent plugin
and its blocking behaviour, and what fired before consent. Anything unconfirmed is UNVERIFIED and
named. Incomplete legal text is a question for the client, never shipped as prose.

## References
- Pairs with `wordpress-forms` (its consent box links the privacy page this skill writes) and with
  `elementor-core` / `divi-core` for building the pages.
