---
name: per-match-preg-replace-quadratic
description: WP's modify_link* pattern — preg_match_all then one document-wide preg_replace per match — is O(n²); ask for a single preg_replace_callback.
type: feedback
---

WP's link rewriting (`modify_link_sitemap()`, `modify_link()`) collects matches, then runs one `preg_replace` over the whole document per match, interpolating the URL into the pattern. Benchmark on a synthetic urlset (BUL-448 plan review, 2026-11-13): 1k URLs 23 ms vs 0.3 ms, 5k 457 ms vs 0.8 ms, 20k 10.9 s vs 3.6 ms for a single `preg_replace_callback()`, identical output. The interpolated replacement also expands `$1` / `\1` found in a URL, and the 1500-char length cap exists only because the URL becomes a pattern.

**How to apply:** a port that iterates matches and replaces document-wide → 🟠 on large inputs (sitemaps, feeds, big pages): one `preg_replace_callback()`, `null` return handled (`.claude/memory/standards/regex-and-xpath-on-html.md`). See also [[wp-inherited-regex]].
