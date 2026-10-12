---
name: craft-php-standards
description: PHP / Craft rules of this plugin that php-cs-fixer, PHPStan and Rector do not enforce — strict comparisons, no empty(), Craft APIs over native PHP, Guzzle through Craft, cache keys, services as components, comments, naming, and the known debt not to copy.
type: feedback
---

Tool-enforced already (don't restate in reviews): `declare(strict_types=1)` and Symfony style (`.php-cs-fixer.dist.php`), PHP 8.2 sets + dead code + code quality (`rector.php`), PHPStan level 6 + strict rules + 100 % param / return / property type coverage (`phpstan.dist.neon`).

Not enforced by any tool:

- Always `===` / `!==`; never `==` / `!=` (`CONTRIBUTING.md` § 4).
- Never `empty()` — test what you mean: `'' !== $string`, `[] !== $array`, `null !== $value`, `$count > 0` (`CONTRIBUTING.md` § 4). Zero occurrences in `src/` and `tests/` today — keep it that way.
- **Craft APIs over native PHP** (`CONTRIBUTING.md` § 5):
  - HTTP through `\Craft::createGuzzleClient()` — never `new \GuzzleHttp\Client`, `file_get_contents()` or `curl_*`. Every outbound call sets a `timeout` (`src/services/UserApiService.php:57`, `src/services/VersionService.php:54`). The scoped Weglot `Client` in `ParserService` is the one exception.
  - Env vars through `craft\helpers\App::env()`, request data through `\Craft::$app->getRequest()`, never `$_SERVER` / `$_GET` / `$_POST` / `$_COOKIE`.
  - Cache through `\Craft::$app->getCache()` with a `weglot_` key prefix; a per-project value includes the API key or its hash in the key (`weglot_workspace_slug` fix, `aaf6281`).
  - Errors are logged with `\Craft::error()` / `\Craft::warning()` and `__METHOD__` as category; a failed external call degrades (defaults, empty string) instead of throwing into the page render (`src/services/OptionService.php:205-209`).
  - Missing page → `throw new \yii\web\NotFoundHttpException()`; redirect → `\Craft::$app->getResponse()->redirect()`. Never `die()` / `exit()`.
- **Services are components**: declared in `Plugin::config()`, reached through `Plugin::getInstance()` (`.claude/memory/architecture/plugin-bootstrap.md`). Never `new` one in `src/`.
- **Escape what you build as HTML in PHP**: `craft\helpers\Html::encode()` or `htmlspecialchars(…, ENT_QUOTES, 'UTF-8')` (`src/services/TranslateService.php:154`). Twig auto-escapes; be careful with `|raw` and `Template::raw()` (`CONTRIBUTING.md` § 6).
- User-facing strings go through `\Craft::t('weglot', '…')` with an English source string; the French translation lives in `src/translations/fr/weglot.php`.
- Comments explain *why*, never *what*. PHPDoc (`@param`, `@return`, `@throws`) is encouraged where it feeds PHPStan (array shapes, `list<string>`).
- Names are explicit English, no abbreviations (`CONTRIBUTING.md` § 3).

## Known debt — do not copy

| Where | Breaks | Status |
|---|---|---|
| `src/services/RedirectService.php:23-33,123` | reads `$_SERVER['HTTP_ACCEPT_LANGUAGE']` / `HTTP_CF_IPCOUNTRY` directly | open; fix with `getRequest()->getHeaders()` in its own PR |
| `src/Plugin.php:335` | reads `$_COOKIE['weglot_allow_private']` directly | open |
| `src/Plugin.php:174-181`, `src/models/Settings.php:68` | French source strings in `\Craft::t()` / log messages | open |

The English AI-disclaimer text in `TranslateService::injectAiDisclaimer()` is deliberate, not debt: it is injected before `$parser->translate()`, so Weglot translates it, and it is appended to the target's last text node with no wrapper, as the WordPress plugin does (`add_ai_disclaimer()`). It stays English inside an excluded block and is sent as source text when `languageFrom` is not `en`.

**Why:** these are the rules of `CONTRIBUTING.md` and the previous `CLAUDE.md` that no configured tool checks; the debt rows were found while writing this file (2026-09-24).
**How to apply:** every PHP change; grep your diff for `empty(`, `$_SERVER`, `new Client` and unprefixed cache keys. Touching a debt row's file does not oblige you to fix it — mention it.
