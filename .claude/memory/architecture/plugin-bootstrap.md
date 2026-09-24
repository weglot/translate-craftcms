---
name: plugin-bootstrap
description: How the plugin boots in Craft — components declared in Plugin::config(), typed getters, the event handlers attachEventHandlers() registers, the settings-save re-entrancy guard, and the layout of src/.
type: architecture
---

## Layout

```
src/
├── Plugin.php              # Entry point — components, events, URL rules, settings page, afterSaveSettings()
├── controllers/            # RouterController (language-prefixed site URLs), ApiController (CP JSON endpoints)
├── services/               # 17 services, all craft\base\Component
├── helpers/                # HelperApi (hosts, env), HelperReplaceUrl (link regexes), HelperSwitcher, HelperFlagType, DashboardHelper (a component too)
├── checkers/dom/           # Plugin-side DOM checkers, discovered by scandir() in DomCheckersService
├── models/Settings.php     # Plugin settings model + validation (API key validated live)
├── events/                 # RegisterSelectorsEvent
├── web/WeglotVirtualRequest.php  # Request wrapper that hides the language prefix from Craft routing
├── templates/_settings.twig      # The CP settings screen (only template)
├── translations/fr/weglot.php    # Only translated message source
├── resources/ + resources-src/   # CP asset bundle (AdminAsset) and its sources — see /weglot-craft-build-and-qa
└── vendor/weglot/          # php-scoper output of the Weglot libraries — never edit, see /vendor-update
```

PSR-4: `weglot\craftweglot\` → `src/` (sub-namespace = directory: `services\`, `helpers\`, `checkers\dom\`…), tests `weglot\craftweglot\tests\` → `tests/`; the scoped libraries are `Weglot\Vendor\…` (`composer.json` `autoload`). The alias `@weglot/craftweglot` points at `src/` (`src/Plugin.php:95`).

## Components

`Plugin::config()` (`src/Plugin.php:63-84`) declares **18 components**: the 17 services plus `DashboardHelper`. 16 of them have a typed getter on `Plugin` (`src/Plugin.php:518-597`, e.g. `getOption()`, `getLanguage()`, `getTranslateService()`); `dashboardHelper` and `versionService` are reached with `Plugin::getInstance()->get('<key>')`. The component key is not the class name (`option` → `OptionService`, `slug` → `SlugService`, `dynamics` → `DynamicsService`) — read the array before guessing.

A new service = one line in `config()` + a typed getter, in the same commit. Never `new` a service in `src/` (tests do it to inject stubs).

## Event handlers (`attachEventHandlers()`, `src/Plugin.php:190`)

| Event | Line | Scope | Does |
|---|---|---|---|
| `View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE` | `:196` | site, non-AJAX | first path segment is a destination language → resolve translated slug, swap `request` for a `WeglotVirtualRequest`, reverse-translate `?query=` |
| `View::EVENT_AFTER_RENDER_PAGE_TEMPLATE` | `:259` | all | restore the original request |
| `UrlManager::EVENT_REGISTER_SITE_URL_RULES` | `:271` | site requests | `<lang:(fr\|de…)>` and `<lang>/<rest>` → `weglot/router/forward`; `<lang>/actions/…` → `actions/…` |
| — | `:292` | | **CP and console requests stop here**: nothing below runs in the control panel |
| `View::EVENT_BEGIN_PAGE` | `:298` | site | hreflang, `weglot-data`, dynamics, switcher assets, Algolia script, page-views script |
| `View::EVENT_AFTER_RENDER_PAGE_TEMPLATE` | `:311` | site | `weglotInit()` (`:321`) → redirect checks → `TranslateService::processResponse()` |

## Settings save

`afterSaveSettings()` (`src/Plugin.php:102`) normalises language codes, pulls languages from the API for a V2 key, pushes settings to Weglot (`OptionService::saveWeglotSettings()`), then calls `savePluginSettings()` again — which re-enters `afterSaveSettings()`. The static `$savingSettings` flag (`:48`, `:106-110`, reset in `finally` `:183`) is what stops the recursion: keep it on any new save path.

**Why:** Craft's component and event model decides what runs where; the CP early return and the re-entrant save are not visible from any single service.
**How to apply:** a new service → `config()` + getter; frontend behaviour → one of the site handlers above, never before the `:292` return if it must not run in the CP; a new save side effect → inside the guarded `try`.
