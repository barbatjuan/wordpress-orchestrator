---
name: wordpress-seo
description: "Trigger: SEO, meta title, meta description, H1/H2 hierarchy, schema, structured data, sitemap, Open Graph, alt text. On-page SEO for a WordPress Orchestrator-built WordPress site."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.1"
---

# WordPress SEO

On-page SEO for the built pages. Content-first and honest; no manipulation.

## Activation Contract
Use after pages exist, or when the user asks for SEO. Works with whatever SEO plugin
`project-context` found (Yoast / Rank Math) or WordPress defaults.

**Build gate — blocking.** This skill writes to a live WordPress site. Do not run until the user
has given an explicit **yes** for THIS build. Reached directly instead of routed by the
orchestrator? Ask for that yes yourself before the first write and stop until you get it.
On an existing site, confirm every page/template you would overwrite by name first.
Sitemap submission (step 5) is an outward action to a third party — confirm it separately.

## Hard Rules
- One H1 per page — counted by `qa-review` house-rule row 12 in the front HTML of every page in
  scope. The H2/H3 outline must match the visible structure; nothing measures that, so read it.
  (verifier: `qa-review` house-rule row 12 counts the H1 tags in the front HTML of every page in scope.)
- Titles/descriptions are unique, human, and describe the page — not keyword stuffing. Verify
  server-side across the whole page set: two identical `<title>` tags is a FAIL, not a detail.
  (no verifier: no check in this repo compares per-page SEO metadata across the page set, so a duplicate title is a manual cross-page read.)
- Every meaningful image has real alt text (`qa-review` step 4 judges whether it is *meaningful*;
  Lighthouse only proves it is present). Fictional/demo content stays labeled as such.
  (verifier: `qa-review` house-rule row 13 runs Lighthouse, which reports every missing image-alt; whether the text is meaningful stays a human call.)
- Don't invent facts (addresses, reviews, credentials) to fill schema.
  (no verifier: only the client can confirm a fact is real — ask for it, never fill the gap.)

## Execution Steps
1. **Per page**: unique `<title>` (~50–60 chars) and meta description (~150–160), set via the
   SEO plugin's post meta.
2. **Headings**: verify exactly one H1 and a sensible heading tree (the builder controls the tags).
3. **Schema**: JSON-LD appropriate to the business — `LocalBusiness`/`AutoRepair`, `Product`
   (WooCommerce usually emits this), `FAQPage` for FAQ sections, `BreadcrumbList`.
4. **Social**: Open Graph title/description/image per key page.
5. **Sitemap + indexing**: ensure an XML sitemap exists and is submitted; noindex utility pages.
6. Verify the emitted tags server-side (fetch HTML, check `<title>`, meta, JSON-LD).

## Output Contract
Report per-page titles/descriptions set, heading check result, schema types added, and the
sitemap status. Flag any page where honest content is missing rather than fabricating it.
