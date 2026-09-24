---
name: admin-frontend-rules
description: Control-panel UI rules — no inline JS or CSS in Twig, server values through data-* attributes, Craft.postActionRequest for CP calls, Craft.t for strings, and the settings template's one inline-style debt.
type: feedback
---

- **No inline JS or CSS in Twig.** JavaScript goes in `src/resources-src/js/admin.js`, styles in `src/resources-src/scss/admin.scss`; `src/templates/_settings.twig` holds markup only.
- **Server values reach JS through `data-*` attributes** on the element the script binds to (`data-weglot-api-key`, `data-weglot-activate`, `data-weglot-api-status`, `data-weglot-reset-btn` — `_settings.twig:26,34,44,252`), never a `<script>` block or an inline `style=""`.
- **CP calls use `Craft.postActionRequest()`** — it carries the CSRF token (`admin.js:47`, `:325`). The matching controller action requires admin, POST and JSON (`src/controllers/ApiController.php:27-29`).
- **UI strings in JS use `Craft.t('weglot', '…')`** (`admin.js:42,45,54`), with the French translation in `src/translations/fr/weglot.php`.
- Build and where compiled files land: `/weglot-craft-build-and-qa` § Assets — the build does not produce every file `AdminAsset` loads.

Known debt: `_settings.twig:68` toggles the V1 fields with an inline `style="display:none"` (d4ab896, V2 preparation). Do not copy it; replace it with a class toggled from `admin.js` when that block is next touched.

**Why:** the rules come from the previous `CLAUDE.md` § Frontend Assets; the CSRF / admin guard pairing is visible in `ApiController`.
**How to apply:** any change to `src/templates/`, `src/resources-src/` or a CP controller action.
