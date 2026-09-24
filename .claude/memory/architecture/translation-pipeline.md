---
name: translation-pipeline
description: What happens on a frontend request from the language-prefixed URL to translated HTML, where each step lives, and the two translation engines (PHP parser vs JS dynamics).
type: architecture
---

## Frontend request, step by step

1. **URL rule** — `/<lang>/<rest>` matches the site rule registered in `src/Plugin.php:271` and routes to `weglot/router/forward`.
2. **Router** — `RouterController::actionForward()` (`src/controllers/RouterController.php:22`): `<lang>/actions/…` is re-dispatched untouched; excluded URLs redirect (`handleExcludedUrlRedirects()`); a request on the *untranslated* slug 301s to the translated one (`SlugService::getRedirectPathIfUntranslated()`, `:48-68`); then Craft routing resolves the rest, or `NotFoundHttpException` (`:167`).
3. **Virtual request** — before the template renders (`src/Plugin.php:196`) the translated slug is mapped back to the source path (`SlugService::getInternalPathIfTranslatedSlug()`) and `request` is swapped for a `WeglotVirtualRequest` (`src/web/WeglotVirtualRequest.php`), so templates and element queries see the source-language path. Restored after render (`:259`).
4. **Page head** — `View::EVENT_BEGIN_PAGE` (`src/Plugin.php:298`) injects hreflang (`HrefLangService`), the `weglot-data` payload (`OptionService::generateWeglotData()`), dynamics config, switcher assets, Algolia and page-views scripts.
5. **Translate** — after render, `weglotInit()` (`src/Plugin.php:321`) → `TranslateService::processResponse()` (`src/services/TranslateService.php:76`): content type detected (`html` / `json` / `xml`); source language or no URL for the language → `weglotRenderDom()` only; otherwise `ParserService::getParser()->translate()` (scoped `weglot-parser-php` + `weglot-php`, DOM checkers from `src/checkers/dom/` and the vendor) calls the Weglot API.
6. **Post-process** — `weglotRenderDom()` (`:137`): link rewriting through `ReplaceUrlService::replaceLinkInDom()` (regexes in `src/helpers/HelperReplaceUrl.php`, per-URL logic in `ReplaceLinkService`), canonical tag rebuilt, `translate="no"` added on `<html>`.

On an exception the page is served untranslated with `<!--Weglot error API : …-->` (`ApiError`) or `<!--Weglot error : …-->` (`src/services/TranslateService.php:116-124`); JSON responses get no marker. Not every failure reaches that path — `.claude/memory/gotchas/silent-translation-cdn-fallback.md`.

## Two engines

- **PHP parser** — server side, steps 5–6, the default.
- **Dynamics engine** — `weglot.min.js` from the Weglot CDN, translates content rendered client side. Enabled by the `enableDynamics` setting, scoped by `dynamicsAllowedUrls`; selectors come from `dynamicsWhitelistSelectors` plus the `EVENT_REGISTER_WHITELIST_SELECTORS` / `EVENT_REGISTER_DYNAMICS_SELECTORS` events (`src/services/DynamicsService.php:48,52`). Its behaviour lives in the CDN bundle, not in this repo.

**Why:** a symptom ("not translated", "wrong link", "404 on the French URL") maps to a different step each time, spread over the router, two request objects, four services and the scoped vendor.
**How to apply:** locate the step first (URL rule → router/slug → virtual request → parser/API → link rewriting → hreflang) before editing; `/weglot-craft-debugging-playbook` has the symptom → step table.
