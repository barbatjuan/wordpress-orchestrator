---
name: wordpress-forms
description: "Trigger: formulario, contact form, form plugin, lead form, contacto, form submission, recipient, consentimiento, RGPD checkbox, Elementor Forms, WPForms, Contact Form 7. Build contact/lead forms on a WordPress Orchestrator site and PROVE one message arrives."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.0"
---

# WordPress Forms

Build the site's forms, wire the recipient, and prove a real submission arrives.

## Activation Contract
After the pages exist, before `qa-review`. Needs `project-context` first: the active form plugin
decides every step below and there is no safe default to guess.

**Build gate — blocking.** This skill writes to a live WordPress site. Do not run until the user
has given an explicit **yes** for THIS build; reached directly rather than routed, ask for that
yes yourself and stop until you get it. Step 4 SENDS A REAL MESSAGE — confirm the address
separately before sending.

## Hard Rules
- **A form that does not deliver is not a form.** Send one real submission and confirm it ARRIVED.
  Rendering, validating and showing a success message looks identical from outside whether the
  mail sends or silently fails, and every lead falls in that gap.
  (verifier: `qa-review` house-rule row 18 sends a marked submission and requires it to be found in the destination inbox or the plugin's stored entries.)
- **The recipient is a named address the client reads, never a default.** Plugins fall back to
  `admin_email`, which on a fresh install is the host's or the developer's. Set it, read it back.
  (verifier: `qa-review` house-rule row 18 reads the stored recipient and FAILs when it is empty or equals `admin_email`.)
- **Consent is a required, unticked box linking a privacy page that EXISTS.** Pointing it at a 404
  is worse than omitting it: it looks compliant and collects the data anyway.
  (verifier: `qa-review` house-rule row 19 requires the checkbox to be present, required and unticked, and requests its privacy link server-side.)
- **Never invent the recipient, the company data or the legal text.** Ask. A guessed address is a
  lead going nowhere; text you wrote is the client's liability.
  (no verifier: nothing here can tell a real address from a plausible one — only the client can.)
- **Never a form in the hero.** It belongs in the closing conversion band.
  (no verifier: nothing inspects a built hero for a capture form; the `TGL-LEAD-FORM` default is a starting point, not a gate.)

## Execution Steps
1. **Capability**: which plugin is active — Elementor Pro Forms, WPForms, CF7, Gravity, Fluent?
   None active: say so and stop. Never build a decorative form.
2. **Build** with the minimum honest field set (name, email, message) plus the consent box, using
   the site tokens. A form is not a place for new styling.
3. **Recipient + subject**: set both, then read both back from the stored config.
4. **Delivery test**: submit once with a unique marker, then confirm arrival — in the client's
   inbox, or in the plugin's stored entries. Report which of the two you proved, never both.
5. **Report** the marker, where it landed, and the recipient you read back.

## Output Contract
Plugin detected, form locations, field set, recipient read back from storage, consent box plus its
privacy URL and status code, delivery marker and where it was found. Anything unproved is
UNVERIFIED and named — a form is as good as its last confirmed delivery.

## References
- Pairs with `wordpress-legal` for the privacy page the consent box links, and with
  `elementor-core` / `divi-core` for placing the widget.
