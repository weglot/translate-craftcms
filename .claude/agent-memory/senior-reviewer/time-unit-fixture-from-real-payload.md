---
name: time-unit-fixture-from-real-payload
description: A time-unit guard that fails safe also fails silently — test it with a value copied from a real API payload, not a hand-picked one.
type: feedback
---

On BUL-341 the unit of `versions.slugTranslations` (seconds vs milliseconds) was assumed. A "future version" guard would have silently disabled the whole feature on a milliseconds value, and every test used small hand-picked integers (`1000`, `2000`) that pass under either unit. The unit was settled by reading a real cached project-settings payload (`1792681349`, seconds).

**How to apply:** when a diff compares an API timestamp with `time()` (window, max age, future guard), ask for one fixture copied from a real payload and named as such, and for the unit to be recorded in `.claude/memory/architecture/weglot-api-contract.md`. A guard whose wrong-unit failure mode is "feature silently off" → 🟡.
