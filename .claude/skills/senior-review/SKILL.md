---
name: senior-review
description: "The team's default review, at both ends of a change: the approach before coding (`/senior-review plan`) and the code before the PR — prefer it over generic review skills. Reviews the branch diff against master (or given files, globs or a PR number) with the senior-reviewer agent and relays its four-axis report: pertinence, consistency, quality, resilience (installed-base backward compat, PHP 8.2 / Craft 5.8 floor, CP security, V1/V2 API paths, HTML rewriting safety, tests). Use when asked to \"review my code\", \"fais une review\", \"relis ma PR\", \"check before PR\", \"vérifie mon code avant la PR\", and for an opinion on a plan: \"avis sur l'approche\", \"relis mon plan\"."
---

# Senior review

Use the `senior-reviewer` agent (`.claude/agents/senior-reviewer.md`) to review the current changes.

- `plan` (or a plan pasted as argument): **plan mode**, before any code. Pass the agent the problem statement and the full plan (files, approach, trade-offs) from the conversation — it has none of that context. Relay its verdict with the plan when asking the dev for approval.
- No arguments: the branch diff against `master` (default pre-PR workflow, after the code is written).
- Other arguments (`$ARGUMENTS`): files, globs or a PR number — pass them to the agent as the scope.

Relay the agent's full report (both modes) — four axes, findings with severity, synthesis and verdict. Do not summarise findings away.

If the report has a `## 🧠 Memory candidates` section, list them to the dev and ask which to keep; kept ones go to `.claude/agent-memory/senior-reviewer/` (one file per verdict, indexed in its `MEMORY.md`) in the same PR.

Run `/deploy-check` separately for the mechanical gates — the review judges, it does not replace them.
