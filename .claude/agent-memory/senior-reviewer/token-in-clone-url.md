---
name: token-in-clone-url
description: A git clone URL that interpolates a token (https://$(TOKEN)@github.com/…) echoes the secret in make output and stores it in .git/config — agents run these recipes, so the token lands in transcripts. Require a credential helper or gh repo clone.
type: feedback
---

The vendor `Makefile` first cloned the private Weglot repos with `https://$(TOKEN)@github.com/...` and no `@` prefix on the recipe lines, so make printed the expanded token and git kept it as the `origin` URL in `build/vendor-src/*/.git/config`. Since `/vendor-update` has agents run `make all`, the token would reach the Claude transcript. Fixed in `9bb7ba2`: token-free URLs plus `GIT_AUTH`, a one-shot credential helper reading `$$GH_PAT` at run time.

**How to apply:** on any Makefile, script or workflow change that touches `GH_PAT` or another token, check it never appears in a URL, an echoed command or a written file; raise 🟠 otherwise.
