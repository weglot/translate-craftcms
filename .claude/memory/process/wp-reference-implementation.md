---
name: wp-reference-implementation
description: How to use the Weglot WordPress plugin as the reference implementation — locate it through PhpStorm MCP, never guess the path, sweep templates/admin/v2/ entirely before concluding V2 lacks something.
type: process
---

The Craft plugin is ported from the Weglot WordPress plugin (`weglot/translate-wordpress`). When a request mentions "WP" or a WordPress behaviour, compare against a local checkout of it.

- **The checkout path is machine-specific and is not recorded in the repo** (a personal path committed here was flagged on #59). Ask for it the first time, keep it in your personal memory, and verify it with `mcp__phpstorm__get_repositories`. If it does not resolve, the project is not open in PhpStorm: say so and ask. Never fall back to plain file reads against a guessed path, and never answer a WP question from memory.
- Locate code with `mcp__phpstorm__search_symbol`, then port behaviour, not WordPress APIs (hooks → Craft events, `wp_remote_*` → `Craft::createGuzzleClient()`).

## V2 lives in its own templates

All V2 admin UI of the WP plugin is under `templates/admin/v2/` (`home.php`, `dashboard.php`, `settings.php`, `section/`). The V1 entry point `templates/admin/pages/settings.php` includes `admin/v2/settings.php` early and returns when the onboarding version is not 1. V1 and V2 screens are separate files that differ in structure: V2 merges the block and URL exclusion cards and drops the Visual Editor card.

**Sweep the whole `templates/admin/v2/` directory before concluding anything is missing.** `admin/v2/settings.php` is only the onboarding screen; the dashboard quick links are in `admin/v2/home.php`. Concluding "V2 has no equivalent" after reading one file has already produced wrong implementations (V2 quick links, `d84003f`).

The WP repo's own Claude tooling (`.claude/` on its `master`) documents the shared API contract and pipeline — useful for context, but every fact must be re-verified in this repo before it is written here.

**Why:** carried over from the previous `CLAUDE.md` § "WordPress Plugin as Reference Implementation".
**How to apply:** any port of, or comparison with, WordPress plugin behaviour.
