---
name: cleanup
description: "Post-merge teardown of local branches and worktrees whose PR is merged on GitHub. Shows a dry-run table, deletes only what the dev confirms. Use when asked to clean up after a merged PR, prune merged branches, \"nettoie les branches\", \"supprime les branches mergées\", or remove a finished worktree."
allowed-tools: Bash, Read, AskUserQuestion
---

# Cleanup — post-merge local teardown

## Contract

- Never touches `master`, the current branch, or the worktree the session runs in.
- Deletes only what has a **merged** PR on GitHub. Open, draft, closed-unmerged or no PR → keep.
- Keeps any branch with commits not contained in its merged PR head (unpushed work).
- Keeps every stash (`git stash list`) — report them, never drop one.
- Shows the table of every candidate, kept ones included, and waits for confirmation.

## Why GitHub decides, not only `git branch --merged`

PRs land as merge commits (`ef86515 Improvement/connect to weglot v2 (#59)` has two parents), so `git branch --merged origin/master` does list merged branches. It cannot tell a branch whose PR was merged from one that was merged by hand or never had a PR, nor spot commits added after the merge — hence the GitHub state check below.

## C1 — Scan (read-only)

```bash
git fetch --prune
current="$(git branch --show-current)"
for b in $(git for-each-ref --format='%(refname:short)' refs/heads/); do
  [ "$b" = master ] || [ "$b" = "$current" ] && continue
  pr="$(gh pr list --head "$b" --state all --json number,state,headRefOid --jq '.[0] | "\(.number) \(.state) \(.headRefOid)"' 2>/dev/null)"
  printf '%s\t%s\n' "$b" "${pr:-none}"
done
```

For each `MERGED` row, check the local tip is contained in the merged head: `git merge-base --is-ancestor <branch> <headRefOid>` (exit 0 → nothing unpushed). If the head object is missing locally, keep the branch.

Worktrees: `git worktree list` — a worktree whose branch is a `delete` row goes first; a dirty worktree (`git -C <path> status --porcelain` not empty) stays unless the dev insists.

## C2 — Dry-run table + confirmation

| branch | PR | state | unpushed | verdict | reason |
|---|---|---|---|---|---|

Then ask: delete every `delete` row, a subset, or abort.

## C3 — Apply (confirmed rows only)

Worktrees first (`git worktree remove <path>`), then `git branch -d <branch>` (a refusal means commits are not on `origin/master`: stop and report, never escalate to `-D` without the dev's word), then `git worktree prune`. Remote branches: if one survived the merge, ask before `git push origin --delete`.

## C4 — Report

Deleted rows, kept rows with their reason, stashes found, any command that failed, verbatim.

## Provenance and maintenance

| Fact | Re-verify |
|---|---|
| PRs land as merge commits | `git log --first-parent --format='%h %p %s' -5 master` — two parents per `(#N)` line |
| Default branch | `gh repo view --json defaultBranchRef --jq .defaultBranchRef.name` |
