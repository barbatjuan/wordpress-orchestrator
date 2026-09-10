# Local to production

**This is one of two routes, and the optional one.** Building directly on the client's WordPress
through the NovaMira connector is the default and stays exactly as it was — there is nothing here
to do on that route, because nothing moves. Everything below applies only when a site was built
locally first.

On this route a site is built on a local WordPress with full freedom — wp-admin, plugins, the Elementor UI,
media, menus — and moved to production as a copy of itself.

**The local environment is LocalWP and the copy is made by All-in-One WP Migration.** Neither is
this framework's job, and neither should become one: both are mature, both are used by hundreds of
thousands of sites, and a bespoke ritual here would be a second implementation of a solved problem.
LocalWP's Blueprints are the golden image; the plugin's export and import are the migration.

Why a copy rather than re-running the build script against production: a rebuild carries only what
the script generates. Plugin settings, WooCommerce configuration, an edit made in the Elementor UI,
the media library, the menu — none of it is in the script. A local WordPress you can only edit
through a build script is not a local WordPress you can do anything in.

## The three things the migration plugin does not know

It packages a WordPress. It has no idea this framework was ever here. All three of these ship a
site that looks perfect.

**1. The sandbox travels in the export unless something stops it.** `es_sandbox_dir()` is
`WP_CONTENT_DIR . '/novamira-sandbox'` — inside wp-content, which All-in-One packages whole. So
`es-builder.php` lands on the client's server, and measured on a live host it answers a direct
request with an empty `200`: served, not absent.

Two defences, and use both. They fail in different ways, which is the point.

**The belt — a mu-plugin, so the exclusion is mechanical rather than remembered.** All-in-One
exposes `ai1wm_exclude_content_from_export` for entries directly under wp-content:

```php
add_filter( 'ai1wm_exclude_content_from_export', function ( $paths ) {
    $paths[] = 'novamira-sandbox';
    return $paths;
} );
```

Three details decide whether it works, and two of them fail silently. The path is **relative** to
wp-content, so it is `novamira-sandbox` and not a full path. It carries **no trailing slash** —
`novamira-sandbox/` is a different entry and simply never matches. And it belongs in
`wp-content/mu-plugins/`, which cannot be deactivated by accident and survives a theme switch —
and which travels in the export itself, so the site protects its own future exports.

MEASURED, on All-in-One 7.x free, WordPress 7.1, PHP 8.2.29, by running the plugin's own iterator
chain from `class-ai1wm-export-enumerate-content.php` and counting what it would package. With the
sandbox holding `es-builder.php` and one file pasted in to debug something once:

| Entry passed to the filter | Sandbox files packaged | |
|---|---|---|
| *(no filter)* | 2 | **both travel** |
| `novamira-sandbox` | 0 | excluded |
| `novamira-sandbox/` | 2 | silently not excluded |
| `/novamira-sandbox` | 2 | silently not excluded |
| `wp-content/novamira-sandbox` | 2 | silently not excluded |

**Three of the four ways you would naturally write it are no-ops, and none of them complains.** The
bare relative name is the only spelling that works, which is why the snippet above is worth copying
rather than retyping.

**MEASURED AGAIN ON THE ARCHIVE ITSELF (2026-09-10), which is what the paragraph above could not
do.** With the Unlimited Extension installed, `wp ai1wm backup` produces a real `.wpress`, and
`wp ai1wm browse-backup` lists what is inside it. Two exports of the same site, minutes apart, the
only change between them being the mu-plugin below:

| Export | Bytes | Entries | `novamira-sandbox` |
|---|---|---|---|
| no filter | 249,742,684 | 10,709 | **`novamira-sandbox\es-builder.php`, 167.93 KB — present** |
| filter installed | 249,572,128 | 10,709 | **absent** |

Diffing the two listings: **exactly one path leaves and exactly one enters.** Out goes
`novamira-sandbox\es-builder.php`; in comes `mu-plugins\novamira-exclude-sandbox.php`, which is
the filter travelling in the export exactly as this section claims it does — so the copied site
protects its own future exports. Nothing else moved.

Two notes from running it. The **URL Extension blocks the CLI when it is out of date** — "Export
failed: URL Extension is out of date" — and it is not needed for a file export; deactivate it.
And the second export took 10.6s against the first one's 2m8s for the same entry count and the
same size class: OS file cache, not a smaller archive.

