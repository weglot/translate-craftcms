---
name: regex-and-xpath-on-html
description: Rules for code that rewrites customer HTML with regexes or queries it with XPath — exclusion lookaheads scan the whole opening tag, interpolated values are escaped, every attribute type gets its own test, and preg_* null returns are handled.
type: feedback
---

The link-rewriting and DOM code runs on every translated page of every customer site, on HTML this repo never sees.

- **An exclusion marker lookahead scans the whole opening tag, right after `<`**: `<a(?![^>]*wg-excluded-link)…`, `<(?![^>]*wg-excluded-link)([^>]+?)?data-link=…` (`src/helpers/HelperReplaceUrl.php:15-29`). Placed right before the attribute, `(?!wg-excluded-link)` only checks that one position and never fails — the `data-*` / `hx-*` patterns did that until #67 (`43ca0a0`) (raised on #55, inherited from the WordPress plugin).
- **A page-wide replace carries the same guard**: `simpleReplace()` and `replaceForm()` rebuild a regex from the tag start and the URL and `preg_replace()` it across the whole page, so an identical tag whose marker comes *after* the URL would be rewritten too. Their regex ends with `(?![^>]*wg-excluded-link)` (`src/services/ReplaceLinkService.php`); any new `replace*` method needs it, or must match up to `>` as `replaceA()` does.
- **Known limitation — `[^>]*` is not quote-aware**: a `>` inside an attribute value placed before the marker (`hx-on::after-request="e => go(e)"`, Alpine `@click="a => b"`) ends the scan early, and the excluded element is rewritten. Every pattern shares it, `a` included. Don't raise it as a regression of a lookahead change; a quote-aware scan belongs in its own PR.
- **Escape every value interpolated into a pattern or query**: `preg_quote($value, $delimiter)` for regexes (`src/Plugin.php:280`), and quote-safe literals for XPath. `TranslateService::cssToXPath()` interpolates selector parts raw into `//*[@id='$id']` (`src/services/TranslateService.php:256,263,272`) — a selector containing `'` breaks the query. Raised on #42, still open.
- **One test per attribute / URL shape**: each rewriting fix since 1.2.6 covered a shape the previous regex missed — `hx-*` (#55), non-navigational schemes (#60, CHANGELOG 1.2.7), same-document `#` / `?` refs (#63, CHANGELOG 1.2.8). A new pattern lands with a test for the plain case, the excluded case (`wg-excluded-link`) and the `actions/` skip.
- **`preg_replace()` / `preg_replace_callback()` return `null` on failure** (backtrack limit on large HTML): cast or check before chaining, as `weglotRenderDom()` does with `(string) $html` (`src/services/TranslateService.php:157`).

**Why:** three consecutive releases (1.2.6 – 1.2.8) fixed link-rewriting edge cases, and two bot findings on this code (#42, #55) were merged unaddressed.
**How to apply:** any change to `HelperReplaceUrl`, `ReplaceUrlService`, `ReplaceLinkService`, `TranslateService::cssToXPath()` / `injectAiDisclaimer()`, or a new DOM checker.
