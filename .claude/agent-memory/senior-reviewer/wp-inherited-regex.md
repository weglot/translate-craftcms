---
name: wp-inherited-regex
description: A regex or guard ported from the WordPress plugin is not proof of correctness — the misplaced wg-excluded-link lookahead came from WP; test a ported pattern's exclusion and edge shapes on their own merits.
type: feedback
---

The `data-link`, `data-url` and `data-cart-url` patterns were ported verbatim from the WordPress plugin (`translate-wordpress` `src/helpers/class-helper-replace-url-weglot.php:23-25`), and the `hx-*` ones (#55) copied their shape. All carried a `(?!wg-excluded-link)` lookahead that can never fail; it shipped in both plugins until #67 (`43ca0a0`). The same happened with loose PHP idioms copied from WP on #48 (`if ($x)` on nullable strings, `!empty()`), fixed later in `44a3ddf`.

**How to apply:** when a diff ports a pattern, guard or method from WP, do not accept "same as WordPress" as evidence. Ask for tests of the exclusion, the edge shapes and this repo's strict rules. When the port reveals a bug that WP shares, suggest reporting it to the WordPress plugin as well.
