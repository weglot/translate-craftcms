# Release Notes for Weglot

## 0.1.0-alpha
- Initial alpha release
## 0.1.1-alpha
- Use intermediate build directory for php-scoper output
## 0.1.2-alpha
- Add classmap for autoload
## 0.1.3-alpha
- Update CI add classmap directly on composer.json
## 0.1.4-alpha
- Update CI add classmap directly on composer.json
## 0.1.5-alpha
- Update CI add classmap directly on composer.json
## 0.2.0-beta
- Update admin-design
## 0.2.1-beta
- Update cookieValidationKey and csrfParam configuration
## 0.2.2-beta
- Update cookieValidationKey and csrfParam configuration directly on construct
## 1.0
- Add auto-redirect feature
- Add Pageviews feature
## 1.1
- Remove credentials, keepalive and header content type from header 
## 1.2.0
- Improvement: Use navigator.sendBeacon instead of fetch when supported by the browser for sending data in the background.
- Improvement: Add slug translation support for better handling of translated URLs.
- Improvement: Add a new “dynamics” option in the admin interface to give more control over dynamic content behavior.
- Improvement: Add reverse translation support for Craft’s search base to improve multilingual search handling.
## 1.2.1
- Improvement: merge all dynamic selectors into the whitelist before injecting Weglot scripts in the DynamicsService.
## 1.2.3
- Improvement: ReplaceLinkService::replaceUrl() now detects when an input URL points to a different host than the current request and returns it unchanged, preventing Weglot language rewriting/slug translation from affecting external links.
## 1.2.4
- Improvement: Adds an opt-in Algolia integration (enableAlgolia) that injects a new frontend script to intercept Algolia search requests via xhook, reverse-translate the query parameter through the Weglot API (with caching/debouncing), and route the request through the Weglot proxy.
- Improvement: Upgrades craftcms/cms from 5.9.5 to 5.9.15 and removes the thamtech/yii2-ratelimiter-advanced dependency from composer.lock.
- Improvement: Updates ParserService::getParser() to propagate a sanitized incoming wg-editor-session request header as an outbound editor-session header (when present) and to always include a weglot-integration: Craft CMS Plugin header on Weglot HTTP requests.
- Improvement: Introduces a large set of new unit tests covering helpers, settings validation, URL eligibility/path rewriting, link replacement, hreflang generation, redirect language selection, slug translation, option parsing/excludes, and translation rendering/error handling; older targeted tests are removed/reworked into broader suites.
