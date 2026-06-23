# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Workflow

### Before Writing Any Code

For any **significant task** (touches multiple files, introduces a new feature, or changes existing behavior), Claude must:

1. **Propose an action plan** — list the files to modify, the approach, and any trade-offs
2. **Wait for explicit approval** before writing a single line of code
3. Only then proceed with the implementation

A "significant task" is anything beyond a trivial single-line fix or typo correction.

### After Writing Any Code

Claude must **always** run the full quality pipeline and fix any reported errors before considering the task done:

```bash
# Step 1 — code style check (dry-run)
composer run check-cs

# Step 2 — static analysis
composer run phpstan

# Step 3 — rector (no un-applied transformations allowed)
composer run rector

# Step 4 — dependency security audit
composer audit
```

If any command reports errors, fix them immediately and re-run until all four pass cleanly.

For `composer audit`, report any advisories found. Vulnerabilities in transitive dependencies pinned by `craftcms/cms` (Twig, Symfony, Yii2) are usually resolved by a Craft upgrade rather than by this plugin — do not attempt to bump them in isolation without confirming Craft's version constraints allow it.

---

## Language

- All code, comments, commit messages, and documentation must be written in **English**
- The user may interact in French, but all produced artifacts (code, docs, configs) are always in English

---

## General Principles

- Write clean, maintainable, and well-tested code
- Prefer simplicity over cleverness
- Keep functions small and focused on a single responsibility
- Use meaningful variable and function names
- Do not hardcode configuration values (URLs, secrets, timeouts, feature flags) — use plugin settings or a centralized config module
- When fixing a bug found in production, update upstream guidelines or conventions to prevent recurrence

---

## Comments

- Do **not** add comments that explain *what* the code does — write code that is clear enough to be self-explanatory
- Only add comments to explain *why* a non-obvious decision was made (e.g. a workaround for a third-party bug)
- PHPDoc blocks (`@param`, `@return`, `@throws`) are allowed and encouraged where they aid static analysis (PHPStan)

---

## Code Quality

- All code must pass PHP-CS-Fixer, PHPStan, and Rector checks before commit (see Workflow above)
- Write unit tests for any new functionality
- Use PHP 8.2 type annotations throughout: typed properties, union types, return types, argument types, and `readonly` where applicable
- Keep dependencies up to date and minimize their number — each new dependency must be justified

---

## Git & Commits

- **Do not add AI attribution markers** — no `Co-Authored-By` in commit messages, no "Generated with Claude Code" or similar footers in merge request descriptions
- Commit messages follow the **Conventional Commits** format: `type(scope): short description`
    - Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `style`
    - Example: `fix(translate): handle empty API response gracefully`
- Never commit code that fails linting or static analysis
- Always run `composer audit` before committing — do not commit with unresolved security advisories on dependencies this plugin can update
- One logical change per commit — avoid mixing unrelated fixes

---

## Tech Stack

- **PHP** 8.2+ — strict types, typed properties, readonly, enums, intersection types
- **Craft CMS** 5.8.0+ — event system, services as Yii2 components, URL manager, view events
- **Yii2** — dependency injection, component lifecycle, cache interface
- **Composer** for PHP dependencies + `php-scoper` for namespace isolation
- **Vite** for frontend JS/CSS compilation
- **PHPStan** level 6 with 100% type coverage
- **Rector** for code modernization

---

## PHP / Craft Standards

