---
name: full-page-parse-cost
description: Measured cost of one extra full-page simple_html_dom parse on the frontend path — ~+1.5 ms at 60 KB, ~+93 ms at 345 KB (vs ~20 ms for DOMDocument) — use it to judge any new per-request DOM pass.
type: feedback
---

Benchmarked on 2026-09-24 (PHP 8.4, synthetic pages of repeated cards with links, entities and images), parse + `find()` + `save()` + `clear()`, average of 5 runs:

| Page | DOMDocument + XPath | simple_html_dom |
|---|---|---|
| 60 KB | 20.4 ms | 21.9 ms |
| 345 KB | 20.1 ms | 112.7 ms |

The cost grows faster than linearly with page size. The AI-disclaimer fix (`fix/translate-ai-disclaimer`) accepted it deliberately (dev decision): it only applies when a disclaimer selector is set, and the faster `DOMDocument` path truncated inline scripts. The Weglot parser pays one such parse on every translated page anyway.

**How to apply:** a diff adding another full-page parse, regex pass or DOM walk on the frontend path → ask whether it can reuse an existing pass, and raise 🟡 with these numbers if it runs on every translated page view. Don't raise the disclaimer's own parse again; a single-parse design (injecting into the parser's tree) is a possible future refactor, not a finding.
