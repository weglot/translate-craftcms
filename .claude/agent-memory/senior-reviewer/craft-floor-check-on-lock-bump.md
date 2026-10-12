---
name: craft-floor-check-on-lock-bump
description: A composer.lock Craft bump is only tested at the locked version — check it against the declared floor (craftcms/cms 5.8.0) in a throwaway copy before passing it.
type: feedback
---

`composer.json` declares `craftcms/cms: ^5.8.0`, but CI and `/deploy-check` run on whatever `composer.lock` pins (5.11.5.1 since #69). No job tests the floor, so an API added after 5.8 can slip into `src/` unnoticed.

On any diff that moves `craftcms/cms` in the lock, check the floor in a copy of the repo in the scratchpad, never in the working tree:

```bash
composer config repositories.asset composer https://asset-packagist.org
composer config audit.block-insecure false
composer update craftcms/cms:5.8.0 -W
vendor/bin/phpstan --memory-limit=1G --no-progress
vendor/bin/phpunit tests --no-coverage
```

Baseline on 2026-10-12 (review of #69): phpunit 195 tests OK; PHPStan 1 error, `nullCoalesce.property` at `src/services/FrontEndScriptsService.php:161` — yii2 2.0.52 documents `AssetBundle::$baseUrl` as `string`, 2.0.55 as `string|null`. A docblock difference, not a regression: don't raise it. Anything beyond it is.

**Why:** the lock had already drifted from 5.8 to 5.10 without anyone testing the floor; #69 moved it to 5.11 for the `enshrined/svg-sanitize` advisories.
**How to apply:** every lock diff touching `craftcms/cms`. A CI matrix job on the floor would replace this check — a `.github/` change in its own PR.