One thing this still does NOT prove: that the archive IMPORTS cleanly on a destination host.
And the purge stays as the arm that blocks regardless — a filter is one line a plugin update or a
careless edit can take away, while an empty directory cannot ship what it does not hold.

**The braces — purge before the export, and ordering is the whole rule.** `es_sandbox_purge()` then
`es_sandbox_report()` returning empty, immediately before exporting: a sandbox emptied and then
used once more for one last fix is a sandbox that ships. This is what covers the case where the
filter was never installed, was installed with a trailing slash, or stopped matching after a plugin
update — none of which announce themselves.

qa-review row 33 carries the production probe, including why a 403 is not a pass and why an empty
200 is the trap.

**2. `blog_public` travels.** Zero is WordPress's "discourage search engines", which a local site is
often built with, and it is carried into production verbatim: the site is delivered looking perfect
and stays invisible for weeks. Set it before the export with `es_indexing_state()` reading it back,
and confirm after over HTTP by reading a page's `<meta name="robots">`, which carries
`noindex, nofollow` when the option is zero.

**Not `/robots.txt`.** Measured on a live site: with `blog_public` = 0 and Yoast active, robots.txt
served `Disallow:` — allow everything — because Yoast filters it and replaces core's output, while
the page meta correctly said `noindex`. Reading robots.txt for this question gives a false pass on
any Yoast site. Row 23.

**3. The destination's runtime is not the one QA ran on.** An older Elementor on production refuses
controls the build wrote, so the page renders wrong while every other check stays green.
`es_build_fingerprint()` records the PHP, WordPress, Elementor and Elementor Pro versions the build
was verified against; row 34 compares them.

## After the import

**Save Settings → Permalinks once.** The rewrite rules live in a file, not the database, so they
were never in the export — and on nginx they never existed. Until that is done, every URL except
the front page returns 404. It is the most likely production-only failure of a migration, and row
25 is where it surfaces.

## What goes in the Blueprint

The Blueprint is what every new site is cloned from, so anything missing here is missing three
hundred times, and anything wrong here is wrong three hundred times.

- **Hello Elementor plus its child theme.** The child theme is load-bearing beyond styling: it is
  where a file that registers a WordPress hook belongs, and row 22 refuses to let such a file live
  in the sandbox.
- **Elementor and Elementor Pro, licensed.** Verify the licence survives a clone before building on
  it — a licence keyed to a domain breaks exactly here, and quietly.
- **A `menu-principal` nav menu, already created.** Nothing in this framework creates one:
  `es-theme-parts.example.php` only checks it exists and omits the nav widget when it does not, so
  without it every site ships headerless until a human notices.
- **A seeded Elementor kit**, so a build overrides a known starting point rather than whatever the
  defaults were that day.
- **`blog_public` at 1** — closing trap 2 once, in the one place it stays closed.
- **Permalinks on post name.**
- **No content, no uploads, no extra users.** A demo page in the Blueprint is a demo page in three
  hundred sites, and nobody remembers putting it there.

Refresh the Blueprint deliberately and note the date and what moved — that date is the answer to
"why do sites built before March behave differently". Refreshing does not touch sites already
created, which is intended, and is also why the runtime fingerprint exists.

## What the build still has to guarantee

Two properties of the build itself, unchanged by any of this and guarded by
`tests/test-replay.php`:

1. **Stable ids.** `es_uid()` is `substr( md5( $seed . '-' . $n ), 0, 7 )`, reset per page by
   `es_uid_reset( $slug )`. No time, no randomness.
2. **A clean starting state.** `es_tokens()` caches in `static $t` and only recomputes when
   `$override` is truthy — and `array()` is falsy, so a second build in the same process silently
   inherits the previous build's palette. `es_tokens_reset()` drops the cache.

## Status

**Trap 1 is now exercised end to end on a real archive** (2026-09-10, LocalWP `prueba1`): the
sandbox WAS inside the `.wpress` with no defence, and the mu-plugin filter took it out and nothing
else — see the second measured table above. What broke on the way: the URL Extension refused the
export until it was deactivated, which is worth knowing because the error names the extension and
not the fix.

**Traps 2 and 3 and the import half are still unexercised.** No site has gone from LocalWP to a
production host through this checklist: nothing here has watched a `.wpress` land on a real server,
so the permalink flush, the `blog_public` carry-over and the runtime comparison remain specified
and unproven. Record the result here the first time one does, and say what broke rather than only
that it worked.
