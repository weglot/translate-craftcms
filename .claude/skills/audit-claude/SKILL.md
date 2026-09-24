---
name: audit-claude
description: "Audit the repo's Claude Code configuration — AGENTS.md import chain, .claude/memory, skills, the senior-reviewer agent and its memory, settings.json. Runs the mechanical lint (dead paths, dangling @imports, frontmatter, orphans, auto-load budget) and every skill's Provenance commands, then reviews for stale / wrong / duplicated content and stops for arbitration. Use when asked to audit .claude, clean up skills or memory, check for outdated rules, \"audite la config claude\", \"le tooling est-il à jour\", or on any PR touching .claude/ or AGENTS.md."
---

# Audit Claude — keep the config correct and cheap

Everything `AGENTS.md` imports is paid on every session; every skill is trusted blindly when it triggers. Drift here silently degrades every session — and this repo's line-number anchors (`src/Plugin.php:190`…) drift with every edit of those files.

## Contract

- Read-only until the checkpoint: no edit before the dev rules on the findings table.
- Evidence for every finding: "outdated" cites what changed — missing path, moved line, renamed symbol, merged PR, `git log -1 -- <file>`. No finding on "feels old".
- Surgical edits only; one home per fact (link, don't restate).
- Shared `settings.json` carries guard-rails only, never convenience hooks. Never add a secret-scanner allow marker.
- `.claude/settings.local.json` is personal (gitignored): report, never edit.
- Everything written is in English (`AGENTS.md` § Language and tone).

## A1 — Mechanical lint

```bash
python3 .claude/skills/audit-claude/scripts/claude-lint.py
```

`IMPORT` (every `@` line resolves), `PATH` (every backticked repo path exists — absence statements, WordPress-repo references (`SKIP_LINE_PATTERNS`) and gitignored outputs are skipped), `SKILL` (name = directory, description not truncated by an unquoted ` #`), `MEMORY` (frontmatter has name + description), `ORPHAN` (agents referenced nowhere), `BUDGET` (auto-loaded chain > 100 KB). `WEIGHT` lines are informational. Exit 1 on any failure.

The lint checks that a path exists, not that `file:line` still points at the right code. Then run the **Provenance and maintenance** commands at the end of every skill — a failing or changed result is a stale fact — and spot-check the line anchors of the auto-imported memory files (`sed -n '<line>p' <file>`).

## A2 — Semantic review

One read-only subagent per area, in parallel, `model: sonnet`: **skills**, **memory**, **agent + agent-memory**, **settings + AGENTS.md**. Each returns `file:line | category | issue | evidence | proposed action`. The main thread re-verifies every high-impact row before the checkpoint.

| Category | Hunt for |
|---|---|
| Stale | removed code, renamed class / component id / event, old version numbers, a bug listed as open that was fixed, a CI gap that was closed |
| Wrong | instruction contradicts code, CI (`.github/workflows/`) or another instruction — name both sources |
| Duplicated | same fact in several homes — propose the canonical one |
| Misplaced | always-imported memory that is rarely relevant (→ on-demand pointer), a runbook living in memory (→ skill) |
| Trigger | vague or overlapping skill `description`, missing phrases devs type (French and English) |
| Permissions | recurring read-only commands missing from the shared allowlist |

## Checkpoint — findings (STOP)

One table (in the dev's language), ranked correctness → tokens saved → hygiene. Ask the dev to mark each row fix / keep / delete / defer. Wait.

## A3 — Apply

Approved rows only; re-run A1 and the touched skills' Provenance commands. Ship as a `chore(claude): …` PR per `/weglot-craft-change-control`.

## Cadence

Monthly, and after each release; A1 alone on any PR touching `.claude/` or `AGENTS.md`.

## Provenance and maintenance

| Fact | Re-verify |
|---|---|
| `CLAUDE.md` is a symlink to `AGENTS.md` | `ls -la CLAUDE.md` |
| Memory frontmatter contract | `sed -n '1,40p' .claude/memory/README.md` |
| Personal settings are ignored | `git check-ignore -q .claude/settings.local.json && echo ignored` |
| Lint script | `.claude/skills/audit-claude/scripts/claude-lint.py` — from the `claude-tooling@weglot-engineering` bootstrap template (2026-09-24), `FOREIGN` / `SKIP_LINE_PATTERNS` adapted for this repo |
