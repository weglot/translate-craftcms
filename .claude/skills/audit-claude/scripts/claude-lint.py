#!/usr/bin/env python3
"""claude-lint.py — read-only mechanical checks of the Claude Code config in this repo.

Checks (each failure is one line, prefixed by its check id):
  IMPORT    every `@path` line in AGENTS.md / */AGENTS.md resolves (relative to that AGENTS.md)
  PATH      every backticked repo path in .claude/ (minus runs/, worktrees/) and AGENTS.md exists
  SKILL     every SKILL.md has `name` == its directory and a `description` not truncated by ` #`
  MEMORY    every .claude/memory/**/*.md (minus README) has frontmatter with name + description
  ORPHAN    every file in .claude/{prompts,commands,agents} is referenced somewhere else
  BUDGET    root chain + each app chain (what one session auto-loads) stays <= BUDGET_BYTES
Informational (never fails):
  WEIGHT    bytes auto-loaded per AGENTS.md import chain, and the 10 heaviest skill files

Usage (from inside the repo):  python3 .claude/skills/audit-claude/scripts/claude-lint.py [--quiet]
Exit code: 0 = clean, 1 = at least one failure.
"""
import os
import re
import subprocess
import sys

ROOT = subprocess.run(["git", "rev-parse", "--show-toplevel"], capture_output=True, text=True).stdout.strip() or os.path.abspath(os.path.join(os.path.dirname(__file__), "../../../.."))
CLAUDE = os.path.join(ROOT, ".claude")
SKIP_DIRS = {"runs", "worktrees", "node_modules"}
PATH_PREFIXES = tuple(sorted({
    l.split("/", 1)[0] + "/"
    for l in subprocess.run(["git", "-C", ROOT, "ls-files", "--cached", "--others", "--exclude-standard"], capture_output=True, text=True).stdout.splitlines()
    if "/" in l
} | {".claude/", ".github/"}))
PLACEHOLDER = re.compile(r"[<>{}*$…|]|\.\.\.|\bX\b|/N\b|(?i:foo)")
NEGATED = re.compile(r"\*\*no\*\*|path_regex|does not exist|no longer|not yet|recreate|\bdeleted\b|was (removed|dropped)|is gone|\bno \S+ yet|\bno `[^`]+` exists|branch `[^`]+` contains", re.I)
# Edit per repo: lines mentioning sibling repos or other foreign paths (e.g. r"infra-terraform|\.\./core").
# Here: the WordPress reference plugin, whose paths do not exist in this checkout. The template's
# generic `plugin` token is dropped — in a Craft plugin it would skip every `src/Plugin.php` line.
SKIP_LINE_PATTERNS = r"translate-wordpress|WordPress plugin|\bWP\b"
FOREIGN = re.compile(r"\.\./[A-Za-z]" + ("|" + SKIP_LINE_PATTERNS if SKIP_LINE_PATTERNS else ""), re.I)
BACKTICK = re.compile(r"`([^`\s]+)`")
QUIET = "--quiet" in sys.argv
BUDGET_BYTES = 100_000

failures = []


def rel(p):
    return os.path.relpath(p, ROOT)


def fail(check, msg):
    failures.append(f"{check:<7} {msg}")


def read(p):
    real = os.path.realpath(p)
    if os.path.commonpath([os.path.realpath(ROOT), real]) != os.path.realpath(ROOT):
        return ""
    with open(real, encoding="utf-8", errors="replace") as f:
        return f.read()


def walk_md(base):
    for d, dirs, files in os.walk(base):
        dirs[:] = [x for x in dirs if x not in SKIP_DIRS]
        for f in files:
            if f.endswith(".md"):
                yield os.path.join(d, f)


def frontmatter(text):
    if not text.startswith("---\n"):
        return None
    end = text.find("\n---", 4)
    if end == -1:
        return None
    fields = {}
    for line in text[4:end].splitlines():
        m = re.match(r"^([A-Za-z_-]+):\s*(.*)$", line)
        if m:
            fields[m.group(1)] = m.group(2)
    return fields


agents_files = [p for p in [os.path.join(ROOT, "AGENTS.md")] if os.path.isfile(p)] + sorted(
    os.path.join(ROOT, d, "AGENTS.md") for d in os.listdir(ROOT) if os.path.isfile(os.path.join(ROOT, d, "AGENTS.md"))
)

# IMPORT + WEIGHT (auto-loaded chains)
weights = []
for a in agents_files:
    total = os.path.getsize(a)
    for line in read(a).splitlines():
        if line.startswith("@"):
            target = os.path.normpath(os.path.join(os.path.dirname(a), line[1:].strip()))
            if os.path.isfile(target):
                total += os.path.getsize(target)
            else:
                fail("IMPORT", f"{rel(a)}: {line.strip()} does not resolve")
    weights.append((rel(a), total))

