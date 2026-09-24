---
name: regex-and-xpath-on-html
description: Rules for code that rewrites customer HTML with regexes or queries it with XPath — exclusion lookaheads scan the whole opening tag, interpolated values are escaped, every attribute type gets its own test, and preg_* null returns are handled.
type: feedback
---

The link-rewriting and DOM code runs on every translated page of every customer site, on HTML this repo never sees.

- **An exclusion marker lookahead scans the whole opening tag**: `<a(?![^>]*wg-excluded-link)…`, as the `a`, `form`, `canonical`, `amp`, `meta`, `next`, `prev` patterns do (`src/helpers/HelperReplaceUrl.php:15,24-29`). Placing `(?!wg-excluded-link)` right before the attribute only checks that one position — the `datalink`, `dataurl`, `datacart` and `hx*` patterns (`:16-23`) do that, so a `wg-excluded-link` element with those attributes is still rewritten. Raised by Cursor Bugbot on #55, still open.
- **Escape every value interpolated into a pattern or query**: `preg_quote($value, $delimiter)` for regexes (`src/Plugin.php:283`), and quote-safe literals for XPath. `TranslateService::cssToXPath()` interpolates selector parts raw into `//*[@id='$id']` (`src/services/TranslateService.php:256,263,272`) — a selector containing `'` breaks the query. Raised on #42, still open.
- **One test per attribute / URL shape**: each rewriting fix since 1.2.6 covered a shape the previous regex missed — `hx-*` (#55), non-navigational schemes (#60, CHANGELOG 1.2.7), same-document `#` / `?` refs (#63, CHANGELOG 1.2.8). A new pattern lands with a test for the plain case, the excluded case (`wg-excluded-link`) and the `actions/` skip.
- **`preg_replace()` / `preg_replace_callback()` return `null` on failure** (backtrack limit on large HTML): cast or check before chaining, as `weglotRenderDom()` does with `(string) $html` (`src/services/TranslateService.php:157`).

**Why:** three consecutive releases (1.2.6 – 1.2.8) fixed link-rewriting edge cases, and two bot findings on this code (#42, #55) were merged unaddressed.
**How to apply:** any change to `HelperReplaceUrl`, `ReplaceUrlService`, `ReplaceLinkService`, `TranslateService::cssToXPath()` / `injectAiDisclaimer()`, or a new DOM checker.
