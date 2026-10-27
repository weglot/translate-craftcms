---
name: gate-off-equals-failure
description: A gate whose "off" result equals the failure result cannot be tested by its return value — count requests or assert on a path whose output differs.
type: feedback
---

On BUL-341 a test asserted `[] === getSlugMapsFromApiWithApiKey(...)` with `enableTranslatedSlugs` off. Without the gate, the method made a real request to `api.weglot.com`, failed (401 or connection error), and also returned `[]`: the assertion could not fail and the test hit the network. The redundant gate and the assertion were dropped; the gate on the cache path is tested with a seeded cache whose content would be returned without it.

**How to apply:** for a test of a feature switch or guard, ask what the method returns when the guard is removed. If it is the same value (empty array, null, false) on the failure path, ask for an HTTP seam with a request count or a seeded state whose output differs. Related: [[cache-hit-masking-in-tests]].