- Follow [Craft CMS coding standards](https://craftcms.com/docs/5.x/extend/coding-guidelines.html)
- Always use `===` / `!==`; never `==` / `!=`
- Always declare `declare(strict_types=1)` at the top of each file
- Use Craft's own cache interface (`\Craft::$app->getCache()`) for caching — never roll your own
- Use Craft's `Request` object — never access `$_SERVER`, `$_GET`, `$_POST` directly
- Use Guzzle (already a dependency) for all outbound HTTP calls — never `file_get_contents()` or `curl_*`
- Services must be registered as Yii2 components in `Plugin::config()` and never instantiated with `new` outside of that
- Raise `\yii\web\NotFoundHttpException` or redirect via `\Craft::$app->getResponse()` — never `die()` or `exit()`
- Use `\Craft::$app->getCache()` with a meaningful cache key prefix (`weglot_*`) to avoid collisions

---

## Setup

```bash
composer install && npm install
```

---

## Build Assets

```bash
npm run dev     # Watch mode (Vite)
npm run build   # Build to src/resources/
```

Source files: `src/resources-src/js/admin.js`, `src/resources-src/scss/admin.scss`
Compiled output: `src/resources/js/`, `src/resources/css/`

---

## Tests

```bash
composer run test                                         # All tests
./vendor/bin/phpunit tests/unit/PluginTest.php            # Single file
./vendor/bin/phpunit --filter testMethodName             # Single test
```

Tests bootstrap a headless Craft app (no database needed) via `tests/bootstrap.php`.

- `tests/unit/` — pure unit tests (settings normalization, URL helpers, service logic)
- `tests/services/` — service-level integration tests with full Craft bootstrap

PHPUnit config: `phpunit.xml.dist`, cache in `.cache/phpunit`.

---

## Vendor Library Update Workflow (Makefile)

The `src/vendor/weglot/` directory contains **namespace-scoped** copies of external Weglot libraries — do not edit them directly.

```bash
make checkout     # Clone weglot-php, weglot-translation-definitions, simple_html_dom at latest tags
make scoper       # Apply php-scoper (namespaces everything under Weglot\Vendor)
make vendor-sync  # Copy scoped output to src/vendor/weglot
make all          # Full update: checkout + scoper + vendor-sync
make clean        # Remove all build artifacts
```

---

## Manual Release (GitHub)

GitHub releases are created automatically by the `.github/workflows/create-release.yml` workflow, which runs **only** on a `repository_dispatch` event of type `craftcms/new-release`. That event is sent by the **Craft Plugin Store** (via id.craftcms.com) when it detects a new version tag — **pushing a git tag alone does NOT create a release.**

### When the release does not appear after tagging

If a tag exists (e.g. on https://github.com/weglot/translate-craftcms) but no release was created, the Craft Plugin Store did not send the dispatch. The root cause is almost always **upstream, not GitHub**:

1. **GitHub authorization in the Craft Console expired/was revoked** — re-authorize the Craft CMS GitHub app on the repo at **id.craftcms.com** (this is the real fix; once repaired, releases resume automatically).
2. **Packagist does not have the new version** — if Packagist doesn't list the tag, Craft never learns about it.

Diagnose with:

```bash
gh auth status                                                                  # must be logged in with 'workflow' scope + write access to the repo
gh run list --repo weglot/translate-craftcms --workflow=create-release.yml --event=repository_dispatch --limit 5
gh release list --repo weglot/translate-craftcms --limit 10                     # compare against tags
gh api repos/weglot/translate-craftcms/tags --jq '.[].name' | head
```

### Workaround — fire the dispatch manually

When Craft support is unresponsive, send the same `repository_dispatch` Craft would have sent. This creates the GitHub Release attached to the **already-existing** tag (it does not touch or move the tag).

**Prerequisites:** `gh` logged in with the `workflow` scope + write access to the repo, and the tag must already exist (otherwise `ncipollo/release-action` would create the tag on the default branch).

```bash
# Build the payload (notes come from the matching CHANGELOG.md section)
cat > dispatch.json <<'EOF'
{
  "event_type": "craftcms/new-release",
  "client_payload": {
    "version": "1.2.5",
    "tag": "1.2.5",
    "latest": true,
    "prerelease": false,
    "notes": "- Fix: ...\n- Fix: ..."
  }
}
EOF

# Send it (HTTP 204, no output = success)
gh api repos/weglot/translate-craftcms/dispatches --input dispatch.json

# Verify
gh run list --repo weglot/translate-craftcms --workflow=create-release.yml --event=repository_dispatch --limit 1
gh release view 1.2.5 --repo weglot/translate-craftcms
```

The workflow maps each `client_payload` field to `ncipollo/release-action`: `version`→`name`, `tag`→`tag`, `notes`→`body`, `latest`→`makeLatest`, `prerelease`→`prerelease`. This is a workaround only — the durable fix is restoring the Craft↔GitHub connection.

---

## Architecture

### Project Structure

```
weglot/
├── src/
│   ├── Plugin.php                       # Entry point — services, events, URL rules
│   ├── controllers/
│   │   ├── RouterController.php         # Language-prefixed URL routing (weglot/router/forward)
│   │   └── ApiController.php            # Admin API endpoints (weglot/api/*)
│   ├── services/                        # 16 services (see Service Layer below)
│   ├── models/
│   │   └── Settings.php                 # Plugin settings model with validation
│   ├── helpers/
│   │   ├── HelperApi.php                # API/CDN URL configuration
│   │   ├── HelperFlagType.php           # Language flag styles
│   │   ├── HelperReplaceUrl.php         # URL replacement regex patterns
│   │   ├── HelperSwitcher.php           # Language switcher HTML builder
│   │   └── DashboardHelper.php          # Dashboard utilities
│   ├── checkers/dom/                    # Custom DOM checkers (9 classes extending Weglot's base)
│   ├── events/
│   │   └── RegisterSelectorsEvent.php   # Event for selector registration
│   ├── web/
│   │   └── WeglotVirtualRequest.php     # Virtual request wrapper for language routing
│   ├── resources/
│   │   ├── AdminAsset.php               # Yii2 asset bundle (registers CSS/JS with Craft CP)
│   │   ├── js/                          # Compiled JS (Vite output)
│   │   ├── css/                         # Compiled CSS (Vite output)
│   │   ├── vendor/                      # select2, selectize
│   │   └── img/                         # SVG icons
│   ├── resources-src/
│   │   ├── js/admin.js                  # Admin JavaScript source
│   │   └── scss/admin.scss              # Admin SCSS source
│   ├── templates/
│   │   └── _settings.twig               # Plugin settings template (Craft CP)
│   └── vendor/weglot/                   # Scoped Weglot libraries (do not edit directly)
│       └── build/vendor-src/
│           ├── weglot-php/              # Weglot API client & HTML parser
│           ├── simple_html_dom/         # HTML parsing library
│           └── weglot-translation-definitions/
├── tests/
│   ├── bootstrap.php                    # PHPUnit bootstrap (Craft app, no DB)
│   ├── unit/                            # Pure unit tests
│   └── services/                        # Integration tests with Craft bootstrap
├── build/                               # Build artifacts (gitignored)
│   ├── vendor-src/                      # Downloaded library sources
│   └── scoped-vendor/                   # php-scoper output
├── composer.json
├── phpstan.dist.neon                    # PHPStan level 6, 100% type coverage
├── .php-cs-fixer.dist.php               # PHP 8.2 + Symfony code style
├── rector.php                           # Rector PHP 8.2 upgrade + dead code
├── scoper.inc.php                       # php-scoper: namespace prefix Weglot\Vendor
├── vite.config.js                       # Vite config for admin assets
├── package.json
└── Makefile                             # Vendor update workflow
```

### Plugin Bootstrap Flow

1. **`Plugin::init()`** — sets alias `@weglot/craftweglot`, calls `attachEventHandlers()`
2. **`Plugin::config()`** — declares all 16 services as Yii2 components (lazy-instantiated)
3. **`attachEventHandlers()`** — registers all Craft event listeners:
    - `UrlManager::EVENT_REGISTER_SITE_URL_RULES` — language-prefixed URL rules
    - `View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE` — detect language, wrap request
    - `View::EVENT_AFTER_RENDER_PAGE_TEMPLATE` — pass HTML through translation pipeline
    - `View::EVENT_BEGIN_PAGE` — inject hrefLang, switcher, analytics scripts
4. **`Plugin::afterSaveSettings()`** — normalizes language codes, syncs settings to Weglot API

### Request Translation Pipeline

1. **URL Routing** — `UrlManager::EVENT_REGISTER_SITE_URL_RULES` registers patterns like `<lang:(fr|de|...)>/<rest:.+>` → `weglot/router/forward`
2. **Virtual Request** — `RouterController::actionForward()` creates a `WeglotVirtualRequest` that strips the language prefix and resolves the Craft-canonical route
3. **Template Rendering** — `View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE` detects language; `View::EVENT_AFTER_RENDER_PAGE_TEMPLATE` passes rendered HTML through `TranslateService::processResponse()`
4. **HTML Processing** — `ParserService` instantiates the scoped Weglot PHP parser with DOM checkers and regex checkers; the parser sends content to the Weglot API/CDN and returns translated HTML
5. **Post-Processing** — `ReplaceUrlService` rewrites internal links to include the language prefix; `ReplaceLinkService` rewrites individual `href`/`src` attributes
6. **Injection** — `View::EVENT_BEGIN_PAGE` injects `<link rel="alternate" hreflang>` tags, language switcher widget CSS/JS, Weglot JS, and page views script

### Service Layer (`src/services/`)

All services are registered as Yii2 components in `Plugin::config()`. Access them via `Plugin::getInstance()->{serviceName}`.

| Service | Component key | Responsibility |
|---|---|---|
| `TranslateService` | `translateService` | Core translation engine; auto-detects HTML/JSON/XML, orchestrates parser + URL rewriting |
| `LanguageService` | `language` | Fetches/caches available languages from API; resolves internal↔external codes |
| `OptionService` | `option` | Reads/writes plugin settings; syncs with Weglot API/CDN; manages exclusion rules |
| `RequestUrlService` | `requestUrlService` | Detects current language from URL; creates Weglot URL objects; checks eligibility |
| `ParserService` | `parserService` | Instantiates scoped Weglot parser with DOM/regex checkers and API client |
| `ReplaceUrlService` | `replaceUrlService` | Rewrites internal links in HTML to include language prefix |
| `ReplaceLinkService` | `replaceLinkService` | Rewrites individual URLs (href/src) using Weglot URL helpers |
| `SlugService` | `slug` | Handles translated URL slugs via API; caches slug maps with TTL=0 |
| `HrefLangService` | `hrefLangService` | Generates and injects `<link rel="alternate" hreflang>` SEO tags |
| `FrontEndScriptsService` | `frontEndScripts` | Injects Weglot JS (CDN), switcher CSS/JS, and frontend config |
| `DomCheckersService` | `domCheckersService` | Registry of custom DOM element selectors from `src/checkers/dom/` |
| `RegexCheckersService` | `regexCheckersService` | Registry of regex-based content checkers (currently extensible via future events) |
| `RedirectService` | `redirectService` | Browser language auto-detection redirects via Accept-Language / Cloudflare headers |
| `DynamicsService` | `dynamics` | Detects dynamically-rendered DOM content; extends via `EVENT_REGISTER_DYNAMICS_SELECTORS` |
| `PageViewsService` | `pageViews` | Injects JS that POSTs page view events to Weglot analytics endpoint |
| `UserApiService` | `userApi` | Validates API keys via Weglot API; used by settings validation and `ApiController` |

### Controllers

- **`RouterController`** (`weglot/router/forward`, `allowAnonymous = ['forward']`) — resolves language-prefixed URLs; handles slug canonicalization and exclusion redirects; falls through to Craft routing
- **`ApiController`** (`weglot/api/*`, requires admin + POST) — `actionValidateApiKey()` proxies to `UserApiService::getUserInfo()`

### Custom Events

| Constant | Class | When fired |
|---|---|---|
| `Plugin::EVENT_REGISTER_WHITELIST_SELECTORS` | `RegisterSelectorsEvent` | `DynamicsService::addDynamics()` — extend language-switcher whitelist CSS selectors |
| `Plugin::EVENT_REGISTER_DYNAMICS_SELECTORS` | `RegisterSelectorsEvent` | `DynamicsService::addDynamics()` — register CSS selectors for dynamic content translation |

Listen to these events from a third-party plugin to extend Weglot's behavior without modifying this plugin.

### Plugin Settings (`src/models/Settings.php`)

| Property | Type | Default | Description |
|---|---|---|---|
| `apiKey` | `string` | `''` | Weglot private API key (required, validated via API) |
| `languageFrom` | `string` | `'en'` | Source language code |
| `languages` | `string[]` | `[]` | Destination language codes |
| `hasFirstSettings` | `bool` | `false` | First-time setup flag |
| `showBoxFirstSettings` | `bool` | `true` | Show onboarding popup |
| `enableDynamics` | `bool` | `false` | Enable dynamic content translation |
| `enableAlgolia` | `bool` | `false` | Enable Algolia integration |
| `dynamicsWhitelistSelectors` | `string` | `''` | Comma-separated CSS selectors for switcher whitelist |
| `dynamicsAllowedUrls` | `string` | `''` | Regex patterns for URLs where dynamics is active |

Language codes are normalized in `Plugin::afterSaveSettings()` before syncing to the Weglot API.

### Namespace Scoping

External Weglot libraries (`weglot-php`, `weglot-translation-definitions`, `simple_html_dom`) are scoped under the `Weglot\Vendor` namespace via `php-scoper` to prevent version conflicts with other Craft plugins. Configuration in `scoper.inc.php`. The scoped output lives in `src/vendor/weglot/` and is committed to the repo — never edit it directly; use the Makefile workflow.

### Namespace & File Naming

| Layer | Namespace | Example |
|---|---|---|
| Plugin entry | `weglot\craftweglot\` | `Plugin` |
| Services | `weglot\craftweglot\services\` | `TranslateService` |
| Models | `weglot\craftweglot\models\` | `Settings` |
| Controllers | `weglot\craftweglot\controllers\` | `RouterController` |
| Checkers | `weglot\craftweglot\checkers\dom\` | `MetaFacebookImage` |
| Helpers | `weglot\craftweglot\helpers\` | `HelperApi` |
| Events | `weglot\craftweglot\events\` | `RegisterSelectorsEvent` |
| Scoped vendor | `Weglot\Vendor\Weglot\*` | `Weglot\Vendor\Weglot\Client\Api\…` |

Files follow PSR-4: namespace hierarchy maps directly to directory structure under `src/`.

---

## Frontend Assets

Vite entry points in `src/resources-src/js/` and `src/resources-src/scss/`. Compiled output in `src/resources/`. The `AdminAsset` class (extends `\craft\web\AssetBundle`) registers compiled files with the Craft control panel.

Language switcher and Weglot JS are loaded from the Weglot CDN at runtime — they are not part of the Vite build.

### Rules

- **No inline JS or CSS in Twig templates** — all JavaScript goes in `admin.js`, all styles go in `admin.scss`. Twig templates must contain only HTML markup.
- Pass server-side values (action URLs, CSRF tokens, translated strings) to JS via `data-*` attributes on HTML elements — never embed them in `<script>` blocks or inline `style=""` attributes.
- Use `Craft.postActionRequest()` for admin API calls — it handles CSRF automatically.
- Use `Craft.t('weglot', '...')` for translated strings in JS — never hardcode UI text.
