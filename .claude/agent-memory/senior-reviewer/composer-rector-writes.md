---
name: composer-rector-writes
description: The `rector` composer script is bare `rector` = `rector process`, which rewrites src/ and tests/ — it is not a check; the only safe gate is vendor/bin/rector process --dry-run.
type: feedback
---

`composer.json` defines `"rector": "rector"`; Rector's default command is `process` without `--dry-run`, so `composer run rector` applies every transformation to the whole tree. The previous `CLAUDE.md` prescribed it as a gate and the first version of the bootstrap allowlisted it in `.claude/settings.json`; both were fixed after the review of #66. CI and `/deploy-check` use `vendor/bin/rector process --dry-run --no-progress-bar`.

**How to apply:** flag 🟠 any doc, allowlist entry or script that calls `composer run rector` as a verification step, and any Rector run without `--dry-run` on files the change did not touch (`AGENTS.md` § Formatters & codemods).
