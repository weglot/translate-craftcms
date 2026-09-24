---
name: simple-html-dom-save-lowercases
description: The scoped simple_html_dom save() is not byte-identical (tag and attribute names lower-cased) — not a page mutation when plugin code uses the parser's own flags, because Parser::translate() does the same round trip right after.
type: feedback
---

With `str_get_html($html, true, true, \WG_DEFAULT_TARGET_CHARSET, false)`, `save()` lower-cases tag and attribute names (`<HTML>` → `<html>`, `viewBox` → `viewbox`) and keeps script / style bodies intact through its noise handling. `Parser::translate()` parses and saves every translated page with exactly these flags (`src/vendor/weglot/build/vendor-src/weglot-parser-php/src/Parser.php:325,408`), so a plugin step using them upstream (`TranslateService::injectAiDisclaimer()`, `fix/translate-ai-disclaimer`) adds no change of its own to the final output. Measured on the review of that branch.

**How to apply:** don't flag the lower-casing as a page mutation for code that uses the parser's flags. Do flag any other flags, another engine, or `DOMDocument` (`.claude/memory/standards/regex-and-xpath-on-html.md`).
