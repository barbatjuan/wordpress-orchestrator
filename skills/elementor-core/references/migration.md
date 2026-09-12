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

## The four things the migration plugin does not know

It packages a WordPress. It has no idea this framework was ever here. All four of these ship a
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

**2. EVERY VISIBILITY SWITCH TRAVELS, and they hide different things.** These are options, options
are rows in the database, and the database is what a migration copies. Each one is set to a
sensible value for a site under construction and to a catastrophic one for a site being handed
over — and the site looks perfect either way, because what they hide is hidden from you too.

**`blog_public`.** Zero is WordPress's "discourage search engines", which a local site is often
built with, and it is carried into production verbatim: the site is delivered looking perfect and
stays invisible for weeks. Set it before the export with `es_indexing_state()` reading it back, and
confirm after over HTTP by reading a page's `<meta name="robots">`, which carries
`noindex, nofollow` when the option is zero.

**`woocommerce_coming_soon`, and this one was found the hard way** (2026-09-11, on the live host).
WooCommerce has shipped Launch Your Store since 9.1: a fresh install starts at
`woocommerce_coming_soon = yes` and only flips when a human finishes the onboarding wizard. A build
script never finishes that wizard. So the store arrives at the destination behind WooCommerce's
own placeholder.

MEASURED on the destination, and the numbers are the reason this is worth a paragraph: the home
page answered 200 with 28 Elementor elements and its real `<h1>`, `/nosotros/` 200 with 26,
`/contacto/` 200 with 14, the custom 404 fired correctly — **ten of the twelve URLs probed
rendered their own content** — while `/tienda/` and `/carrito/`, the other two, answered **200 with
zero Elementor elements and the `<h1>` "Great things are on the horizon"**, carrying
`woocommerce-coming-soon` on the body class. With
`woocommerce_store_pages_only = yes` the placeholder covers ONLY the store, so every page a person
naturally clicks first is fine. A migration can be flawless and still hand over a shop nobody can
buy from.

Check both before exporting, and check them again over HTTP after. The HTTP arm is cheap and
unambiguous: a `woocommerce-coming-soon` body class on any store URL is a FAIL, and it does not
need a login to see.

**Not `/robots.txt`.** Measured on a live site: with `blog_public` = 0 and Yoast active, robots.txt
served `Disallow:` — allow everything — because Yoast filters it and replaces core's output, while
the page meta correctly said `noindex`. Reading robots.txt for this question gives a false pass on
any Yoast site. Row 23.

**3. The destination's runtime is not the one QA ran on.** An older Elementor on production refuses
controls the build wrote, so the page renders wrong while every other check stays green.
`es_build_fingerprint()` records the PHP, WordPress, Elementor and Elementor Pro versions the build
was verified against; row 34 compares them.

**4. THE MIGRATION KILLS THE CONNECTOR, in SIX layers, and every one of them reports success.**
Measured across two real imports (grey-mule, 2026-09-10 and 2026-09-11). This is the trap that
bites hardest, because it takes the agency's access away at the exact moment the new site needs
work — installing what the archive left out, verifying, fixing. Peeling one layer reveals the next,
and each new layer only becomes visible once the one above it is fixed.

| # | Layer | Why it breaks | What it answers |
|---|---|---|---|
| 1 | `active_plugins` | the SOURCE's list arrives, and a local build never had the connector in it | plugins deactivated; their FILES untouched on disk |
| 2 | the credential | the agent user and its token live in the DATABASE, which was replaced | `/wp-json/mcp/bridge` → **401**, not 404 |
| 3 | the MCP route | the server registration is configuration, also in the database | `/wp-json/mcp/novamira` → **404**, while `/wp-json/novamira/v1/*` is registered and answers 403 |
| 4 | build mode | `WP_ENVIRONMENT_TYPE` and `AMB_ALLOW_PHP_EXEC` live in `wp-config.php`, which this ritual EXCLUDES on purpose | the site reads as `production`, so typed writes and PHP execution are never registered |
| 5 | the client's tool list | the capability tier is read ONCE, when the connector is added | the site advertises nine abilities and the client holds four; disabling and re-enabling the connector does not refresh it |
| 6 | the domain lock | the enable flag is an OPTION, so the source's answer to "are abilities on here" overwrites the destination's | the upload endpoint answers **403 `novamira_disabled`** |

