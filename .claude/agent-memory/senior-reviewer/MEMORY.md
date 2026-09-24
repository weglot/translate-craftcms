# Senior-reviewer memory

Operational notes private to the `senior-reviewer` agent. General conventions live in `.claude/memory/` — do not duplicate them here. Seeded 2026-09-24 from the Cursor Bugbot and codenudge findings on PRs #37–#65 (68 inline comments over nine months) and what the merged code did with them.

## Context

Review on this repo is almost entirely automated: Cursor Bugbot and codenudge comment on every PR, human approvals rarely carry a comment, and the author seldom replies to a finding. **A finding merged without a fix or a reply is not an accepted verdict** — several are still bugs on `master`.

## Recurring findings (raise them)

- [Open bot findings still on master](open-bot-findings.md) — five findings merged unaddressed; raise at their severity when a diff touches the same code, don't re-raise on unrelated diffs.
- [Link-rewriting edge cases](link-rewriting-cluster.md) — #55, #60, #63 each fixed one URL shape the regexes missed; demand a test per shape.
- [V1 / V2 host and endpoint pairs](api-version-host-pairs.md) — #59 drew five wrong-pair findings and two cache follow-ups.
- [PRs land as merge commits, not squash](merge-commits-not-squash.md) — every branch commit reaches `master`.
- [`composer run rector` writes](composer-rector-writes.md) — not a check; only `rector process --dry-run` is.
- [Token in a clone URL](token-in-clone-url.md) — echoed by make and stored in `.git/config`; use a credential helper.

## Reusable verdicts (don't re-raise at the same severity)

None recorded yet — no finding has an explicit, reasoned rejection in the review history. Add one here only when the dev states why a finding is accepted.
