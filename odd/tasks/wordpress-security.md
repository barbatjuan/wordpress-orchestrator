# wordpress-security

## Objective
Ship a `wordpress-security` skill so every site the framework builds leaves with the common
scanner findings already closed, and the rest reported to a human.

## Problem / why
Three external scans of three client sites repeat the same findings (xmlrpc enabled, user
enumeration, missing security headers, outdated plugins/core). The framework had zero coverage,
and one exposed username is the connector's own `mcp-agent` account.

## Constraints
- KISS (owner instruction): one `SKILL.md`, one single-file mu-plugin, one test file, a few
  `qa-review` rows. No settings UI, no options, no extra abstractions.
- Strict TDD: enabled (source: owner global instructions). Runner: `php tests/<file>.php`.
- Gate: `php skills/framework-audit/assets/framework-audit.php` stays at 0 FAIL; all `tests/test-*.php` green.
- Artifacts in English. No personal email anywhere.
- Delivery strategy: `single-pr`.

## Scope
Fixed by default (mu-plugin `skills/wordpress-security/assets/es-security.php`):
- xmlrpc disabled, unless Jetpack is active (skip and say so)
- REST `/wp/v2/users` closed to anonymous requests; authenticated unchanged
- `?author=N` no longer redirects to the author archive
- users provider removed from core `wp-sitemap`; `author_name`/`author_url` removed from oEmbed
- generic login error message
- `DISALLOW_FILE_EDIT` defined when not already defined
- headers: `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`,
  `Permissions-Policy`, HSTS with a short `max-age`, no `includeSubDomains`, no `preload`, HTTPS only

Reported, never auto-fixed (SKILL.md + `qa-review` rows):
- outdated core / plugins / theme
- connector user (`mcp-agent`) application password revoked or user removed at handoff
- administrator count, no `admin` login, author slug differs from login
- SSL expiry, backup exists, `WP_DEBUG_DISPLAY` off and `debug.log` not public
- PHP execution in `uploads/` blocked (`.htaccess`; report on nginx)
- Two-Factor plugin recommended for administrators (each human enrolls their own device)

Out of scope: login rate limiting, hiding the WordPress version, firewall/malware scanning.

## Tasks
- [x] T1 (delegated writer — 2+ non-trivial files) mu-plugin + `tests/test-security.php`, RED then GREEN
- [x] T2 (same writer) `skills/wordpress-security/SKILL.md`, `qa-review` rows, overview/orchestrator/README mentions; audit 0 FAIL

## Acceptance
- `php tests/test-security.php` green, observed RED first
- every other `tests/test-*.php` still green; framework audit 0 FAIL
- each hard rule in the new `SKILL.md` names its verifier or admits `(no verifier: <reason>)`

## Progress / evidence
- T1+T2 done by one delegated writer. RED observed (`0 OK / 1 FAIL`, plugin missing), then GREEN `30 OK / 0 FAIL`.
- Gate: framework audit `0 FAIL / 2 WARN / 0 JUDGE across 17 skills`; all other tests unchanged and green.
- Owner decision (2026-10-04): the framework may write PHP outside the sandbox only with explicit human authorization obtained beforehand; rule reworded in elementor-core knowledge.md, es-builder.php (comment), qa-review rows 17 and 22, and enforced as a hard rule in the new skill.
- Deviation: `?author=N` answers 404 instead of merely not redirecting.
- Not checked: behaviour on a live site (connector auth with `/wp/v2/users` closed to anonymous).
- Next: live check on one staging site, then PR.
- Independent verification (2026-10-04, tier high, RDD off): pass with findings; one bounded correction applied — host headers never downgraded, `?author=` digit/array variants, real header assertions, `geolocation` dropped, `ES_SECURITY_KEEP_XMLRPC` opt-out, last absolute-ban string reworded, rows 36–42 back inside the table. `php tests/test-security.php`: `43 OK / 0 FAIL`.
