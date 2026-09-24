---
name: weglot-dev-env-normalization
description: WEGLOT_DEV=true or WEGLOT_DEV=1 does NOT switch to staging — App::env() turns them into bool/int and HelperApi only accepts a non-empty string; use WEGLOT_ENV=staging. WEGLOT_DASHBOARD_URL_STAGING is read nowhere.
type: gotcha
---

`HelperApi::getEnvironment()` switches to staging on `WEGLOT_DEV` only when `App::env('WEGLOT_DEV')` returns a non-empty **string** (`src/helpers/HelperApi.php:47-49`). `craft\helpers\App::env()` passes every value through `normalizeValue()`, which returns `true` / `false` / `null` for those words and an int or float for numeric strings (`vendor/craftcms/cms/src/helpers/App.php:494-516`).

| `.env` line | `App::env()` returns | Environment |
|---|---|---|
| `WEGLOT_DEV=true` | `true` | **production** |
| `WEGLOT_DEV=1` | `1` | **production** |
| `WEGLOT_DEV=false` | `false` | production |
| `WEGLOT_DEV=yes` | `'yes'` | staging |
| `WEGLOT_ENV=staging` | `'staging'` | staging (read first, `:38-45`) |

The symptom of the trap: a staging key answers "Project settings not found" / an invalid-key error, because the calls go to the `.com` hosts.

Also: `WEGLOT_DASHBOARD_URL_STAGING` appears in consuming projects' `.env` but no code reads it — the dashboard hosts are the constants `DASHBOARD_URL_STAGING` / `AUTH_URL_STAGING` (`HelperApi.php:28-31`).

`tests/unit/helpers/HelperApiTest.php` covers `WEGLOT_ENV` only; the boolean `WEGLOT_DEV` case is untested.

**Why:** found while bootstrapping the Claude tooling (2026-09-24) by reading `HelperApi` against Craft's `App::env()`; fixing the check is a separate `fix(env)` change.
**How to apply:** target staging with `WEGLOT_ENV=staging` plus `WEGLOT_API_URL_STAGING` / `WEGLOT_CDN_URL_STAGING`. Any code reading an env var with `App::env()` must expect `bool|int|float|string|null`, not a string.
