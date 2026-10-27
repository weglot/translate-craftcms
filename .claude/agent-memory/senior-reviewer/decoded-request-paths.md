---
name: decoded-request-paths
description: Craft hands the router a decoded request path — external data matched against it must be decoded on ingest and encoded once, when the URL is built.
type: feedback
---

On BUL-341 the redirect builder decoded-then-encoded slug segments, but the lookups compared the decoded request segments (yii `Request::resolvePathInfo()` calls `urldecode()`, `vendor/yiisoft/yii2/web/Request.php`) with maps keyed by whatever the API returned. A percent-encoded slug from the API would have redirected to a URL that then 404'd. Fixed by `rawurldecode()` in `SlugV2Service::sanitizeMap()`.

**How to apply:** when a diff matches request path segments against external data (API slugs, settings, URLs from the dashboard), ask where the data is normalised. Decode once on ingest, encode once when building a URL; a lookup on raw external data → 🟡. Remember `+` reaches the router as a space.
