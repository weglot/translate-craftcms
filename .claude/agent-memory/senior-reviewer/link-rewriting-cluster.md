---
name: link-rewriting-cluster
description: Three consecutive releases fixed a URL shape the link-rewriting code mishandled — any change to ReplaceLinkService / HelperReplaceUrl needs a test per URL shape and attribute, not only the case the PR fixes.
type: feedback
---

- #55 (1.2.6, `3963583`) added `hx-*` attributes — and inherited a misplaced exclusion lookahead from the WordPress plugin: `wg-excluded-link` was ignored on `data-*` / `hx-*`, and the page-wide replace also rewrote an excluded twin tag. Fixed in #67 (`43ca0a0`) with a test per pattern (`tests/unit/helpers/HelperReplaceUrlTest.php`) and an end-to-end one (`tests/services/ReplaceUrlServiceTest.php`).
- #60 (1.2.7, `074e2e6`) — `mailto:`, `tel:`, `sms:` got a language prefix because they carry no host.
- #63 (1.2.8, `72cf88f`) — `#anchor`, `#/spa/route`, `?q=` rebased onto the language root.
- 1.2.3 — external hosts were rewritten.

Each fix added tests for its own shape only (`tests/unit/services/ReplaceLinkServiceTest.php`, `tests/unit/helpers/HelperReplaceUrlTest.php`).

**How to apply:** on a diff touching `src/services/ReplaceLinkService.php`, `src/services/ReplaceUrlService.php` or `src/helpers/HelperReplaceUrl.php`, check the tests cover: relative path, absolute internal, external host, non-http scheme, `#` / `?` only, `actions/…`, `wg-excluded-link`, and each attribute pattern touched. Missing shapes → 🟡 finding with the list.
