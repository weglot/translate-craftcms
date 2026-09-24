---
name: weglot-craft-build-and-qa
description: "Set up, build and test the Weglot Craft plugin locally: composer / npm install, the Vite build of the control-panel assets and which compiled files it does and does not produce (style.css, algolia.js), Node version, the headless-Craft PHPUnit suite, WEGLOT_ENV / WEGLOT_DEV and the staging hosts, the consuming Craft project. Use when asked \"lance les tests\", \"run the tests\", \"build the assets\", \"npm run build fails\", on ERR_REQUIRE_ESM, \"Vite requires Node.js\", \"how do I point the plugin at staging\", or when a compiled asset does not reflect a source change."
---

# Build, environment and QA

## Setup

```bash
composer install && npm install
```

The plugin is developed inside a Craft project at `<craft-project>/plugins/weglot`, installed as a path repository; the project's `.env` (`../../.env`) configures the Weglot hosts. `vendor/` and `node_modules/` are gitignored and survive a branch switch — after checking out a branch whose `composer.lock` differs, run `composer install` again.

## Environment variables

| Name | Read by | Effect |
|---|---|---|
| `WEGLOT_ENV` | `HelperApi::getEnvironment()` (`src/helpers/HelperApi.php:38`) | `staging` → `*.weglot.dev`, `env_us` → `*.weglot.us`, other → production. **The way to target staging** |
| `WEGLOT_DEV` | same, `:47` | staging only if a non-boolean, non-numeric string — `true` / `1` are ignored (`.claude/memory/gotchas/weglot-dev-env-normalization.md`) |
| `WEGLOT_API_URL_STAGING`, `WEGLOT_CDN_URL_STAGING` | `:62`, `:76` | V1 API / CDN hosts in staging |
| `WEGLOT_DASHBOARD_URL_STAGING` | nothing | present in some `.env` files, unused |

Hand-made API requests: `.claude/memory/gotchas/weglot-dev-hosts.md`.

## Assets

```bash
npm run build   # vite build → src/resources/
npm run dev     # vite build --watch
```

Vite 7 needs **Node ≥ 20.19 or ≥ 22.12**; on older Node (22.5 measured) the build dies with `ERR_REQUIRE_ESM … vite.config.js`. Compiled files are **committed** in `src/resources/`.

What the build produces vs what is served (checked 2026-09-24):

| Source | Vite output (`vite.config.js`) | File actually loaded | Loaded by |
|---|---|---|---|
| `src/resources-src/js/admin.js` | `js/admin.js` | `js/admin.js` | `src/resources/AdminAsset.php` |
| `src/resources-src/scss/admin.scss` | `css/admin.css` | **`css/style.css`** | `AdminAsset.php:27` |
| `src/resources-src/js/algolia.js` | **not a Vite entry** | `js/algolia.js` | `src/services/FrontEndScriptsService.php:168` (frontend, when `enableAlgolia`) |

So:
- A SCSS change reaches the CP only once its compiled CSS is in `css/style.css`; the build writes `css/admin.css`, and the step that turns one into the other is not in the repo. `css/style.css` does contain the compiled `admin.scss` (e.g. `.weglot-hidden`, `fa1d8d3`). Ask the maintainer before changing the build.
- `src/resources/js/algolia.js` is maintained separately from `src/resources-src/js/algolia.js`, and the two differ today (flagged on #47). Edit the served file, and keep the source in step.
- `css/admin-bak.css` is loaded by nothing.
- `src/resources/vendor/` (selectize, select2, xhook) is vendored by hand.

Commit the compiled file with its source change in the same commit, as `f7f8929` / `fa1d8d3` do.

## Tests

How to write one: `.claude/memory/testing/writing-tests.md`.

```bash
composer run test                                        # all of tests/
./vendor/bin/phpunit tests/unit/                         # what CI runs
./vendor/bin/phpunit tests/unit/services/SlugServiceTest.php
./vendor/bin/phpunit --filter testName
```

Last full run: `OK, but there were issues! Tests: 181, Assertions: 313, PHPUnit Deprecations: 2` (2026-09-24, PHP 8.4). No database, no network: the bootstrap boots a headless Craft app (`tests/bootstrap.php`).

## Validation before a PR

`/deploy-check` — every gate, including those CI skips. For anything user-visible, also check it by hand in the consuming Craft project: CP settings screen (V1 `wg_…` key and V2 key), a translated page (`/fr/…`), the switcher, a nested translated slug, an excluded URL.

## When NOT to use this skill

- A failing behaviour to explain → `/weglot-craft-debugging-playbook`.
- Updating the scoped Weglot libraries → `/vendor-update`. Shipping → `/release`.

## Provenance and maintenance

| Fact | Re-verify |
|---|---|
| Vite entries | `grep -n "resolve(__dirname" vite.config.js` |
| Loaded CP assets | `grep -n "'css/\|'js/\|'vendor/" src/resources/AdminAsset.php` |
| Algolia file served | `grep -n "algolia.js" src/services/FrontEndScriptsService.php` |
| Algolia source vs served drift | `cmp -s src/resources-src/js/algolia.js src/resources/js/algolia.js && echo same || echo differ` |
| Vite Node requirement | `npx vite --version` (prints the warning on an unsupported Node) |
| Env var reads | `grep -n "App::env" src/helpers/HelperApi.php` |
| Test count | `./vendor/bin/phpunit tests --no-coverage \| tail -3` |
