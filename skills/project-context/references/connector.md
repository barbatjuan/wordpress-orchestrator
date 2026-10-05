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
3. **File upload for large files.** Only the local-to-production route needs it (a migration
   archive); a page build does not (`elementor-core/references/migration.md`).
4. **Telling which connector this session holds.** Read the tool names the session actually has:
   `execute-php` + `create-upload-link` is NovaMira; `amb-execute-php` is the bridge. Then run
   `project-context` step 7 against that one.

## What this repo records

| Need | NovaMira | Agency MCP Bridge |
|---|---|---|
| Execute PHP | `execute-php` (`project-context` step 7) | `amb-execute-php`; advertised only when the site is in build mode: `WP_ENVIRONMENT_TYPE` staging and `AMB_ALLOW_PHP_EXEC` in `wp-config.php` (`migration.md`, layer 4 and 5) |
| Library persists | Its loader `require_once`s every `*.php` in `wp-content/novamira-sandbox/` on EVERY request, and stops when `.crashed` exists (read from the plugin source, `gotchas.md`) | Not recorded in this repo: whether it uses the same directory or loads it. `amb-execute-php` can write a file the server already has (`migration.md`) |
| How `es-builder.php` gets there | `create-upload-link` + multipart `curl -F file=@` (`gotchas.md`, deploy pipeline step 1) | Not recorded in this repo: no file transport exists, so the library would travel through the conversation or be written by `amb-execute-php`. Confirm on the site |
| Large file upload | `/novamira/v1/upload`, 250 MB in 57 s measured (`migration.md`) | None: `amb-media-upload` targets the media library and its payload travels through the conversation (`migration.md`) |
| Detection | Tool names, `project-context` step 7 | Tool names. The REST route is `/wp-json/mcp/bridge` (`migration.md`) |
| Write rights | Not recorded in this repo | A restricted user: `unfiltered_html` is false, so kses strips `<style>` from `post_content`, and writable options are an allowlist (`divi-core/references/gotchas.md`) |

## What is NovaMira-only

The `.crashed` safe mode and `es_sandbox_state()`; `create-upload-link` and its ~20-minute token;
the `novamira_ai_abilities_domain` lock; `mu-plugins/novamira-exclude-sandbox.php`. A skill that
cites one of these names NovaMira on purpose. A statement that only needs "run PHP on the site"
says "the connector".

Identifiers stay as they are on either connector: the option `es_novamira_manifest`, the directory
`novamira-sandbox`, and the tool names above.
