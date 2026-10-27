# Release Notes for Weglot

## 1.2.9 - 2026-10-27

- Improvement: Adds Weglot 2 support: a Weglot 2 API key connects the site to the Weglot 2 API and CDN, the languages are pulled from the project when the settings are saved, and the control-panel links open the Weglot 2 dashboard. Weglot 1 (`wg_`) keys keep working as before.
- Improvement: Weglot 2 projects: translated slugs saved in the Weglot dashboard are now served, the untranslated URL redirects (301) to the translated one and a former translated slug redirects to the current one, whether or not AI slug translation is enabled. Slug edits show on the next page view. Opt out with `'enableTranslatedSlugs' => false` in `config/weglot.php`.
- Fix: Slug redirects keep the subdirectory the site is installed in and percent-encode non-ASCII slugs.
- Fix: Tracking parameters (`utm_*`, `gclid`, `fbclid`, `msclkid`…) are no longer copied into the language switcher links and the canonical URL, where a full-page cache such as Blitz could serve them to every visitor.
- Fix: Elements marked `wg-excluded-link` are no longer rewritten when their URL is in a `data-link`, `data-url`, `data-cart-url` or `hx-*` attribute, or when an identical non-excluded link, form or meta tag on the page is rewritten.
- Fix: The AI disclaimer no longer truncates inline scripts on translated pages, and is now inserted for compound selectors and selectors containing quotes.
- Fix: Saving the settings with a Weglot 2 key no longer shows "Weglot sync failed (missing_private_key)", and the settings are synced to Weglot.
- Fix: The settings screen and the API-key check no longer error when the Weglot API is unreachable.
- Fix: The "Go on my front page" button of the onboarding popup now opens the site in its first translated language.
- Fix: Removes the extra spacing between elements in the control-panel modals.

## 1.2.8 - 2026-07-29

- Fix: Same-document links (fragment-only `#anchor` / `#/spa/route` and query-only `?foo=bar` hrefs) are no longer rewritten with a language prefix on translated pages; they now stay relative to the current page instead of being rebased onto the language root (e.g. `#/booking/step-1?` no longer becomes `/fr/#/booking/step-1?`), fixing in-page and SPA navigation.
- Fix: Restores translation of the `srcset` attribute on `<img>` in translated pages. The responsible checker was dropped in the library split, so a translated `src` could previously coexist with original-language `srcset` URLs.
- Fix: The `media_enabled` and `external_enabled` options now correctly disable translation of media attributes (`src`, `data-src`, `srcset`) and external links respectively; a class-name matching bug previously made both toggles silent no-ops.
- Improvement: Migrates the bundled Weglot PHP library to weglot-php 1.9.9, whose parsing layer is now extracted into a separate weglot-parser-php 0.1.1 dependency; the scoped vendor and the DOM/regex checker wiring were updated accordingly, with no change to translation output.
- Improvement: Updates locked dependencies to clear all reported security advisories — guzzlehttp/guzzle 7.15.2, guzzlehttp/psr7 2.13.0, and craftcms/cms 5.10.12 (pulling fixed phpoffice/phpspreadsheet and web-auth/webauthn-lib) — all within the existing version constraints.

## 1.2.7 - 2026-07-13

- Fix: URLs using non-navigational schemes (mailto:, tel:, sms:, javascript:, data:, ...) are no longer rewritten with a language prefix; only http/https links go through language rewriting and slug translation.

## 1.2.6 - 2026-06-23

- Improvement: Adds htmx attribute URL rewriting support, extending the existing URL replacement pipeline to translate URLs found in `hx-*` attributes.
- Improvement: Reverses the release order and adds dates to each entry to improve display in the Craft CMS Plugin Store.

## 1.2.5 - 2026-06-18

- Fix: Language switcher links and `original_path` in the `weglot-data` payload are now computed from the source-language slug instead of the current translated slug, so every switcher link resolves to the correct per-language URL when browsing a translated page.
- Fix: Slug translation now matches every path segment against the slug maps instead of only the first one, so nested URLs (e.g. blog articles under `blog/`) are correctly redirected to their translated slug and resolved back to the source entry — fixing missing redirects and 404s on translated article URLs.
- Fix: In-memory slug-map memoization is now keyed by the requested destination-language set, preventing languages from being dropped within a single request depending on call order.

## 1.2.4 - 2026-05-18

- Improvement: Adds an opt-in Algolia integration (enableAlgolia) that injects a new frontend script to intercept Algolia search requests via xhook, reverse-translate the query parameter through the Weglot API (with caching/debouncing), and route the request through the Weglot proxy.
- Improvement: Upgrades craftcms/cms from 5.9.5 to 5.9.15 and removes the thamtech/yii2-ratelimiter-advanced dependency from composer.lock.
- Improvement: Updates ParserService::getParser() to propagate a sanitized incoming wg-editor-session request header as an outbound editor-session header (when present) and to always include a weglot-integration: Craft CMS Plugin header on Weglot HTTP requests.
- Improvement: Introduces a large set of new unit tests covering helpers, settings validation, URL eligibility/path rewriting, link replacement, hreflang generation, redirect language selection, slug translation, option parsing/excludes, and translation rendering/error handling; older targeted tests are removed/reworked into broader suites.

## 1.2.3 - 2026-03-09

- Improvement: ReplaceLinkService::replaceUrl() now detects when an input URL points to a different host than the current request and returns it unchanged, preventing Weglot language rewriting/slug translation from affecting external links.

## 1.2.2 - 2026-03-02

- No public release notes.

## 1.2.1 - 2026-02-19

- Improvement: merge all dynamic selectors into the whitelist before injecting Weglot scripts in the DynamicsService.

## 1.2.0 - 2026-02-16

- Improvement: Use navigator.sendBeacon instead of fetch when supported by the browser for sending data in the background.
- Improvement: Add slug translation support for better handling of translated URLs.
- Improvement: Add a new “dynamics” option in the admin interface to give more control over dynamic content behavior.
- Improvement: Add reverse translation support for Craft’s search base to improve multilingual search handling.

## 1.0.0 - 2026-01-12

- Add auto-redirect feature
- Add Pageviews feature
- Remove credentials, keepalive and header content type from header

## 0.2.2-beta - 2025-11-27

- Update cookieValidationKey and csrfParam configuration directly on construct

## 0.2.1-beta - 2025-11-27

- Update cookieValidationKey and csrfParam configuration

## 0.2.0-beta - 2025-11-20

- Update admin-design

## 0.1.5-alpha - 2025-11-06

- Update CI add classmap directly on composer.json

## 0.1.4-alpha - 2025-11-03

- Update CI add classmap directly on composer.json

## 0.1.3-alpha - 2025-10-31

- Update CI add classmap directly on composer.json

## 0.1.2-alpha - 2025-10-31

- Add classmap for autoload

## 0.1.1-alpha - 2025-10-31

- Use intermediate build directory for php-scoper output

## 0.1.0-alpha - 2025-10-28

- Initial alpha release