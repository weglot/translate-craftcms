---
name: vendor-update
description: "Update the php-scoper'd Weglot libraries in src/vendor/weglot (weglot-php, weglot-parser-php, weglot-translation-definitions, simple_html_dom, crawler-detect): pin the ref in the Makefile, make all with GH_PAT, re-check the scoper patchers and the plugin's checker wiring, composer dump-autoload, run the gates. Use when asked to \"update weglot-php\", \"bump the parser\", \"mettre à jour le vendor\", \"re-scope the libraries\", \"make all\", or on Class \"\\Weglot\\Vendor\\...\" not found right after make all or a version bump."
---

# Vendor update — scoped Weglot libraries

`src/vendor/weglot/` is generated and committed. Never edit it by hand; every change goes through this flow.

## 1. Pick the versions

In `Makefile`: `WEGLOT_PHP_REF` and `WEGLOT_PARSER_PHP_REF` pin exact tags (today `1.9.9` and `0.1.1`); leave one empty to take the latest tag. `weglot-translation-definitions`, `simple_html_dom` and `crawler-detect` always take their latest tag (optional `*_MIN` floors). Read the upstream changelogs for the range you cross — the 1.9.9 split moved the parser out of `weglot-php` and dropped a checker (`6ee4640`, `bf4e39d`).

## 2. Build

```bash
export GH_PAT=<token with read access to the private weglot/* repos>
make clean-build && make all   # checkout → composer run php-scoper → vendor-sync (rsync --delete into src/vendor/weglot)
```

`make clean-build` is required to pick up new refs: the checkout targets are directories under `build/vendor-src/` and are skipped when they already exist. Avoid plain `make clean`: it also deletes `src/vendor/weglot/` (`clean-src`), leaving the plugin broken if the checkout then fails (`git restore src/vendor/weglot` recovers it).

The Makefile reads the token from `GH_PAT` (`TOKEN ?= $(GH_PAT)`); never write it in the file. `build/` is the scratch area and is not committed.

## 3. Re-check what scoping does not handle

1. `git diff --stat src/vendor/weglot` — new or removed files under `Parser/Check/Dom/`?
2. **String class names** stay unscoped: `grep -rn "'\\\\\\\\Weglot\\\\\\\\" src/vendor/weglot/build/vendor-src --include='*.php'` — each hit that the code compares or instantiates needs a `patchers` entry in `scoper.inc.php` (as `DomCheckerProvider.php` has), then `make scoper vendor-sync` again.
3. **Checker wiring in the plugin**: `ParserService::getParser()` removes checkers by `'\\'.Foo::class` (`src/services/ParserService.php:109-123`); a renamed or dropped upstream checker must be updated there, and a dropped one may need a plugin-side checker in `src/checkers/dom/` (`ImageSourceSet`, `bf4e39d`).
4. `composer dump-autoload` here **and** in every Craft project consuming the plugin — the classmap is static (`.claude/memory/gotchas/scoped-vendor-pitfalls.md`).

## 4. Verify

`/deploy-check`, then render a translated page in the consuming project: text, an `<img srcset>`, an external link, with the media / external toggles on and off.

Commit: `chore(deps): migrate to weglot-php X.Y.Z …` with the vendor diff and the plugin-side adjustments in separate commits when they are independent (`6ee4640` then `bf4e39d`). CHANGELOG line: `- Improvement: Migrates the bundled Weglot PHP library to …` (1.2.8).

## When NOT to use this skill

- Composer dependencies of the plugin itself (`craftcms/cms`, Guzzle) → plain `composer update <pkg>` + `composer audit`; see `AGENTS.md` § Verification for Craft-pinned transitive advisories.

## Provenance and maintenance

| Fact | Re-verify |
|---|---|
| Pinned refs | `grep -n "_REF :=" Makefile` |
| Libraries scoped | `ls src/vendor/weglot/build/vendor-src` |
| Patchers | `grep -n "str_contains(\$filePath" scoper.inc.php` |
| Classmap autoload | `grep -n "classmap" -A2 composer.json` |
| Checker removal list | `grep -n "removeChecker\[\]" src/services/ParserService.php` |
