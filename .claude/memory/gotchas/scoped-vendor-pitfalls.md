---
name: scoped-vendor-pitfalls
description: The php-scoper'd Weglot libraries break in three silent ways — the classmap misses a new checker file (Class not found), class names written as strings are not prefixed (dead comparisons), and ::class has no leading backslash while DomCheckerProvider stores one (array_diff never matches).
type: gotcha
---

`src/vendor/weglot/build/vendor-src/` is php-scoper output (`scoper.inc.php`, prefix `Weglot\Vendor`), autoloaded through a **classmap** (`composer.json` `autoload.classmap`).

1. **Classmap drift.** The parser finds DOM checkers by `scandir()` of `Parser/Check/Dom/` and builds each class name from the file name; the classmap is a static list. A vendor update that adds or removes a checker file needs `composer dump-autoload` in every consuming Craft project, otherwise: `Class "\Weglot\Vendor\Weglot\Parser\Check\Dom\…" not found` at runtime (the `ImageSourceSet` break, fixed with `bf4e39d`).
2. **String class names are not scoped.** php-scoper rewrites `use` statements and `::class`, not class names inside string literals. `scoper.inc.php` patches exactly one file for this (`DomCheckerProvider.php`, the `patchers` entry). Every other literal still points at the unscoped namespace — e.g. `DomFormatter::imageSource()` compares `$details['class']` to `'\Weglot\Parser\Check\Dom\ImageSource'` and `'…\ImageDataSource'` while the file lives in `Weglot\Vendor\Weglot\Parser\Formatter` (`src/vendor/weglot/build/vendor-src/weglot-parser-php/src/Formatter/DomFormatter.php:3,125,130`), so the srcset / data-srcset clearing branches never run. After a vendor update, `grep -rn "'\\\\\\\\Weglot\\\\\\\\" src/vendor/weglot/build/vendor-src` lists the remaining literals.
3. **Leading backslash.** `DomCheckerProvider` stores checker names as `'\Fully\Qualified'`; `Foo::class` has no leading `\`. `removeCheckers()` with bare `::class` values never matched, which made the `media_enabled` / `external_enabled` toggles silent no-ops until `bf4e39d` prefixed `'\'` in `src/services/ParserService.php`.

Plugin-side checkers (`src/checkers/dom/`) are discovered the same way (`src/services/DomCheckersService.php`: `scandir()` + class name from file name), but are PSR-4 autoloaded, so they need no dump-autoload.

**Why:** each of the three produced a shipped regression or a dead code path (CHANGELOG 1.2.8, `bf4e39d`, #62 review).
**How to apply:** after `/vendor-update`, and when comparing or removing checker class names. Never edit `src/vendor/weglot/` by hand — add a `patchers` entry in `scoper.inc.php` instead.
