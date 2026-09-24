---
name: open-bot-findings
description: Review-bot findings merged without a fix or a reply that are still present on master (checked 2026-09-24) — raise them when a diff touches the same code.
type: feedback
---

| # | Finding | Where (still true 2026-09-24) | Raised on | Severity to use |
|---|---|---|---|---|
| 1 | XPath injection: selector parts interpolated raw into XPath | `src/services/TranslateService.php:256,263,272` (`cssToXPath()`) | #42 (Cursor) | 🟠 |
| 2 | AI disclaimer text hardcoded in English; `loadHTML()` / `saveHTML()` round trip can alter inline scripts and entities; `saveHTML()` can return `false` | `src/services/TranslateService.php:210,215,232` | #42 (Cursor) | 🟡 |
| 3 | `buildSwitcherLink()` never applies the URL path prefix (`getPathPrefix()` is called nowhere in `src/`) — switcher links wrong on a site served under a sub-path | `src/services/OptionService.php:620` | #54 (Cursor) | 🟡 — confirm the site has a path prefix before raising higher |
| 4 | Scoped `DomFormatter::imageSource()` compares `$details['class']` with unscoped `'\Weglot\Parser\Check\Dom\ImageSource'` / `ImageDataSource` — the srcset-clearing branches are dead | `src/vendor/weglot/build/vendor-src/weglot-parser-php/src/Formatter/DomFormatter.php:125,130` | #62 (Cursor), confirmed while bootstrapping | 🟡 — fix via a `scoper.inc.php` patcher, never in the vendor file |

Also unverified from #47 (Algolia): unpinned CDN script without SRI, a promise rejection that can hang search, `xhook` loaded in the CP. Check the current code before raising any of them.

The misplaced `wg-excluded-link` lookahead (#55) was fixed in #67 (`43ca0a0`) and is no longer listed.

**Why:** #42 was approved without a comment and self-merged with the bot findings unanswered; #47, #54, #55 were merged with the findings open. Re-verified against `master` (`ef86515`) on 2026-09-24.
**How to apply:** when a diff touches one of these files, check whether the finding still holds (`sed -n` the line) and raise it at the severity above, citing the PR. When a row is fixed, delete it in the same PR.
