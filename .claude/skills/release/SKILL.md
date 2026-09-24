---
name: release
description: "Ship a plugin version on the Craft Plugin Store: CHANGELOG.md entry in the format the store parses, the release-notes PR, the git tag on the right commit, the craftcms/new-release dispatch that creates the GitHub release, and the manual dispatch workaround when the release does not appear. Use when asked \"prepare the release\", \"prépare la 1.3.0\", \"tag the release\", \"la release n'apparaît pas\", \"release missing on GitHub\", \"publish on the Plugin Store\", or to write release notes."
---

# Release

The version is the **git tag** — `composer.json` has no `version` field; Packagist reads the tag and the Craft Plugin Store reads `CHANGELOG.md` at that tag.

## 1. Release-notes PR (`docs(changelog): add X.Y.Z release notes`)

Model: #65 (1.2.8), #61, #57. Add at the top of `CHANGELOG.md`, below `# Release Notes for Weglot`:

```markdown
## X.Y.Z - YYYY-MM-DD

- Fix: <user-facing sentence>
- Improvement: <user-facing sentence>
```

- Heading format `## X.Y.Z - YYYY-MM-DD`, newest first — required by the Plugin Store (#56 "Plugin Store compatibility", CHANGELOG 1.2.6).
- Prefixes `Fix:` / `Improvement:` only; one line per merged PR since the last tag (`git log --oneline <last-tag>..master`); describe the effect on the site, not the code.
- Dependency security bumps get an `Improvement:` line naming the packages and versions (1.2.8).
- Run `/deploy-check` before opening the PR.

## 2. Tag — on the release-notes commit

After the PR is merged: `git fetch && git tag X.Y.Z origin/master && git push origin X.Y.Z` (ask before pushing a tag). No `v` prefix.

Tag the commit that **contains** the new CHANGELOG entry. `1.2.7` points at `5283c6e` (#60) while its notes landed afterwards in #61, so the store read a CHANGELOG without them; `1.2.8` points at the notes commit `5f3d392` (#65) — do that.

## 3. GitHub release (automatic)

`.github/workflows/create-release.yml` runs **only** on a `repository_dispatch` of type `craftcms/new-release`, sent by the Craft Plugin Store (id.craftcms.com) when it detects the tag. Pushing the tag alone does not create the release. The workflow maps `client_payload` to `ncipollo/release-action`: `version`→`name`, `tag`→`tag`, `notes`→`body`, `latest`→`makeLatest`, `prerelease`→`prerelease`.

### When the release does not appear

Almost always upstream, not GitHub:
1. The Craft Console's GitHub authorization expired or was revoked — re-authorize the Craft CMS GitHub app for the repo at id.craftcms.com (the durable fix).
2. Packagist does not list the tag — then Craft never learns about it.

```bash
gh auth status                                                                                   # needs the workflow scope + write access
gh run list --repo weglot/translate-craftcms --workflow=create-release.yml --event=repository_dispatch --limit 5
gh release list --repo weglot/translate-craftcms --limit 10
gh api repos/weglot/translate-craftcms/tags --jq '.[].name' | head
```

### Workaround — send the dispatch by hand

Only when the tag already exists (otherwise `ncipollo/release-action` creates it on the default branch). It creates the release on the existing tag and never moves it. Ask before sending.

```bash
cat > dispatch.json <<'EOF'
{
  "event_type": "craftcms/new-release",
  "client_payload": {
    "version": "X.Y.Z",
    "tag": "X.Y.Z",
    "latest": true,
    "prerelease": false,
    "notes": "- Fix: ...\n- Improvement: ..."
  }
}
EOF
gh api repos/weglot/translate-craftcms/dispatches --input dispatch.json   # HTTP 204, no output = success
gh run list --repo weglot/translate-craftcms --workflow=create-release.yml --event=repository_dispatch --limit 1
gh release view X.Y.Z --repo weglot/translate-craftcms
rm dispatch.json
```

`notes` = the CHANGELOG section of that version. This is a workaround; restore the Craft ↔ GitHub connection.

## When NOT to use this skill

- Commit / PR conventions → `/weglot-craft-change-control`. Gates → `/deploy-check`.

## Provenance and maintenance

| Fact | Re-verify |
|---|---|
| No version in composer.json | `grep -c '"version"' composer.json` (0) |
| Tags point at notes commits | `for t in $(git tag --sort=-creatordate \| head -3); do git log -1 --format="$t %s" $t; done` |
| Dispatch trigger and mapping | `cat .github/workflows/create-release.yml` |
| Heading format | `grep -m3 '^## ' CHANGELOG.md` |
