---
name: writing-tests
description: PHPUnit conventions here — one headless Craft app shared by every test, random execution order, stubbing components with Plugin::getInstance()->set() and restoring them, no network (seed the cache or override a protected fetch method), env vars snapshotted, and which suites CI actually runs.
type: testing
---

## Runtime

- `tests/bootstrap.php` boots **one** headless Craft web app with no database (`:21-22`), registers the `weglot` message source (`:25-30`) and installs the plugin with its components (`:32-35`). Runtime files go to `tests/_craft/` (gitignored, excluded from PHPStan).
- Every test class shares that app and its components. `phpunit.xml.dist` sets `executionOrder="random"`, so any state a test leaves behind can break an unrelated test depending on the seed (`395479d`: `TranslateServiceTest` only passed because an earlier class had resolved the request URL).
- Base class is `PHPUnit\Framework\TestCase`; there is no project base class and no Craft test module.

## Layout and commands

- `tests/unit/<mirror of src>/<Class>Test.php`, namespace `weglot\craftweglot\tests\…` (`composer.json` `autoload-dev`). `tests/services/` holds one integration-style class (`ReplaceUrlServiceTest`).
- `composer run test` runs `tests/` (both folders). **CI runs only `tests/unit/`** (`.github/workflows/code-quality.yml`, last step) — `/deploy-check` runs both.
- One class: `./vendor/bin/phpunit tests/unit/services/SlugServiceTest.php`; one test: `./vendor/bin/phpunit --filter testName`; reproduce an order bug: `./vendor/bin/phpunit --order-by=random --random-order-seed=<seed>` (the seed is printed at the top of a run).

## Isolating a test

- **Replace a collaborator** with an anonymous subclass registered on the plugin: `Plugin::getInstance()->set('userApi', $stub)` (`tests/unit/models/SettingsTest.php:64-72`). The key is the component id from `Plugin::config()`, not the class name.
- **Restore it in `tearDown()`**, guarded because `tearDown()` runs even when `setUp()` threw — reference: `tests/services/ReplaceUrlServiceTest.php:18-34`. Six classes (`SettingsTest`, `RequestUrlServiceTest`, `LanguageServiceLegacyFallbackTest`, `ReplaceLinkServiceTest`, `HrefLangServiceTest`, `LanguageServiceAdditionalTest`) still set components without restoring them; don't copy them.
- **Never hit the network.** Either seed the Craft cache under the exact key the service reads (`UserApiService::workspaceCacheKey()`, `tests/unit/services/UserApiServiceTest.php:37-41`) and flush it in `setUp()` / `tearDown()`, or subclass the service and override its `protected` fetch method (`fetchWorkspaceSlug()`, `src/services/UserApiService.php:107`). Make a fetch method `protected` rather than `private` when a test needs that seam.
- **Env vars**: snapshot with `getenv()`, clear with `putenv($key)`, restore in `tearDown()` — `tests/unit/helpers/HelperApiTest.php:17-36`. `App::env()` normalises values: see `.claude/memory/gotchas/weglot-dev-env-normalization.md`.
- **Request URL**: code reading the absolute URL needs `\Craft::$app->getRequest()->setUrl('/')` in `setUp()` (`tests/unit/services/TranslateServiceTest.php:36-43`).
- `$_SERVER` keys a test sets are unset in `tearDown()` (`tests/unit/services/RedirectServiceTest.php:36-39`).

## What a test must prove

Assert the behaviour and the branch taken (a value changed, the stub was called), not only "nothing changed" — a wrong component id or cache key makes a no-op assertion pass for the wrong reason.

**Why:** the shared app + random order combination has already produced one order-dependent failure (`395479d`), and the component-leak pattern exists in six classes.
**How to apply:** every new or edited test class.
