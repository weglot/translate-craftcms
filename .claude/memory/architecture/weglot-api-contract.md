---
name: weglot-api-contract
description: Which Weglot hosts the plugin calls, how v1 and v2 keys are told apart and authenticate, where each endpoint is called, how the V2 host and workspace slug are resolved, and which env vars switch to staging.
type: architecture
---

## Hosts

Every base URL is a constant in `src/helpers/HelperApi.php:18-34` — production (`api.weglot.com`, `cdn.weglot.com`), V2 (`api.eu.weglot.com`, `cdn-v2.weglot.*`), staging (`*.weglot.dev`), US (`*.weglot.us`), dashboards (`dashboard.weglot.*` for V1, `auth.weglot.*` for V2). Never hardcode a Weglot host elsewhere; add a constant and a getter.

## Environment

`HelperApi::getEnvironment()` (`:36`): `WEGLOT_ENV` (`staging`, `env_us`, anything else = production), else staging when `WEGLOT_DEV` is a non-empty **string**, else production. Staging API and CDN hosts come from `WEGLOT_API_URL_STAGING` / `WEGLOT_CDN_URL_STAGING` (`:62`, `:76`). The `WEGLOT_DEV=true` trap: `.claude/memory/gotchas/weglot-dev-env-normalization.md`. `VersionService` always reads the routing file from the **production** CDN (`HelperApi::getProductionRootCdnBase()`, `:93`).

## v1 vs v2

`HelperApi::isV2ApiKey()` (`:108`): a key **not** starting with `wg_` is V2. Every branch below forks on it.

| Call | V1 | V2 | Where |
|---|---|---|---|
| Read project settings | `GET {api}/projects/settings?api_key=` | `GET {api}/project-settings?api_key=` | `src/services/OptionService.php:201`, `:219` |
| Save settings | `POST {api}/projects/settings?api_key=` | `PATCH {api}/projects/settings`, header `Authorization: Key <key>` | `OptionService.php:722`, `:782-788` |
| Validate key (settings form) | `GET {api}/project-settings?api_key=` | same | `src/services/UserApiService.php:48` |
| Translate | scoped `Client`, host `HelperApi::getApiUrl()` | host = `api_base_url` option, else `HelperApi::getApiUrlV2()`; header `Authorization: Key <key>` | `src/services/ParserService.php:43-56` |
| Workspace slug (V2 dashboard links) | — | `GET {api_base_url}/workspaces/current`, `Authorization: Key <key>` | `UserApiService.php:109-122` |

- The auth scheme is **`Key`, not `Bearer`**.
- `api_base_url` comes from the V2 project settings and carries the project's region; `getApiUrlV2()` is only the fallback (`HelperApi.php:113-122`).
- V2 project settings carry **no `organization_slug`** and no `api_key` (they expose `public_key`); the workspace slug needs the separate `workspaces/current` call. Its cache is keyed by API key and a failed lookup is not cached for the full TTL (`aaf6281`, `2333c59`).
- Dashboard URL shapes differ in structure, not just host: V1 `dashboard.weglot.*/workspaces/{organization_slug}/projects/{project_slug}/translations/languages/`, V2 `auth.weglot.*/{workspace_slug}/{project_slug}/languages` (`src/helpers/DashboardHelper.php`).

## Errors

The scoped `weglot-php` throws `Weglot\Vendor\Weglot\Client\Api\Exception\ApiError`; `TranslateService::processResponse()` turns it into an HTML comment (`src/services/TranslateService.php:116`). Settings reads return `['success' => false, 'result' => defaults]` and log with `Craft::error()` instead of throwing (`OptionService.php:205-209`).

**Why:** the plugin is a client of an API owned by another team; PR #59 (V2 connection) drew five review-bot findings on a wrong host / endpoint pair for one of the two versions.
**How to apply:** any change to a URL, header or option name above is a contract change — check the V1 **and** V2 path and the staging switch, and name the host + endpoint pair in the PR body.