**The layers fail in ascending order of disguise.** Reactivating the plugins fixes the first and
looks like it fixed everything: the plugin page says Enabled, the endpoint resolves, the status
screen reports the pairing it inherited from the source site. Measured on the day: the WordPress
panel said connected, the client's connector list said `connected`, the call said
*"connection was invalidated"*, and the reconnect tool refused with *"is connected; only a failed
server can be reconnected"* — **three systems reporting three different things about one dead
connection**, and the one tool that could have repaired it declined BECAUSE the status lied.

Layer 5 is the same disease one level up, and it is easy to lose an hour in: turning build mode on
flips the site to `staging` and the bridge starts advertising `amb/execute-php` immediately, while
the client keeps serving the four read tools it cached at connect time. **Turn build mode on BEFORE
adding the connector**, or remove and re-add it afterwards. Toggling it off and on within a session
is not enough — measured.

**Layer 6 is not a defect and must not be "fixed".** `novamira_is_enabled()` decides from
`novamira_ai_abilities_domain`, and that is an option — a row in the table the import replaces.
The security model is working exactly as designed: copying a database must not carry the right to
execute AI abilities onto another host. A migration trips it because a migration is that copy.
Re-enabling is a deliberate per-domain decision a human makes in the plugin's own UI, and
automating past it would empty the control of meaning. Document it; do not route around it.

**Measured, and not the shape the phrase "domain lock" suggests.** On the destination the option
read `false` — ABSENT, not set to some other domain. A local build never turns the abilities on,
so what the archive carries is the ABSENCE of the flag, and it overwrites a destination where a
human had switched it on. The 403 therefore means "never enabled here", not "enabled somewhere
else", and the direction matters: **a host that was working stops working, and nothing in the
archive looks wrong.** The remedy is the same either way — a person re-enables it — but expect to
find nothing rather than to find a stale value.

The remedy for 2 and 3 is not a reconnect, it is a RE-PAIR: the destination mints a new credential
against the database it actually has now, and that new URL is added to the client fresh. Removing
the stale entry first is part of it — it carries the old pairing inside, which is what keeps every
status green.

**Carry the connector as a mu-plugin and layer one disappears**, for exactly the reason the sandbox
exclusion is a mu-plugin: `active_plugins` cannot deactivate what was never in it. The file lives
at `wp-content/mu-plugins/`, filters `option_active_plugins`, and **checks `file_exists` before
adding each path** — the authoring site does not have those plugins installed, and naming a path
WordPress cannot load would fatal every request. Everything below layer one is credentials,
configuration and a deliberate human gate: plan for them rather than discover them. A migration
that is otherwise perfect still hands back a site nobody can reach.

Worth copying rather than retyping, for the same reason the sandbox filter is — here the failure
mode is worse than silence. `wp-content/mu-plugins/novamira-keep-connector.php`:

```php
add_filter( 'option_active_plugins', function ( $plugins ) {
    if ( ! is_array( $plugins ) ) {
        return $plugins;
    }
    $keep = array(
        'agency-mcp-bridge/agency-mcp-bridge.php',
        'novamira/novamira.php',
    );
    foreach ( $keep as $plugin ) {
        if ( in_array( $plugin, $plugins, true ) ) {
            continue;
        }
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
            continue;
        }
        $plugins[] = $plugin;
    }
    return $plugins;
} );
```

Drop the `file_exists` check and the authoring machine — which does not have these plugins
installed — fatals on every request, including wp-admin. The check is what makes the file inert
where the plugins are absent and active where they are present, which is the whole behaviour
wanted from something that travels inside the archive.

## Getting the archive to the host

**Carry the plugins. The first real run excluded them and it was a mistake** — corrected here
because this file gave the wrong advice and a reader would have repeated it.

The reasoning that produced `--exclude-plugins` was: an archive cannot travel through a
conversation, so make it small enough that it might. That premise was already dead when the flag
was used. The constraint is not the archive's size, it is that **base64 through the context window
is not a file transport at any size** — and there is a real one.

MEASURED (2026-09-10, LocalWP → Hostinger): the FULL archive, ~250 MB with every plugin in it,
reached the host in **57 s, about 4.3 MB/s**, posted to Novamira's `/novamira/v1/upload` with
`novamira_sign_upload_payload()` and an `x-novamira-upload-token` header. The bytes go over HTTP
from disk; they never enter the conversation. Nothing about that route cares whether the file is
5 MB or 250 MB.

