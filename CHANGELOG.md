# Release Notes for Weglot

## Unreleased

- Fix: `CHANGELOG.md` is now compatible with the Craft CMS Plugin Store.

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