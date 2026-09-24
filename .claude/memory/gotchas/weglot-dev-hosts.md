---
name: weglot-dev-hosts
description: Probing the Weglot API by hand — use the hosts the consuming project's .env declares (a dev key only resolves on *.weglot.dev, "Project settings not found" on .com), and auth.weglot.* is behind Cloudflare Access so status probing tells you nothing.
type: gotcha
---

- The plugin sits at `<craft-project>/plugins/weglot`; the consuming project's `.env` is `../../.env` from this repository root. Read its `WEGLOT_ENV`, `WEGLOT_API_URL_STAGING`, `WEGLOT_CDN_URL_STAGING` before any hand-made request — `HelperApi` resolves every host from them (`.claude/memory/architecture/weglot-api-contract.md`).
- A dev / staging key only exists on `*.weglot.dev`. The same key against the `.com` production hosts answers `Project settings not found`, which looks like a broken key.
- `auth.weglot.*` (the V2 dashboard) sits behind Cloudflare Access: every path, valid or not, 302s to a login. HTTP status probing cannot discover or validate its routes — read them from the WordPress plugin templates instead (`.claude/memory/process/wp-reference-implementation.md`).
- V2 calls authenticate with `Authorization: Key <apiKey>`, not `Bearer`.

**Why:** carried over from the previous `CLAUDE.md` § "Probing the Weglot API by hand", written after the V2 connection work (#59).
**How to apply:** before any `curl` against a Weglot host, or when a key "does not work" locally.
