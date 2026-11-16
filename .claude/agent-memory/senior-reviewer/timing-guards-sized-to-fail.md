---
name: timing-guards-sized-to-fail
description: A performance-assertion test must be sized so the regression it guards exceeds the budget several times over, or it passes the bug.
type: feedback
---

The first "stays linear" sitemap test ran 5 000 entries against a 1 s budget; a quadratic rewrite measured 411 ms (per-match `preg_replace`) and 311 ms (`str_replace`) at that size, so it passed the regression it was written for (BUL-448 code review, 2026-11-13). At 20 000 entries the quadratic versions took 7.2 s / 4.7 s and the linear one ~80 ms, under the same budget.

**How to apply:** on every timing assertion, ask what the guarded regression measures at the test's size: it must take ≥ 5× the budget (size n up), or assert a ratio such as `t(4n) / t(n) < 8`. A test whose collaborators are stubbed guards only the code under test, not the production cost — say so in its docblock.
