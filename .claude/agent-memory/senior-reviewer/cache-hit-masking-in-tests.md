---
name: cache-hit-masking-in-tests
description: A test that calls a caching service twice must reset or seed the cache between calls, or the second call is served from cache and the assertion passes for the wrong reason.
type: feedback
---

On the Weglot 2 slug branch (BUL-341), a test that turned a setting off and called `SlugV2Service` again still passed when the gate was removed: the second call was answered from the per-language cache written by the first, so no request was made either way. The test was rewritten to seed or flush the cache explicitly before the call under test.

**How to apply:** for any test calling a service with a Craft cache or in-memory memo twice (or after a `setUp()` that warmed it), check that the call under test cannot be satisfied by the cache. Ask for a mutation check (remove the branch, the test must fail) when in doubt. A test that cannot fail → 🟡.
