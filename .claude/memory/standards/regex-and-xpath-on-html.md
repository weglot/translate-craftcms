---
name: regex-and-xpath-on-html
description: Rules for code that rewrites customer HTML with regexes or looks elements up in it — exclusion lookaheads scan the whole opening tag, interpolated values are escaped, CSS selectors go through simple_html_dom and pages are never re-serialised through DOMDocument, every attribute type gets its own test, and preg_* null returns are handled.
type: feedback
---

The link-rewriting and DOM code runs on every translated page of every customer site, on HTML this repo never sees.

- **An exclusion marker lookahead scans the whole opening tag**: `<a(?![^>]*wg-excluded-link)…`, as the `a`, `form`, `canonical`, `amp`, `meta`, `next`, `prev` patterns do (`src/helpers/HelperReplaceUrl.php:15,24-29`). Placing `(?!wg-excluded-link)` right before the attribute only checks that one position — the `datalink`, `dataurl`, `datacart` and `hx*` patterns (`:16-23`) do that, so a `wg-excluded-link` element with those attributes is still rewritten. Raised by Cursor Bugbot on #55, still open.
- **Escape every value interpolated into a pattern**: `preg_quote($value, $delimiter)` for regexes (`src/Plugin.php:280`).
- **Look elements up with the scoped simple_html_dom, never with XPath built from a selector or with `DOMDocument`**: `str_get_html($html, true, true, \WG_DEFAULT_TARGET_CHARSET, false)` then `find($selector, 0)`, the parser's own engine and flags (`weglot-parser-php/src/Parser.php:325`) — `TranslateService::injectAiDisclaimer()` is the reference. `DOMDocument::loadHTML()` / `saveHTML()` re-serialises the whole page and truncated inline scripts containing `</div>`; raw interpolation into XPath broke on `'` and compound selectors (#42 review, fixed on `fix/translate-ai-disclaimer`).
- **One test per attribute / URL shape**: each rewriting fix since 1.2.6 covered a shape the previous regex missed — `hx-*` (#55), non-navigational schemes (#60, CHANGELOG 1.2.7), same-document `#` / `?` refs (#63, CHANGELOG 1.2.8). A new pattern lands with a test for the plain case, the excluded case (`wg-excluded-link`) and the `actions/` skip.
- **`preg_replace()` / `preg_replace_callback()` return `null` on failure** (backtrack limit on large HTML): cast or check before chaining, as `weglotRenderDom()` does with `(string) $html` (`src/services/TranslateService.php:157`).

**Why:** three consecutive releases (1.2.6 – 1.2.8) fixed link-rewriting edge cases, and two bot findings on this code (#42, #55) were merged unaddressed.
**How to apply:** any change to `HelperReplaceUrl`, `ReplaceUrlService`, `ReplaceLinkService`, `TranslateService::injectAiDisclaimer()`, or a new DOM checker.