So the trade the exclusion bought was never needed, and what it cost was severe: the destination
arrives with `_elementor_data` in every page and **no Elementor to render it** — every URL answers
200 and every body is empty. An excluded-plugins archive is not a migrated site, it is a database
waiting for a second, manual, undocumented installation step. If you ever must exclude them,
re-installing and re-licensing is part of the ritual, not an afterthought.

**The bridge cannot do this, and that gap is worth naming.** The Agency MCP Bridge has no
file-transport ability at all. `amb-media-upload` is not one: it targets the media library and
its payload travels through the conversation, so the context window is its ceiling. `amb-execute-php`
can WRITE a file the server already has, and can even stand up an ephemeral receiver — which is
building a transport rather than using one, and leaves something on a client's disk that has to be
deleted and verified gone. Use Novamira's upload endpoint.

**And that endpoint is the first casualty of layer 6.** A human enables the abilities on the host,
the upload works — and then the next import replaces the option that said so, because a local
source has never had them on. The channel you would use for the NEXT migration is switched off BY
the previous one. Plan the re-enable as a step, not as a surprise.

## After the import

**Save Settings → Permalinks once — and this paragraph used to overstate it.** The rewrite rules
live in a file, not the database, so they were never in the export; on nginx they never existed.
This file called that "the most likely production-only failure of a migration", and the first real
migration did not reproduce it: **16 routes answered 200 with nobody having touched permalinks** —
pages, the four legals, a WooCommerce product and three product categories, with a missing URL
correctly 404. All-in-One runs `Ai1wm_Import_Permalinks` at priority 170 of its own import chain
and flushes them itself.

Two reasons that is not yet permission to drop the step. The source and destination carried the
SAME `/%postname%/` structure, so nothing exercised a structure CHANGE; and the destination was
nginx, where there is no `.htaccess` for the flush to have to write. Save them anyway — it costs
one click and covers both untested cases — but report it as a precaution rather than as the
failure this file used to predict. Row 25 still measures the outcome, which is the part that
matters.

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

**The import half is now exercised too** (2026-09-11, LocalWP `prueba1` → a real Hostinger staging
host). What the run proved, and what it did not:

- **Trap 1 closed end to end.** The sandbox is absent on the live host — `404`, with a random-name
  calibration probe also `404`, so it is not a soft-404 — and `mu-plugins/novamira-exclude-sandbox.php`
  is present, so the destination now protects its own future exports. Source disk → archive → host.
- **Trap 4 was discovered BY the run**, and is the reason this list grew from three to four.
- **The permalink paragraph was corrected rather than confirmed**, above.
- **Trap 2 (`blog_public`) and trap 3 (runtime) looked unproven, and one of those readings was
  simply wrong.** `blog_public` genuinely went untested: both sites carried 1, so nothing exercised
  the carry-over of a zero. But "no version skew existed" was not a measurement — nobody had read
  the destination's PHP. The second run did, on the SAME pair of hosts, and found 8.2.29 against
  8.3.33. The skew had been there the whole time. See the second run below, and note the shape of
  the mistake: an unmeasured value reported as a finding rather than as a gap.
- **What broke, and it is worth more than what worked:** the archive was exported
  `--exclude-plugins` to get it from 238 MB to 5 MB, so the destination arrived with
  `_elementor_data` in the database and no Elementor to render it — every page 200 and every body
  empty. **That exclusion was a mistake, not a trade** — see "Getting the archive to the host": the
  transport it was protecting had already been measured carrying the full 250 MB in 57 s, and the
  flag was used out of habit rather than need.
- **Layers 5 and 6 were discovered on the retry** (2026-09-11), which is why the connector table
  grew from four rows to six. Neither is visible until the layer above it is fixed, and layer 6 is
  correct security behaviour rather than a defect.

**And then the whole ritual was run again, carrying everything** (2026-09-11, same pair of hosts,
250,051,200-byte archive, 10,717 entries, `--exclude-tables=wp_users,wp_usermeta`). This is the run
that turned the list above from advice into measurement.

- **The keep-connector mu-plugin travelled and is loaded, and the export really did lack the
  plugins** — but read the next bullet before calling that a proof of anything. `prueba1` has
  neither `agency-mcp-bridge` nor `novamira` on disk, and grepping the `.wpress` itself confirms
  it: the string `agency-mcp-bridge` occurs in that archive exactly twice, both times inside the
  mu-plugin's own source, and never in an `active_plugins` value. The destination registers `mcp`
  and `novamira/v1` in `/wp-json/`, `/wp-json/mcp/bridge` answers 401 rather than 404, and
  `novamira-keep-connector.php` is present in `WPMU_PLUGIN_DIR`.
