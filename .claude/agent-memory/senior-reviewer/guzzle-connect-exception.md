---
name: guzzle-connect-exception
description: In Guzzle 7 ConnectException is not a RequestException — any catch (RequestException) around a Weglot call lets DNS, timeout and TLS failures through.
type: feedback
---

`ConnectException` extends `TransferException`, not `RequestException`. `UserApiService::getUserInfo()` and `fetchWorkspaceSlug()` caught only `RequestException`, so an unreachable API answered 500 on the API-key check and crashed the CP settings page, and a test that hit the real API failed CI with cURL error 35 (fixed `c810340`). Remaining sites at the time: `OptionService.php:166`, `SlugService.php:131`.

**How to apply:** any `catch (RequestException)` around an outbound Weglot call → 🟠 when the exception reaches a page or CP render, 🟡 when an outer `\Throwable` catch masks it. Catch `GuzzleException`, and narrow with `instanceof RequestException && hasResponse()` before reading the status.
