---
name: wordpress-security
description: "Trigger: seguridad, security, hardening, xmlrpc, user enumeration, security headers, HSTS, two factor, 2FA, plugin vulnerable, scanner findings. Close the findings external scanners repeat on a WordPress Orchestrator-built site, and report the rest to a human."
license: Apache-2.0
metadata:
  author: "juan"
  version: "1.0"
---

# WordPress Security

Closes the common scanner findings with one mu-plugin and reports what only a human can decide.
Runs after the pages exist and before `qa-review`.

## Activation Contract
Use at hand-off, or when the user brings a scanner report. Read `project-context` first: it says
whether Jetpack is active; ask whether the WordPress mobile app is used.

**Build gate — blocking.** This skill writes to a live WordPress site. Do not run until the user
has given an explicit **yes** for THIS build. Reached directly instead of routed by the
orchestrator? Ask for that yes yourself before the first write and stop until you get it.
The mu-plugin is PHP outside the sandbox, so it also needs the separate authorization in the Hard Rules.

## Hard Rules
- Auto-fix only what `assets/es-security.php` does. Everything else is reported and never changed.
  (no verifier: nothing compares what a session changed against this list; the Output Contract is the record.)
- Before deploying `es-security.php` to `wp-content/mu-plugins/`, ask the human for explicit
  authorization naming that exact file and that path, then stop and wait. On refusal install
  nothing, hand the file over for manual install, and report every default fix as NOT applied.
  (no verifier: nothing records whether authorization came before the write; the hand-off report is the only record.)
- A plugin being installed proves nothing: headers sent from PHP do not reach cached pages or static
  files, so the verdict is the SERVED response.
  (verifier: `qa-review` house-rule row 38 reads the headers of the served home page.)
- HSTS stays short and never carries `includeSubDomains` or `preload`: both are hard to undo.
  (verifier: `tests/test-security.php` asserts the header value.)
- The REST users route closes only for anonymous requests, because the connector authenticates over REST.
  (verifier: `tests/test-security.php` asserts that a logged-in request keeps every endpoint.)
- xmlrpc stays on when Jetpack or the WordPress mobile app is in use; say so in the report.
  (no verifier: the mu-plugin sees Jetpack, but nothing detects the mobile app, so ask the client.)
- The connector user's application password is revoked, or the user removed, before hand-off.
  (verifier: `qa-review` house-rule row 41 checks the connector user.)
- Outdated core, plugins and theme are listed with versions. A human decides; never update here.
  (verifier: `qa-review` house-rule row 42 lists the versions and flags the outdated ones.)

## Execution Steps
1. With that authorization, deploy `assets/es-security.php` to `wp-content/mu-plugins/` through the
   connector (create the directory if missing). It needs no settings; deleting the file undoes it.
2. Fixed by default: xmlrpc off (Jetpack excepted), anonymous `/wp/v2/users` closed, `?author=N`
   answered 404, users out of the sitemap and oEmbed, one generic login error, file editor off, and
   `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, plus HSTS over HTTPS.
3. Report, never fix: outdated core/plugins/theme; administrator count, an `admin` login, an author
   slug equal to the login; SSL expiry; whether a backup exists; `WP_DEBUG_DISPLAY` on or a public
   `debug.log`; PHP executing in `uploads/` (block it in `.htaccess`, report only on nginx).
4. Recommend the Two-Factor plugin for administrators. Each human enrolls their own device, and
   application passwords bypass 2FA, which is why step 5 matters.
5. Revoke the connector user's application password, or remove the user, at hand-off.
6. Verify with `qa-review` rows 38 to 42 against the served site.

## Output Contract
Report what the mu-plugin closed, what was found and left to a human (with versions), and what
could not be verified. Out of scope: login rate limiting, hiding the WordPress version,
firewall or malware scanning.
