---
name: deploy-check
description: "Run every quality gate of the plugin locally before opening a PR — composer validate, composer audit, php-cs-fixer, Rector, PHPStan (which CI does not run) and PHPUnit on all of tests/ (CI runs only tests/unit/), plus diff warnings (debug calls, empty(), superglobals, raw HTTP clients, vendor or asset changes). Use when asked to \"run the checks before the PR\", \"lance les checks\", \"lance la pipeline qualité\", \"vérifie que la CI passe\", \"lance phpstan\", \"run the quality gate\", or after finishing an implementation."
allowed-tools: Bash, Read, Grep, Glob
---

# Pre-PR check

```bash
bash .claude/skills/deploy-check/scripts/deploy-check.sh            # every gate (default)
bash .claude/skills/deploy-check/scripts/deploy-check.sh --ci-only  # exactly what CI runs
```

Exit 0 = every gate passed. Exit 1 = at least one failed; the summary names them.

## What it runs

| Step | Command | In CI? |
|---|---|---|
| 1 | `composer validate --strict --no-plugins` | yes |
| 2 | `composer audit` | yes |
| 3 | `vendor/bin/php-cs-fixer fix --dry-run --diff` (= `composer run check-cs`) | yes |
| 4 | `vendor/bin/rector process --dry-run --no-progress-bar` | yes |
| 5 | `vendor/bin/phpstan --memory-limit=1G --no-progress` (= `composer run phpstan`) | **no** |
| 6 | `vendor/bin/phpunit tests --no-coverage` (= `composer run test`) | only `tests/unit/` |

CI source: `.github/workflows/code-quality.yml`; why the default goes beyond it: `.claude/memory/gotchas/ci-gate-gaps.md`.

Warnings (never flip the exit code): local PHP ≠ 8.2; `var_dump` / `dd` / `empty()` / `$_SERVER`-family / `new Client(` / `file_get_contents` / `curl_*` added in the branch diff against `master`; `src/vendor/weglot` changed (→ `composer dump-autoload` in the Craft project); `src/resources-src` changed with no compiled file in `src/resources`.

Not run: `composer-dependency-analyser` (commented out in CI, currently fails on `psr/http-message`), the Vite build, a real Craft project.

## Workflow

1. Run it once, just before opening the PR — not during iteration.
2. On failure, fix and re-run until exit 0. A Rector or php-cs-fixer failure is fixed by applying it **to the files you touched only** (`AGENTS.md` § Formatters & codemods).
3. `composer audit` advisories on packages pinned by `craftcms/cms`: report, don't bump in isolation (`AGENTS.md` § Verification).
4. Never silence PHPStan with a new `ignoreErrors` entry or a baseline without saying so in the PR body.

## Provenance and maintenance

| Fact | Re-verify |
|---|---|
| CI steps and order | `grep -n "run:" .github/workflows/code-quality.yml` |
| PHPStan still absent from CI | `grep -n "phpstan" .github/workflows/code-quality.yml` |
| Composer script names | `grep -n '"check-cs"\|"phpstan"\|"rector"\|"test"' composer.json` |
| CI PHP version | `grep -n "php-version" .github/workflows/code-quality.yml` |
