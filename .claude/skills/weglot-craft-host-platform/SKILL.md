---
name: weglot-craft-host-platform
description: "Craft CMS as this plugin's host: the Craft / Yii events it listens to, how site, CP and console requests are told apart, CP controller security (requireAdmin, POST, JSON, CSRF via Craft.postActionRequest, allowAnonymous), settings stored in project config, the public extension points (EVENT_REGISTER_* events, Settings properties) and their backward compatibility, the supported PHP / Craft versions. Use when adding a controller action, a CP screen, a setting, an event listener or a public event, when asked \"is this secure\", \"quelle version de Craft/PHP on supporte\", \"can I use this PHP 8.3 feature\", or when a change touches Plugin::EVENT_* or Settings."
---

# Craft CMS host platform

## Supported versions

| What | Value | Source |
|---|---|---|
| PHP | ≥ 8.2; CI runs 8.2; dependency resolution pinned to 8.2 | `composer.json` `require.php`, `config.platform.php`, `.github/workflows/code-quality.yml` |
| Craft CMS | ^5.8.0 (lock: 5.10.12) | `composer.json`, `composer.lock` |
| Yii | ^2.0 | `composer.json` |

PHP 8.3+ syntax or functions (typed class constants, `json_validate()`, `#[\Override]`) break the 8.2 floor — `phpstan.dist.neon` leaves constant type coverage at 0 for that reason. A Craft API newer than 5.8 needs a version guard or a constraint bump.

## Where the plugin hooks in

`.claude/memory/architecture/plugin-bootstrap.md` has the full table. Rules that follow from it:

- **Site vs CP vs console**: site-only logic checks `getRequest()->getIsSiteRequest()` (and skips `getIsAjax()`, `src/Plugin.php:201`); everything after `src/Plugin.php:292` is skipped for CP and console requests.
- The request component is **swapped** for a `WeglotVirtualRequest` during page rendering (`src/Plugin.php:196-256`): code running in that window sees the source-language path. Restore anything you swap (`:259`).
- URL rules are added only for site requests (`src/Plugin.php:268-289`), and `<lang>/actions/…` must keep routing to Craft actions.

## Security baseline as applied

| Surface | Rule | Reference implementation |
|---|---|---|
| CP JSON action | `requireAdmin()` + `requirePostRequest()` + `requireAcceptsJson()` in `beforeAction()` | `src/controllers/ApiController.php:22-32` |
| CSRF | CP JS calls go through `Craft.postActionRequest()`, never raw `fetch()` | `src/resources-src/js/admin.js:47,325` |
| Anonymous site action | only the router: `allowAnonymous = ['forward']`, and it re-dispatches `actions/…` through `runAction()` | `src/controllers/RouterController.php:17,22-33` |
| Input | `getRequiredBodyParam()` / `getBodyParam()`; settings validated by `Settings::rules()` (API key checked live) | `ApiController.php:36`, `src/models/Settings.php:33-71` |
| Output | Twig auto-escape; HTML built in PHP escaped with `Html::encode()` / `htmlspecialchars()` | `src/services/TranslateService.php:154` |
| Headers forwarded upstream | sanitised before reuse (the `wg-editor-session` header, CHANGELOG 1.2.4) | `src/services/ParserService.php` |

Known debt: `$_SERVER` / `$_COOKIE` reads in `RedirectService` and `Plugin::weglotInit()` — `.claude/memory/standards/craft-php-standards.md` § Known debt.

## Settings and project config

Settings live in the project config (`plugins.weglot.settings`); on environments with `allowAdminChanges = false` they are read-only in the CP. `ApiController::actionResetSettings()` writes the project config directly to bypass `Settings::rules()` and **lists every property by hand** (`src/controllers/ApiController.php:47-62`): a new `Settings` property must be added there too. `afterSaveSettings()` pushes settings to the Weglot API and re-saves under the `$savingSettings` guard.

## Public extension points

Events `registerWhitelistSelectors` / `registerDynamicsSelectors`, `Settings` property names, routes, plugin handle — never rename or re-type; risky behaviour ships opt-in (`.claude/memory/standards/public-extension-points-backward-compat.md`).

## Distribution

Craft Plugin Store, license `proprietary`, handle `weglot`, class `weglot\craftweglot\Plugin` (`composer.json` `extra`). Everything under `src/` ships, including `src/vendor/weglot/` and the compiled `src/resources/`. Publishing: `/release`.

## When NOT to use this skill

- A concrete bug → `/weglot-craft-debugging-playbook`. Tests and assets → `/weglot-craft-build-and-qa`.

## Provenance and maintenance

| Fact | Re-verify |
|---|---|
| Version matrix | `grep -n '"php"\|craftcms/cms\|yiisoft/yii2' composer.json; grep -n "php-version" .github/workflows/code-quality.yml` |
| CP action guards | `grep -n "require[A-Z]" src/controllers/ApiController.php` |
| Anonymous actions | `grep -rn "allowAnonymous" src/controllers` |
| CP / console early return | `grep -n "getIsCpRequest" src/Plugin.php` |
| Reset lists every setting | `diff <(grep -oE "public (string\|bool\|array) \\\$[a-zA-Z]+" src/models/Settings.php \| sed 's/.*\$//' \| sort) <(sed -n '/packAssociativeArrays/,/]);/p' src/controllers/ApiController.php \| grep -oE "'[a-zA-Z]+' =>" \| tr -d "' =>" \| sort)` (empty = in sync) |
