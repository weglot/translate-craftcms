---
name: create-url-object-per-item
description: RequestUrlService::createUrlObject() rebuilds OptionService::getExcludeUrls() on every call — about 43 % of the per-URL cost when looped over sitemap entries or links.
type: feedback
---

`createUrlObject()` (`src/services/RequestUrlService.php:35-49`) calls `getExcludeUrls()`, which builds a new `Regex` per excluded path (and reads the destination languages for entries with `excluded_languages`), then the vendor `Url::getAllUrls()` loops languages × exclusions. Measured with real collaborators, 10 languages / 30 exclusions: ~57 µs per sitemap `<loc>`, ~24 µs of it in `getExcludeUrls()`; 50 000 URLs ≈ 2.8 s per uncached hit (BUL-448 code review, 2026-11-13). Link rewriting in HTML pays the same per link.

**How to apply:** a diff looping over `createUrlObject()` / `replaceUrl()` per item → 🟡 with a measurement: hoist the per-request invariants or memoise `getExcludeUrls()` next to the options (reset with them). Not yet fixed on master at the time of writing.
