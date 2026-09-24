# Weglot — Craft CMS plugin

Craft CMS 5 plugin (`weglot/craft-translate`, handle `weglot`) distributed on the Craft Plugin Store that translates a site through the Weglot API. Single unit: PHP 8.2+ (`weglot\craftweglot\` namespace, services as Yii2 components), a small control-panel UI (Twig + JS/SCSS built by Vite). Client of Weglot's own API through php-scoper'd copies of `weglot-php`, `weglot-parser-php`, `simple_html_dom`, `weglot-translation-definitions` and `crawler-detect` (`src/vendor/weglot/`, never edited by hand).

`CLAUDE.md` is a symlink to this file — edit `AGENTS.md`.

## Behavioral principles

These apply before any project-specific rule. Bias toward caution over speed; on trivial tasks, use judgment.

**Think before coding.** State assumptions explicitly. If multiple interpretations exist, present them — don't pick silently. If a simpler approach exists, say so and push back when warranted. If something is unclear, stop, name what is confusing, and ask.

**Simplicity first.** Minimum code that solves the problem. No features beyond what was asked, no abstractions for single-use code, no configurability nobody requested, no error handling for impossible scenarios. If you write 200 lines and it could be 50, rewrite it. Would a senior engineer call it overcomplicated? Then simplify.

**Surgical changes.** Every changed line traces to the request. Don't "improve" adjacent code, comments or formatting; don't refactor what isn't broken; match existing style. Remove the imports/variables/functions *your* change orphaned; mention unrelated dead code, don't delete it.

**Goal-driven execution.** Turn tasks into verifiable goals ("add validation" → "write tests for invalid inputs, then make them pass"; "fix the bug" → "write a test that reproduces it, then make it pass"). For multi-step work, state a brief plan with a check per step and loop until verified.

**Plan approval.** For any significant task (touches several files, adds a feature, or changes existing behaviour): propose a plan — files, approach, trade-offs — **run `/senior-review plan` on it**, present the plan with the reviewer's verdict, and **wait for explicit approval** before writing a single line of code. Anything beyond a trivial single-line fix or typo is significant.

**Review loop.** Every change goes: branch → problem stated → plan reviewed (`/senior-review plan`) → dev approval → code → diff reviewed (`/senior-review`), 🔴 / 🟠 findings fixed → `/deploy-check` → PR. Review before the gates: fixing a finding can break them.

## Verification before claiming

A claim that something works cites a command run in this session and its output. Never cite a self-written test or a mock as proof a behaviour exists in production code. Report failing tests with their output.

After writing any code, run the quality gate and fix every error before calling the task done — `/deploy-check` runs all of it, or by hand:

```bash
composer run check-cs     # php-cs-fixer, dry run
composer run phpstan      # level 6, 100 % type coverage — NOT run by CI
composer run rector       # must report no un-applied transformation
composer run test         # all of tests/ — CI runs only tests/unit/
composer audit
```

CI (`.github/workflows/code-quality.yml`) skips PHPStan and `tests/services/`: a green CI is not a green gate (`.claude/memory/gotchas/ci-gate-gaps.md`).

For `composer audit`, report any advisory. Vulnerabilities in transitive dependencies pinned by `craftcms/cms` (Twig, Symfony, Yii2) are usually resolved by a Craft upgrade rather than by this plugin — do not bump them in isolation without confirming Craft's version constraints allow it.

## Landmarks

Canonical locations, so a lookup is a read and not a repo-wide grep. Verify a path before relying on it.

| Thing | Where |
|---|---|
| Components (service ids), events, URL rules, settings page, settings save | `src/Plugin.php` — `config()` `:63`, `attachEventHandlers()` `:190`, `afterSaveSettings()` `:102` |
| Public events | `Plugin::EVENT_REGISTER_WHITELIST_SELECTORS` / `EVENT_REGISTER_DYNAMICS_SELECTORS` (`src/Plugin.php:57-58`) |
| Weglot hosts, environment, v1/v2 key detection | `src/helpers/HelperApi.php` |
| Env vars | `WEGLOT_ENV`, `WEGLOT_DEV`, `WEGLOT_API_URL_STAGING`, `WEGLOT_CDN_URL_STAGING` — read in `HelperApi` with `App::env()`; the consuming project's `.env` is `../../.env` |
| Settings model | `src/models/Settings.php` |
| Link-rewriting regexes | `src/helpers/HelperReplaceUrl.php` |
| Plugin-side DOM checkers | `src/checkers/dom/` (auto-discovered, `src/services/DomCheckersService.php`) |
| Scoped vendor + how it is built | `src/vendor/weglot/` ← `Makefile` (pins `WEGLOT_PHP_REF`, `WEGLOT_PARSER_PHP_REF`) + `scoper.inc.php` |
| CP template, JS/SCSS sources, compiled assets | `src/templates/_settings.twig`; `src/resources-src/`; `src/resources/` (`AdminAsset.php` lists what is loaded) |
| French strings | `src/translations/fr/weglot.php` |
| Test bootstrap | `tests/bootstrap.php` (headless Craft, no DB) |
| Quality configs | `.php-cs-fixer.dist.php`, `phpstan.dist.neon`, `rector.php`, `phpunit.xml.dist` |
| CI / release | `.github/workflows/code-quality.yml`, `.github/workflows/create-release.yml` |
| Release notes | `CHANGELOG.md` (`## X.Y.Z - YYYY-MM-DD`, newest first — read by the Plugin Store) |

## Project memory (auto-imported)

Shared knowledge — see [`.claude/memory/README.md`](.claude/memory/README.md) for the schema and the promote / retire workflow.

**Run `/audit-claude` once a month.** Outdated tooling is worse than none: an agent trusts a stale path, line number or rule and acts on it with confidence.

Architecture:
@.claude/memory/architecture/plugin-bootstrap.md
@.claude/memory/architecture/translation-pipeline.md

Standards:
@.claude/memory/standards/craft-php-standards.md

Testing:
@.claude/memory/testing/writing-tests.md

Cross-cutting gotchas:
@.claude/memory/gotchas/ci-gate-gaps.md
@.claude/memory/gotchas/weglot-dev-env-normalization.md
@.claude/memory/gotchas/scoped-vendor-pitfalls.md

## On-demand standards

- `.claude/memory/architecture/weglot-api-contract.md` — read before touching an API URL, header, auth mode (v1/v2), `api_base_url` or the workspace / dashboard links. Always-on: never hardcode a Weglot host outside `HelperApi`; V2 auth is `Authorization: Key`, not `Bearer`.
- `.claude/memory/standards/regex-and-xpath-on-html.md` — read before editing `HelperReplaceUrl`, `ReplaceUrlService`, `ReplaceLinkService`, `cssToXPath()` or a DOM checker. Always-on: exclusion lookaheads scan the whole tag, `(?![^>]*wg-excluded-link)`.
- `.claude/memory/standards/admin-frontend-rules.md` — read before touching `src/templates/`, `src/resources-src/` or a CP controller action. Always-on: no inline JS or CSS in Twig; values go through `data-*`.
- `.claude/memory/standards/public-extension-points-backward-compat.md` — read before touching `Plugin::EVENT_*`, `RegisterSelectorsEvent`, a `Settings` property, a URL rule or `composer.json` `extra`. Always-on: never rename or re-type them.
- `.claude/memory/gotchas/silent-translation-cdn-fallback.md` — read on any "not translated, no Weglot error comment" report.
- `.claude/memory/gotchas/weglot-dev-hosts.md` — read before any hand-made request to a Weglot host.
- `.claude/memory/process/wp-reference-implementation.md` — read when the request mentions "WP" or a WordPress plugin behaviour. Always-on: never guess the checkout path; sweep all of `templates/admin/v2/` before concluding V2 lacks something.

## Commands

```bash
composer install && npm install        # setup
npm run build                          # Vite → src/resources/ (npm run dev = watch) — needs Node ≥ 20.19 / 22.12
composer run test                      # all tests
./vendor/bin/phpunit --filter <name>   # one test
make all                               # re-scope the Weglot libraries (GH_PAT needed) — see /vendor-update
```

Tech stack: PHP ≥ 8.2 (`composer.json`, `config.platform.php` 8.2, CI on 8.2), Craft CMS ^5.8.0, Yii2, Guzzle ^7.10, PHPUnit 11, PHPStan 2 (level 6 + strict rules + type coverage), Rector 2, php-cs-fixer (`@Symfony`, `@PHP82Migration`), php-scoper 0.18, Vite 7 + Sass.

## Git, branches, PRs

- Branch off `master` as `<type>/<kebab-slug>` (`fix/replace-url-same-document-references`, `improvement/connect-to-weglot-v2`).
- Commits and PR titles follow Conventional Commits: `type(scope): short description` — types `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `style` (e.g. `fix(translate): handle empty API response gracefully`). One logical change per commit. PRs are squash-merged.
- PR body and review etiquette: `/weglot-craft-change-control`.
- **No AI attribution**: no `Co-Authored-By` in commit messages, no "Generated with Claude Code" or similar footer in PR descriptions.
- Never commit code that fails the quality gate; always run `composer audit` before committing — do not commit with unresolved advisories on dependencies this plugin can update.

## Git safety

Before **any** `git checkout`, `git switch`, `git checkout -b` or `git stash`:

1. Run `git status` **and** `git log --oneline master..HEAD`, and report both to the user.
2. Name explicitly what is uncommitted and whether the current branch has commits of its own (and whether they are pushed).
3. **Stop and ask** what to do with that work — commit it here, stash it, or carry it over.

A branch name is not proof the work is saved. Never stage files you did not intentionally edit; before destructive commands (`checkout --`, `reset --hard`, `clean`) set staged and unstaged work aside first.

## Formatters & codemods

Never run `composer run fix-cs`, Rector without `--dry-run`, or any codemod on the whole tree when only a few files changed: scope them (`vendor/bin/php-cs-fixer fix <files>`, `vendor/bin/rector process <files>`). If a tool rewrites unrelated files, revert them. Never run php-scoper output into `src/vendor/weglot/` by hand — `/vendor-update`.

## Language and tone

The user may write in French; everything produced — code, comments, commit messages, PR titles, docs, `.claude/` files — is in **English**. PR bodies are terse: problem, fix, how it was verified.

## Comments

Comments explain *why* a non-obvious decision was made (a workaround for a third-party bug, a Craft quirk), never *what* the code does. PHPDoc blocks (`@param`, `@return`, `@throws`) are encouraged where they aid PHPStan.

## General principles

- Small functions with one responsibility; meaningful names.
- No hardcoded configuration (URLs, secrets, timeouts, feature flags) — plugin settings, `HelperApi` constants or env vars.
- Minimise dependencies; justify each new one.
- Unit tests for any new functionality.
- When a production bug is fixed, record the lesson where it prevents recurrence (a memory file, a standard, a playbook row).

## Tooling

Prefer the **PhpStorm MCP** tools over `grep` / raw Bash for exploration when they are available: `mcp__phpstorm__get_file_text_by_path`, `find_files_by_name_keyword`, `find_files_by_glob`, `search_in_files_by_text`, `search_in_files_by_regex`, `search_symbol`, `get_symbol_info`, `list_directory_tree`. Fall back to `grep` only when no MCP equivalent exists. Exclude `src/vendor/` from searches unless the question is about the scoped libraries.

## Skills

Invoke with `/skill-name` instead of running the steps by hand. Pick the repo skill over a generic one.

| Need | Skill |
|---|---|
| Review an approach before coding ("avis sur l'approche", "relis mon plan") | `/senior-review plan` |
| Code review before a PR ("fais une review", "review my code") | `/senior-review` — mandatory before every PR; built-in `/code-review` only for a pure bug hunt, `/security-review` for security only |
| Run the quality gates before a PR | `/deploy-check` — once, after `/senior-review`, just before opening the PR |
| A plugin-specific bug, error string or customer report | `/weglot-craft-debugging-playbook` before any generic debug skill |
| Setup, Vite assets, tests, env vars, staging | `/weglot-craft-build-and-qa` |
| Craft events, CP controllers and security, supported versions, public API | `/weglot-craft-host-platform` |
| Branch / commit / PR / review-bot conventions | `/weglot-craft-change-control` |
| Update the scoped Weglot libraries | `/vendor-update` |
| Ship a version (CHANGELOG, tag, Plugin Store release) | `/release` |
| Audit this `.claude/` config | `/audit-claude` |
| Prune merged branches | `/cleanup` |

Subagent models: read-only locate / explore fan-outs run with `model: sonnet`; review (`senior-reviewer`), planning and anything that writes stay on the session model.
