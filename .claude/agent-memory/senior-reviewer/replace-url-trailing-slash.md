---
name: replace-url-trailing-slash
description: ReplaceLinkService::replaceUrl() always appends a trailing slash — a WP habit Craft has no redirect for; SEO-facing URLs built with it 301 on slash-less sites.
type: feedback
---

`ReplaceLinkService::replaceUrl()` appends `/` to every rewritten path (`src/services/ReplaceLinkService.php:65-67`). WordPress backs this with `manage_trailing_slash()`, which redirects every URL to its slashed form; Craft has no equivalent. On a site that strips trailing slashes every rewritten URL 301s: hagastrand.se, `/en/erbjudande/the-morning-reset/` → 301 → `/en/erbjudande/the-morning-reset` (checked 2026-11-13, BUL-448 plan review). Canonical and hreflang (`HrefLangService.php:57-66`) are built without the slash.

**How to apply:** on any new use of `replaceUrl()` for SEO-facing URLs (sitemaps, canonical, hreflang, og:url) → 🔴: build the URL as hreflang does (`createUrlObject()->getForLanguage($lang, false)` + `getPathForLanguage()`) and test `generated URL === hreflang href`. In-content links: raise it as a known issue, not a blocker on unrelated diffs.
