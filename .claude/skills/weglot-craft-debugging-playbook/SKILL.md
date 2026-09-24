---
name: weglot-craft-debugging-playbook
description: "Symptom → discriminating check → fix for bugs of the Weglot Craft plugin: page not translated, \"la page n'est pas traduite\", no Weglot error comment, <!--Weglot error API, Project settings not found, invalid API key on a staging key, WEGLOT_DEV=true ne marche pas / ignored, 404 on a translated URL, wrong or missing slug redirect, switcher links pointing to the wrong page, links rewritten with /fr/ that should not be (mailto:, #anchor, external, wg-excluded-link), srcset not translated, media or external toggle ignored, dynamics or Algolia dead on a V2 project, dashboard links wrong, onboarding button redirects away, Class \"\\Weglot\\Vendor\\...\" not found, tests failing only in random order. Use before any generic debugging skill when a plugin-specific error or customer report comes in."
---

# Debugging playbook — Weglot Craft plugin

Before proposing a fix, write the root-cause claim as: **hypothesis → cheapest check that would disprove it → ran it → result**. Locate the pipeline step first (`.claude/memory/architecture/translation-pipeline.md`): URL rule → router / slug → virtual request → parser / API → link rewriting → hreflang.

## Translation not applied

| Symptom | Discriminating check | Fix / where | Evidence |
|---|---|---|---|
| Translated URL in source language, **no** `<!--Weglot error … -->` | Which host did `ParserService::getClient()` use? Status of the translate POST? | Non-200 is swallowed by the scoped `CdnTranslate`; fix the host / env, clear caches | `.claude/memory/gotchas/silent-translation-cdn-fallback.md` |
| Source language **with** `<!--Weglot error API : … -->` | The message (quota, bad key, project not found) | API side: key, project version (V1 `wg_…` vs V2) | `src/services/TranslateService.php:116-124` |
| Staging key: "Project settings not found" / invalid key | `App::env('WEGLOT_DEV')` type; is `WEGLOT_ENV` set? | Use `WEGLOT_ENV=staging` — `WEGLOT_DEV=true`/`1` is ignored | `.claude/memory/gotchas/weglot-dev-env-normalization.md`, `.claude/memory/gotchas/weglot-dev-hosts.md` |
| JSON / XML response untranslated | `getContentType()` result; is it a page template render? | Only `View::EVENT_AFTER_RENDER_PAGE_TEMPLATE` output is processed | `src/Plugin.php:311`, `TranslateService.php:99-101` |
| `<img srcset>` in source language while `src` is translated | `ImageSourceSet` present in `src/checkers/dom/`? | Restored as a plugin checker (1.2.8) | `bf4e39d` |
| `media_enabled` / `external_enabled` off but still translated | Checker names passed to `removeCheckers()` start with `\`? | Prefix `'\\'` to `::class` | `bf4e39d`, `.claude/memory/gotchas/scoped-vendor-pitfalls.md` |
| V2 project translated with a different engine than WordPress | `getTranslationEngine()` value | Default engine 3 when the option is absent (1.2.x) | `4a1d6af` |

## URLs, slugs, links

| Symptom | Discriminating check | Fix / where | Evidence |
|---|---|---|---|
| 404 on `/fr/<nested>/<slug>` or no redirect to the translated slug | Is only the first path segment matched against the slug map? | Every segment matched | `b212972`, CHANGELOG 1.2.5 |
| Switcher links on a translated page point to the wrong URL | Links built from the current translated slug or the source slug? | Built from the source slug | `b212972`, `b90ede2` |
| Some languages missing from slug maps within one request | Memoisation keyed by the destination-language set? | Keyed by the set | `b212972` |
| `mailto:` / `tel:` becomes `/it/a@b.com/` | URL scheme | Only `http`/`https` are rewritten | `074e2e6`, CHANGELOG 1.2.7 |
| `#anchor`, `#/spa/route`, `?q=` rebased to `/fr/#…` | href starts with `#` or `?` | Same-document refs untouched | `72cf88f`, CHANGELOG 1.2.8 |
| External link gets a language prefix | Host differs from the request host? | External hosts returned unchanged | CHANGELOG 1.2.3 (`ReplaceLinkService::replaceUrl()`) |
| Element with `wg-excluded-link` still rewritten | Which pattern in `HelperReplaceUrl::getReplaceModifyLink()` matched? Does an identical non-excluded tag with the same URL exist on the page? | Lookahead right after `<` in every pattern, trailing guard in `simpleReplace()` / `replaceForm()` | `fix/replace-url-excluded-link` (#55 review), `.claude/memory/standards/regex-and-xpath-on-html.md` |
| `/fr/actions/…` (CP / plugin actions) broken | Rule `<lang>/actions/<action>` registered? | Re-dispatched untouched by the router | `src/Plugin.php:283-284`, `src/controllers/RouterController.php:24-33` |

## Settings, CP, V2

| Symptom | Discriminating check | Fix / where | Evidence |
|---|---|---|---|
| Dynamics dead / Algolia sends empty credentials on a V2 project | `getPublicApiKey()` reads `public_key` (V2) or `api_key` (V1)? | Read `public_key` for V2 | `0519225` |
| V2 switcher `button_style` / custom settings lost | `custom_settings` is `{}` in the V2 response and wiped the defaults by `array_merge` | Nested merge | `0519225` |
| V2 dashboard link on another project's workspace | Workspace slug cache keyed by the API key? | `UserApiService::workspaceCacheKey()` | `aaf6281` |
| Dashboard link stays broken for the cache TTL after one API hiccup | Failed lookup cached? | Not cached for the full TTL | `2333c59` |
| "Activate Weglot" saves and leaves for `/admin/settings` | Plain submit? Craft's plugin settings layout hardcodes that redirect | Override the redirect, click the real submit | `fa1d8d3` |
| Settings save loops / saves twice | `$savingSettings` guard bypassed by a new `savePluginSettings()` call | Keep the call inside the guarded block | `src/Plugin.php:106-110,183` |
| V1 language fields shown / hidden wrongly | `showV1Fields` = key starts with `wg_` | `src/Plugin.php` `settingsHtml()` | #59 review |

## Vendor, tests, tooling

| Symptom | Discriminating check | Fix / where | Evidence |
|---|---|---|---|
| `Class "\Weglot\Vendor\Weglot\Parser\Check\Dom\…" not found` | Did a vendor update add a checker file? | `composer dump-autoload` in the Craft project | `.claude/memory/gotchas/scoped-vendor-pitfalls.md` |
| A branch in the scoped vendor never runs | Does it compare with an unscoped `'\Weglot\…'` string? | `patchers` entry in `scoper.inc.php`, re-scope | same gotcha, `DomFormatter.php:125,130` |
| Test fails only with some seeds | Component or request state left by another class? | Restore components in `tearDown()`, seed the URL | `395479d`, `.claude/memory/testing/writing-tests.md` |
| PHPStan errors on a branch whose CI is green | CI runs no PHPStan | `/deploy-check` | `.claude/memory/gotchas/ci-gate-gaps.md` |
| `npm run build` → `ERR_REQUIRE_ESM` | `node -v` < 20.19 / 22.12 | Upgrade Node | `/weglot-craft-build-and-qa` § Assets |

## "Works on our site, broken on the customer's"

Ask for, in this order: Craft and PHP versions, V1 (`wg_…`) or V2 key, the site's `.env` `WEGLOT_*` values, a cache / CDN in front (Cloudflare, Varnish — `HTTP_CF_IPCOUNTRY` feeds the auto-redirect, `src/services/RedirectService.php:32`), plugins that also rewrite URLs or render htmx / Vue, and the raw HTML of the page (look for `<!--Weglot error`, `translate="no"` on `<html>`, `wg-excluded-link`). Then check `storage/logs/web*.log` for `weglot` categories (`\Craft::error(…, __METHOD__)`).

## When NOT to use this skill

- Running the gates → `/deploy-check`. Setup, assets, tests, env → `/weglot-craft-build-and-qa`.
- Vendor library update → `/vendor-update`. Security or extension-point questions → `/weglot-craft-host-platform`.

## Provenance and maintenance

| Fact | Re-verify |
|---|---|
| Fix commits cited | `for h in bf4e39d 4a1d6af b212972 b90ede2 074e2e6 72cf88f 0519225 aaf6281 2333c59 fa1d8d3 395479d; do git log -1 --oneline $h; done` |
| Error comment strings | `grep -n "Weglot error" src/services/TranslateService.php` |
| Every link pattern and page-wide replace guarded | `grep -c "(?!\[^>\]\*wg-excluded-link)" src/helpers/HelperReplaceUrl.php src/services/ReplaceLinkService.php` (15 and 2) |
| Actions rules | `grep -n "actions/<action" src/Plugin.php` |
| Settings-save guard | `grep -n "savingSettings" src/Plugin.php` |
