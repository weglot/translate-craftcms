---
name: merge-commits-not-squash
description: PRs land as merge commits titled after the PR, not squashed — every branch commit reaches master, so intermediate commits must meet the conventions and git branch -d / --merged work.
type: feedback
---

`git log --first-parent master` shows a two-parent merge for every PR (`ef86515 Improvement/connect to weglot v2 (#59)` → parents `5f3d392 2fc1a3b`); the repo uses `merge_commit_title: PR_TITLE`, which is why merge subjects read `Title (#N)`. The bootstrap tooling first assumed squash-merge in three files; the review of #66 caught it.

**How to apply:** challenge any doc, skill or advice that reasons from squash-merging ("the PR title is the only commit that matters", "use `-D`, `-d` refuses merged branches"). On a PR, each commit must be a clean Conventional Commit that passes the gates on its own — raise a 🟡 on "wip" / "fix rector issue" commits.
