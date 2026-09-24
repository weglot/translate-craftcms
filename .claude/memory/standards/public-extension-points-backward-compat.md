---
name: public-extension-points-backward-compat
description: The plugin's public surface for third-party Craft code and installed sites — the two EVENT_REGISTER_* events and their payload, the Settings model properties, the weglot/* routes — never rename or re-type them; a behaviour change that could surprise an installed site ships opt-in.
type: feedback
---

What installed sites and third-party plugins depend on:

| Surface | Where | Contract |
|---|---|---|
| `Plugin::EVENT_REGISTER_WHITELIST_SELECTORS` = `'registerWhitelistSelectors'`, `Plugin::EVENT_REGISTER_DYNAMICS_SELECTORS` = `'registerDynamicsSelectors'` | `src/Plugin.php:57-58`, fired in `src/services/DynamicsService.php:48,52` | event class `RegisterSelectorsEvent`, public `array<int, array{value: string}> $selectors` (`src/events/RegisterSelectorsEvent.php`) |
| Settings properties (`apiKey`, `languageFrom`, `languages`, `enableDynamics`, `enableAlgolia`, `dynamicsWhitelistSelectors`, `dynamicsAllowedUrls`, `hasFirstSettings`, `showBoxFirstSettings`) | `src/models/Settings.php:13-26` | stored in the project config and overridable from `config/weglot.php`; renaming one silently resets it on every site |
| Routes `weglot/router/forward`, `weglot/api/*` | `src/Plugin.php:268-289`, `src/controllers/` | URL rules and CP JS depend on the names |
| Plugin handle `weglot`, class `weglot\craftweglot\Plugin` | `composer.json` `extra` | changing either uninstalls the plugin from Craft's point of view |

Rules:

- **Never rename an event constant or its string value, never change the payload shape.** Add a new event and keep the old one firing.
- **Never rename or re-type a `Settings` property** without a migration of the stored value.
- **A behaviour change that could break an installed site ships opt-in** (a setting, default = old behaviour) — the Algolia integration shipped behind `enableAlgolia` (CHANGELOG 1.2.4).
- The scoped vendor classes are **not** public: third parties must not depend on `Weglot\Vendor\…`.

**Why:** the plugin is distributed on the Craft Plugin Store; a customer's module listening to `registerDynamicsSelectors` breaking on update is a support ticket, not a failing test.
**How to apply:** review any diff touching `Plugin::EVENT_*`, `RegisterSelectorsEvent`, `Settings` properties, URL rules or `composer.json` `extra`.
