---
name: full-page-cache-freezes-request-data
description: Request-derived data written into the rendered HTML is cached with the page and served to every later visitor behind Blitz or a CDN.
type: feedback
---

A full-page cache (Blitz, a CDN) stores the HTML of the first request and serves it to every later visitor. Anything built from `getWeglotUrl()` / `getRequest()` (query, cookies, geo) and written into `#weglot-data`, the canonical or a `<link>` goes out with it. BUL-359: with Blitz ignoring query strings, the first visitor's `gad_source` / `gclid` reached every visitor's switcher links and the canonical; fixed with `HelperQueryString` (`fix/switcher-strip-tracking-params`).

**Why:** the plugin renders per request, but the page is not served per request; tests and local runs never show the leak.
**How to apply:** when a diff writes request-derived data into the HTML, ask whether that value is safe to serve to another visitor. Hreflang already drops the query (`HrefLangService.php:57`); in-content links are built from their own `href` and are not affected.