- **`get_option( 'active_plugins' )` CANNOT verify this, and reading it back is how you fool
  yourself.** `get_option()` applies the `option_{$name}` filter, which is the very filter the
  mu-plugin installs — so the value it returns names the connector whether the database does or
  not. Read `wp_options` through `$wpdb` if you want the stored value. Measured on the destination
  a day later: the table itself now lists both plugins, even though the archive proves the import
  did not carry them. **The injected entries get baked in by the next write**, because activating
  or deactivating any plugin in wp-admin builds the new list from the FILTERED value and saves
  that. So the mu-plugin becomes redundant after the first visit to the plugins screen — and the
  evidence that it ever did anything disappears at the same moment. What stays established is
  narrower than it looks: the archive lacked them, the file travels, the file loads. Whether the
  first post-import request was served by the filter or by a human clicking Activate is not
  something `active_plugins` can be asked afterwards. **Measure it during the import window or not
  at all.**
- **CONFIRMED the next day by the only witness who could settle it.** Asked directly whether they
  had activated anything by hand in wp-admin after the restore, the operator answered no. With the
  archive proving the import did not carry the plugins and nobody having clicked Activate, the
  filter is what served them — and some later write, not a human, baked the injected entries into
  the table. Layer one is closed by the mu-plugin, and this is the sentence that earns the claim.
- **LAYER ONE DOES NOT ONLY HIT YOUR CONNECTOR, and that is the part nobody plans for.** The
  source's `active_plugins` replaces the destination's, so EVERY plugin the destination had and the
  source did not is switched off. Measured on the host a day later: `Hostinger Tools` — the hosting
  company's own management plugin, installed and running before the import — sat installed and
  INACTIVE, and had done for a day without anyone noticing. It is not in the mu-plugin's keep list
  because nobody thinks of the host's own tooling as theirs to protect. **After any import, diff
  `get_plugins()` against the STORED `active_plugins` and read what fell out of the list.** One
  query, and it is the only thing that sees this.
- **Post ids survive, which is the property the whole design rests on.** 13 pages at ids 3–36 and
  9 products at 37–45, contiguous and unshifted, with the kit still at `elementor-kit-5` on the
  body class. `es_manifest_verify()` has nothing to drift against and `post-<id>.css` stays
  correctly named.
- **Pages render on the first request, with no rebuild step.** Ten of the twelve URLs probed, out
  of 13 published: home 200 with 28 Elementor elements, `/nosotros/` 26, `/contacto/` 14, the custom
  404 firing on an unknown URL with its own copy, `/inicio/` correctly 301 to the front page, the
  four legals and the thanks page all 200. Elementor regenerates its CSS on first render exactly as
  this file claims. The two that did NOT render their own content are `/tienda/` and `/carrito/`,
  and they are trap 2's doing rather than the migration's — see the coming-soon paragraph above.
  `/finalizar-compra/` was never probed, so it is a gap rather than a pass.
- **The destination keeps its own users.** Excluding `wp_users` and `wp_usermeta` means the login
  that existed on the host before the import is the login that exists after it — the operator ran
  the restore from wp-admin with their own account and never lost it. Without that exclusion the
  source site's user table lands on top and the host's own administrator is gone.
- **Trap 2 is now half proven, in the half nobody expected.** `blog_public` still went untested —
  both sites carried 1 — but `woocommerce_coming_soon` travelled and hid the store behind
  WooCommerce's placeholder while every other page rendered perfectly. That is the same disease,
  found in a different option, which is why trap 2 above is now written about the class rather than
  the one switch.
- **Trap 3 finally had something to compare, in the harmless direction.** The plugin FILES travel
  inside the archive, so WordPress 7.1, Elementor 4.2.4 and WooCommerce 11.1.0 are identical on
  both sides by construction — a carried migration cannot produce plugin skew, which is worth
  knowing because it means the fingerprint's plugin half only ever fires on an
  `--exclude-plugins` run or a hand-installed destination. **PHP is the half that does move**: the
  local build ran 8.2.29 and the host runs 8.3.33. That is the destination being NEWER, which row
  34 files as a note rather than a FAIL. The FAIL direction — a destination running something
  older than QA — is still untested.
