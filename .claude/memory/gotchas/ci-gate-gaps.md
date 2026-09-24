---
name: ci-gate-gaps
description: CI does not run PHPStan and runs only tests/unit/ — a green CI is not a green PHPStan, and tests/services/ never runs there. CI runs on PHP 8.2; local machines are often newer.
type: gotcha
---

`.github/workflows/code-quality.yml` (PHP **8.2**, on every PR and push to `master`) runs, in order: `composer validate --strict --no-plugins`, `composer audit`, `vendor/bin/php-cs-fixer fix --dry-run --diff`, `vendor/bin/rector process --dry-run --no-progress-bar`, `vendor/bin/phpunit tests/unit/ --no-coverage`.

Not run by CI (commented out as "TODO SOON"):
- **PHPStan** (`vendor/bin/phpstan analyse`) — the level 6 / 100 % type-coverage gate exists only on developer machines.
- **`composer-dependency-analyser`** — currently reports `psr/http-message` used as a shadow dependency (`src/services/OptionService.php:808`, `Psr\Http\Message\ResponseInterface`), so it would fail today.

Not covered by CI either: `tests/services/` (`composer run test` runs `tests/`, CI only `tests/unit/`).

Local PHP is often newer than 8.2 (8.4 on the machine that wrote this file): `config.platform.php = 8.2` pins dependency resolution, and Rector / php-cs-fixer target 8.2 sets, but the interpreter still accepts 8.3+ runtime functions — `composer.json` requires `php >= 8.2`.

**Why:** measured on 2026-09-24 — all gates green locally, dependency analyser exit 1; workflow file read the same day.
**How to apply:** before a PR run `/deploy-check`, which runs PHPStan and all of `tests/`. Never read "CI green" as "PHPStan green". Adding PHPStan to CI is a `.github/` change outside this tooling.
