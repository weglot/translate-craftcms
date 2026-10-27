---
name: redact-v1-guzzle-messages
description: A Guzzle exception message carries the full URI — on a Weglot 1 call that includes ?api_key=, so never log or return it unredacted.
type: feedback
---

Weglot 1 calls send the key as `?api_key=` in the query string; Guzzle puts the whole URI in exception messages (`cURL error 35: … for https://…?api_key=…`). Logging `$e->getMessage()` writes the key to `storage/logs`; returning it puts it in CP JSON. Weglot 2 calls send `Authorization: Key`, so their messages are safe. `UserApiService::requestError()` logs only the exception class and the cURL errno (`c810340`).

**How to apply:** for any diff logging or returning a Guzzle exception message, check which key version the call uses. A V1 call with an unredacted message → 🟠. Fix: log class + `getHandlerContext()['errno']`, or redact `api_key=[^&\s]+` first. Related: [[api-version-host-pairs]].
