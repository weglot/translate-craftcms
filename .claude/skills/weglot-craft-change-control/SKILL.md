---
name: weglot-craft-change-control
description: "How a change lands in this repo: branch naming, Conventional Commits, PR title and body shape, the review bots (Cursor Bugbot, codenudge) and how to answer them, the CI gates that block a merge, CHANGELOG entries. Use when asked \"ouvre la PR\", \"open the PR\", \"write the PR description\", \"quel nom de branche\", \"commit this\", \"prepare the commit message\", \"réponds aux commentaires du bot\", or before merging."
---

# Change control

## Branch

Off `master`: `<type>/<kebab-slug>` — `fix/replace-url-same-document-references`, `feat/translate-htmx-urls`, `improvement/connect-to-weglot-v2`, `docs/changelog-1.2.8`, `chore/…` (`gh pr list --state merged`). No ticket id convention. Before switching branch: `AGENTS.md` § Git safety.

## Commits

Conventional Commits, `type(scope): short description`, lower-case, English: `fix(replace-url): leave same-document references untouched`. Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `style`. Scopes in use: `replace-url`, `slug`, `parser`, `options`, `settings`, `dashboard`, `translate`, `deps`, `quality`, `phpstan`, `changelog`, `build`, `claude`. One logical change per commit; the body explains *why* and what a reviewer must know (`bf4e39d`, `4a1d6af` are good models).

**No AI attribution** — no `Co-Authored-By` trailer, no "Generated with" footer.

## Pull request

- Title = the Conventional Commit of the squash; GitHub appends ` (#N)`. Since #54 titles follow this; older free-form titles (`Improvement/connect to weglot v2`) are not the model.
- No template file, no labels, no CODEOWNERS. Body, on the model of #63:

```markdown
## Problem
<what breaks, for whom, with the failing input — e.g. the exact href>

## Fix
<the approach; host + endpoint pair for any V1/V2 API change>

## Tests
<tests added, commands run and their result>

## QA
<manual check in a Craft project, when user-visible>
```

  Terse: don't restate the diff. Cursor appends a `CURSOR_SUMMARY` block by itself — leave it.
- Open as draft until `/deploy-check` passes: `gh pr create --draft --base master`.

## Review

Review comes almost entirely from bots: Cursor Bugbot and codenudge comment on every PR; a human approval (usually without comments) follows. **Answer every bot finding** — fix it, or reply why not. A finding merged without either is not a decision: several were (#42, #47, #54, #55) and are still open bugs, listed in `.claude/agent-memory/senior-reviewer/open-bot-findings.md`. Run `/senior-review` before asking for the human approval.

## Merge gates

`.github/workflows/code-quality.yml` on every PR and push to `master`: `composer validate --strict --no-plugins`, `composer audit`, php-cs-fixer dry run, Rector dry run, `phpunit tests/unit/`. PHPStan and `tests/services/` are **not** in CI — `/deploy-check` covers them (`.claude/memory/gotchas/ci-gate-gaps.md`).

## Changelog

`CHANGELOG.md` entries are written in the release PR (`/release`), not in each feature PR (#57, #61, #65). Write the PR title so it can become the `- Fix: …` / `- Improvement: …` line.

## When NOT to use this skill

- Running the gates → `/deploy-check`. Reviewing the diff → `/senior-review`. Shipping a version → `/release`.

## Provenance and maintenance

| Fact | Re-verify |
|---|---|
| Branch and title conventions | `gh pr list --state merged --limit 20 --json title,headRefName --jq '.[] \| "\(.headRefName)  \(.title)"'` |
| Body model | `gh pr view 63 --json body --jq .body \| grep '^## '` |
| Reviewers | `gh pr view 63 --json reviews --jq '.reviews[].author.login' \| sort -u` |
| CI steps | `grep -n "run:" .github/workflows/code-quality.yml` |
| No template, no CODEOWNERS | `ls .github/` |
