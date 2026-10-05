# Connector contract

The framework reaches a site only through an MCP connector. Two are in use: **NovaMira** and the
agency's **Agency MCP Bridge**. This page says what the framework needs from one and what this repo
records about each. A cell that says "not recorded" is NOT a "no": confirm it on the site.

## What the framework needs

1. **Execute PHP on the site.** Every skill that reads or writes WordPress does it as PHP.
2. **A place where `es-builder.php` persists between calls**, and a way to get it loaded. The
   library path is `WP_CONTENT_DIR . '/novamira-sandbox'` (`es_sandbox_dir()`); a build function is
   called after an explicit `require_once` of the library (`elementor-core/references/gotchas.md`,
   deploy pipeline step 2).
3. **A way to put files on the site.** A page build needs it too: `es-builder.php` (~165 KB) and the
   builder files are uploaded (`gotchas.md`, deploy pipeline step 1). A migration archive is the
   large case (`elementor-core/references/migration.md`).
4. **Telling which connector this session holds.** No code does it; the model reads the tools it
   holds. The ids this repo records are `novamira/create-upload-link` (`gotchas.md`) and
   `amb/execute-php` (`migration.md`). Then run `project-context` step 7 against that one.

## What this repo records

| Need | NovaMira | Agency MCP Bridge |
|---|---|---|
| Execute PHP | `execute-php` (`project-context` step 7) | `amb-execute-php`, ability `amb/execute-php`; advertised only when the site is in build mode: `WP_ENVIRONMENT_TYPE` staging and `AMB_ALLOW_PHP_EXEC` in `wp-config.php` (`migration.md`, layers 4 and 5) |
| Library persists | Its loader `require_once`s every `*.php` in `wp-content/novamira-sandbox/` on EVERY request, and stops when `.crashed` exists (read from the plugin source, `gotchas.md`) | Not recorded in this repo: whether it uses the same directory or loads it. `amb-execute-php` can write a file the server already has (`migration.md`) |
| How `es-builder.php` gets there | `create-upload-link` + multipart `curl -F file=@` (`gotchas.md`, deploy pipeline step 1) | Not recorded in this repo: no file transport exists, so the library would travel through the conversation or be written by `amb-execute-php`. Confirm on the site |
| Large file upload | `/novamira/v1/upload`, 250 MB in 57 s measured (`migration.md`) | None: `amb-media-upload` targets the media library and its payload travels through the conversation (`migration.md`) |
| Detection | `novamira/create-upload-link` | `amb/execute-php`; the REST route is `/wp-json/mcp/bridge` (`migration.md`) |
| Write rights | Not recorded in this repo | A restricted user: `unfiltered_html` is false, so kses strips `<style>` from `post_content`, and writable options are an allowlist (`divi-core/references/gotchas.md`) |

The upload token lasts about 20 minutes and the connector intermittently answers "requires additional
permissions" (`gotchas.md`, orchestrator "When a build breaks mid-flight"). Both describe the
connector in general; which of the two it belongs to is not recorded in this repo.

## What is NovaMira-only

The `.crashed` safe mode and `es_sandbox_state()`; `create-upload-link`; the
`novamira_ai_abilities_domain` lock; `mu-plugins/novamira-exclude-sandbox.php`. A skill that cites
one of these names NovaMira on purpose. A statement that only needs "run PHP on the site" says
"the connector".

Identifiers stay as they are on either connector: the option `es_novamira_manifest`, the tool names
above, and the directory `novamira-sandbox`. That directory is where `es_sandbox_dir()` looks; that
the bridge keeps the library there is not recorded in this repo. QA rows 22 and 33 prove the sandbox
empty through `es_sandbox_dir()`, so if the bridge keeps it elsewhere that proof does not cover it:
confirm on the site.
