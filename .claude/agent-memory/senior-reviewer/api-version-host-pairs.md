---
name: api-version-host-pairs
description: A change on the V1/V2 API surface is where this repo's bugs cluster — check the host, endpoint, auth header and cache key for both key versions, four cases each.
type: feedback
---

On #59 (V2 connection) Cursor Bugbot raised five findings on a wrong host / endpoint pair for one of the two versions (V1 host used for a V2 call, wrong PATCH endpoint…), plus two cache defects fixed right after in `aaf6281` (workspace slug cache not scoped to the API key) and `2333c59` (failed lookup cached for the full TTL). `0519225` then fixed V2 fields read under their V1 name (`public_key` vs `api_key`, `custom_settings` `{}` wiping defaults), and `4a1d6af` a V1-only default applied to V2.

The contract table: `.claude/memory/architecture/weglot-api-contract.md`.

**How to apply:** for any diff touching `HelperApi`, `OptionService` fetch / save methods, `UserApiService`, `ParserService::getClient()` or `DashboardHelper`, ask: V1 key? V2 key? V2 with `api_base_url` set? Staging (`WEGLOT_ENV=staging`)? Each must hit the right host with the right auth (`?api_key=` vs `Authorization: Key`), read the right field names, and cache under a key that includes the API key. A case that silently degrades → 🟠.