# PATH (skipped: lines stating an absence, paths in another repo, gitignored build outputs)
scanned = list(walk_md(CLAUDE)) + agents_files
missing = []
for f in scanned:
    for n, line in enumerate(read(f).splitlines(), 1):
        if NEGATED.search(line) or FOREIGN.search(line):
            continue
        for tok in BACKTICK.findall(line):
            tok = re.sub(r"(:\d+([-,]\d+)*|#.*)$", "", tok).rstrip(".,;)")
            if not tok.startswith(PATH_PREFIXES) or PLACEHOLDER.search(tok):
                continue
            if not os.path.exists(os.path.join(ROOT, tok)):
                missing.append((f, n, tok))
if missing:
    ignored = set(subprocess.run(["git", "-C", ROOT, "check-ignore", "--no-index", "--stdin"],
                                 input="\n".join(t for _, _, t in missing), capture_output=True, text=True).stdout.split())
    for f, n, tok in missing:
        if tok not in ignored:
            fail("PATH", f"{rel(f)}:{n}: `{tok}` does not exist")

# SKILL
skills_dir = os.path.join(CLAUDE, "skills")
for s in sorted(os.listdir(skills_dir)) if os.path.isdir(skills_dir) else []:
    p = os.path.join(skills_dir, s, "SKILL.md")
    if not os.path.isfile(p):
        fail("SKILL", f".claude/skills/{s}: no SKILL.md")
        continue
    fm = frontmatter(read(p))
    if fm is None:
        fail("SKILL", f"{rel(p)}: missing frontmatter")
        continue
    if fm.get("name") != s:
        fail("SKILL", f"{rel(p)}: name '{fm.get('name')}' != directory '{s}'")
    desc = fm.get("description", "")
    if not desc:
        fail("SKILL", f"{rel(p)}: empty description")
    elif desc[0] not in "\"'" and " #" in desc:
        fail("SKILL", f"{rel(p)}: unquoted ' #' truncates the description")

# MEMORY
for p in walk_md(os.path.join(CLAUDE, "memory")):
    if os.path.basename(p) == "README.md":
        continue
    fm = frontmatter(read(p))
    if fm is None:
        fail("MEMORY", f"{rel(p)}: missing frontmatter")
    elif not fm.get("name") or not fm.get("description"):
        fail("MEMORY", f"{rel(p)}: frontmatter lacks name or description")

# ORPHAN
corpus = []
for d, dirs, files in os.walk(ROOT):
    dirs[:] = [x for x in dirs if x not in SKIP_DIRS and not (x.startswith(".") and x not in (".claude", ".github"))
               and x not in ("vendor", "var", ".next")]
    for f in files:
        if f.endswith((".md", ".yml", ".yaml", ".sh", ".json")):
            corpus.append(os.path.join(d, f))
texts = {p: read(p) for p in corpus}
for sub in ("prompts", "commands", "agents"):
    base = os.path.join(CLAUDE, sub)
    if not os.path.isdir(base):
        continue
    for f in sorted(os.listdir(base)):
        path = os.path.join(base, f)
        stem = os.path.splitext(f)[0]
        needles = (f".claude/{sub}/{f}", f"{sub}/{f}") if sub == "prompts" else (f".claude/{sub}/{f}", f"{sub}/{f}", stem)
        if not any(p != path and any(n in t for n in needles) for p, t in texts.items()):
            fail("ORPHAN", f".claude/{sub}/{f}: referenced nowhere")

root_weight = dict(weights).get("AGENTS.md", 0)
for a, b in weights:
    session = b if a == "AGENTS.md" else root_weight + b
    if session > BUDGET_BYTES:
        fail("BUDGET", f"{a}: {session} bytes auto-loaded per session (root + app) > {BUDGET_BYTES} — move a narrow standard to `## On-demand standards`")

for line in failures:
    print(line)

if not QUIET:
    print("\nWEIGHT  auto-loaded bytes per session (AGENTS.md + @imports):")
    for a, b in weights:
        print(f"        {b:>8}  {a}")
    heavy = []
    for d, _, files in os.walk(skills_dir):
        for f in files:
            p = os.path.join(d, f)
            heavy.append((os.path.getsize(p), rel(p)))
    print("WEIGHT  heaviest skill files (loaded on trigger / on read):")
    for b, p in sorted(heavy, reverse=True)[:10]:
        print(f"        {b:>8}  {p}")

print(f"\n{len(failures)} failure(s)")
sys.exit(1 if failures else 0)
