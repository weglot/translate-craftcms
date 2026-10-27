---
name: cp-assets-through-bundle
description: Craft Cloud only pre-publishes asset bundle sourcePaths — a CP file published on its own with getPublishedUrl() 404s there.
type: feedback
---

Craft Cloud uploads `cpresources` to its CDN at build time, and only the `sourcePath` of each asset bundle. `getPublishedUrl('@weglot/craftweglot/resources/img/x.svg', true)` publishes `img/` as a directory of its own (`…-src-resources-img/`), which Cloud never uploads, so the 7 settings-screen images returned 404 on Cloud while working locally and on classic hosting, where Craft copies on demand (fixed `d4ce7b3`).

**How to apply:** in a CP template, keep the return of `registerAssetBundle()` and use `view.getAssetManager().getAssetUrl(bundle, 'img/x.svg')` (same CDN path as the bundle's CSS, keeps the `?v=` / build-id cache-busting). A new `getPublishedUrl(…, true)` on a file or sub-directory of a bundle → 🟠. In PHP, read `$assetManager->getBundle(AdminAsset::class)->baseUrl` (`FrontEndScriptsService`).
