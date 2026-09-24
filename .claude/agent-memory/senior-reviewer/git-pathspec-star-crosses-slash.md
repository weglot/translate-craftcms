---
name: git-pathspec-star-crosses-slash
description: "Pathspec 'src/*.php' only matches top-level files" is a false positive — git's default pathspec `*` crosses `/`; only the :(glob) magic stops it.
type: feedback
---

codenudge raised on #66 that `git diff "$BASE" -- 'src/*.php' 'tests/*.php' ':!src/vendor'` in `.claude/skills/deploy-check/scripts/deploy-check.sh` misses nested files. It does not: `gitglossary(7)` ("pathspec") states that `Documentation/*.jpg` matches `Documentation/chapter_1/figure_1.jpg`. Reproduced on `4a1d6af` (touches only `src/services/OptionService.php` and `tests/unit/services/OptionServiceTest.php`): both files matched, 7 added lines captured; on `6ee4640` the `':!src/vendor'` exclusion drops the 135 vendor files. Answered on the PR, code unchanged.

**How to apply:** do not raise it, for this script or any other `git diff` / `git ls-files` pathspec. Raise it only when the pathspec uses the `:(glob)` magic, or for shell globbing / `find -name`, where `*` really does stop at `/`. When checking a `git diff` pipeline by hand in a Claude session, run it inside `bash -c`: the RTK hook rewrites the output of top-level `git diff` calls, and a `grep '^\+'` on it then counts 0.
