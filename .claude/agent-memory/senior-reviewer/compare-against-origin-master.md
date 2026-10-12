---
name: compare-against-origin-master
description: Local master is often stale — diff a branch and check its own commits against origin/master, not master.
type: feedback
---

Local `master` is rarely pulled. On 2026-10-12 it sat at `ef86515` while `origin/master` was at `f4f5dd1`: `git log master..HEAD` listed 11 commits on a branch that had none of its own, all already merged through #66 and #68.

**Why:** a stale base inflates the review diff with merged work, and makes the git-safety check (`AGENTS.md` § Git safety) report unsaved commits that are already on `master`.
**How to apply:** `git fetch origin` first, then review `git diff origin/master...HEAD` and count own commits with `git log origin/master..HEAD`.
