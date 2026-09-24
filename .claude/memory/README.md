# Claude Code shared memory

This directory holds the codebase knowledge that is **auto-loaded** by Claude Code through the `@.claude/memory/...` imports in `AGENTS.md` (`CLAUDE.md` is a symlink to it), or read on demand from its `## On-demand standards` section.

Goal: every teammate using Claude Code (or any LLM that reads `AGENTS.md`) starts from the same validated baseline — conventions, traps and patterns that were paid for once in a bug, a review or a release.

## Structure

- `architecture/` — how the plugin is wired into Craft and how it talks to the Weglot API (not obvious from a single file)
- `standards/` — code rules that php-cs-fixer, PHPStan and Rector do **not** enforce
- `testing/` — how tests are written and run in this repo
- `gotchas/` — known traps, false friends, known debt, deliberate decisions not to drive-by-fix
- `process/` — how to work with sources outside this repo (the WordPress reference plugin)

Each file follows a frontmatter + body format:

```markdown
---
name: short-kebab-slug
description: one-line, scannable
type: architecture | feedback | gotcha | testing | process
---

The rule itself.

**Why:** why it exists — cite a PR number, a commit hash or a `file:line`.
**How to apply:** where and when it applies concretely.
```

A rule without a source (PR, commit, `file:line`) does not belong here.

## When to promote a personal memory to the repo

Personal memories live in `~/.claude/projects/<project-slug>/memory/`. Promote one here when:

- It is **reusable** by another dev (not a personal preference, not a path on your machine).
- It is **backed by evidence** in this repo (merged PR, commit, code).
- It is **not tied to a ticket in flight** — pending-branch notes stay local.
- It will likely stay true for **3+ months**.

Keep local: machine-specific paths (the WordPress plugin checkout), docs change-request ids, in-flight branch status.

## How to add a shared rule

1. Create the `.md` in the right sub-directory.
2. Add the frontmatter (`name`, `description`, `type`).
3. Wire it from `AGENTS.md`: `@`-import it only if it applies to most sessions; a rule for a narrow task goes under `## On-demand standards` as `` - `path` — read before <trigger>. Always-on: <one-line rule> ``. Budget: the auto-loaded chain stays ≤ 100 KB, enforced by `python3 .claude/skills/audit-claude/scripts/claude-lint.py` (`BUDGET`).
4. Ship it in the PR that motivated it, or in a `chore(claude): …` PR.

## How to remove an outdated rule

1. Delete or update the `.md` in the same PR as the code change that made it stale.
2. Remove its `@`-import or on-demand pointer from `AGENTS.md`.
3. Run `python3 .claude/skills/audit-claude/scripts/claude-lint.py` — a dangling import fails it.

## Repo-shared workflow assets

Skills live in `.claude/skills/`, the review agent in `.claude/agents/`, its verdict memory in `.claude/agent-memory/senior-reviewer/`. `ls` them; `AGENTS.md` § Skills says which one to pick.
