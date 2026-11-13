---
name: url-decode-not-reescaped
description: The scoped Weglot Url urldecodes paths and ReplaceLinkService writes the result raw into HTML/XML — check escaping on any diff that emits a replaceUrl() result.
type: feedback
---

The scoped `Url` runs `urldecode()` on the path (`src/vendor/weglot/build/vendor-src/weglot-php/src/Util/Url.php:309`): `https://x.se/a%26b/c%20d` → `…/en/a&b/c d`, `…/%22%3E%3Cimg%20src=x%3E/a` → `…/en/"><img src=x>/a` (verified with the scoped class, BUL-448 plan review, 2026-11-13). `ReplaceLinkService` puts `$newUrl` into attribute replacements unescaped; WP added `esc_replacement()` for this. A raw `&` makes a sitemap unparseable; `">` in an attribute is injection.

**How to apply:** any diff writing a `replaceUrl()` result into HTML or XML → require decode once / encode once ([[decoded-request-paths]]): decode entities, rewrite, `rawurlencode` each path segment, then `htmlspecialchars` (`ENT_XML1` for XML). Demand tests with `%26`, `%20`, `%22%3E`, a non-ASCII slug and `&amp;` in the query.
