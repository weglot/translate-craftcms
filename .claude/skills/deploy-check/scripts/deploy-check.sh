#!/usr/bin/env bash
# Runs every quality gate of the plugin locally: the CI steps of
# .github/workflows/code-quality.yml, plus PHPStan and tests/services/, which CI skips.
# Usage: bash .claude/skills/deploy-check/scripts/deploy-check.sh [--ci-only]
#   --ci-only   run exactly what CI runs (no PHPStan, phpunit tests/unit/ only)
set -u

cd "$(git rev-parse --show-toplevel)" || exit 1

CI_ONLY=0
for arg in "$@"; do
	case "$arg" in
		--ci-only) CI_ONLY=1 ;;
		*) echo "Unknown argument: $arg" >&2; exit 2 ;;
	esac
done

FAILED=()
WARNED=()

run() {
	local label="$1"
	shift
	echo "==> $label"
	if "$@"; then
		echo "    PASS $label"
	else
		echo "    FAIL $label"
		FAILED+=("$label")
	fi
}

run "composer validate" composer validate --strict --no-plugins
run "composer audit" composer audit
run "php-cs-fixer" vendor/bin/php-cs-fixer fix --dry-run --diff
run "rector" vendor/bin/rector process --dry-run --no-progress-bar
if [ "$CI_ONLY" -eq 0 ]; then
	run "phpstan" vendor/bin/phpstan --memory-limit=1G --no-progress
	run "phpunit tests/" vendor/bin/phpunit tests --no-coverage
else
	run "phpunit tests/unit/" vendor/bin/phpunit tests/unit/ --no-coverage
fi

LOCAL_PHP="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
if [ "$LOCAL_PHP" != "8.2" ]; then
	WARNED+=("local PHP is $LOCAL_PHP, CI runs 8.2: 8.3+ functions pass here and fail for 8.2 users")
fi

BASE="$(git merge-base HEAD master 2>/dev/null || true)"
if [ -n "$BASE" ]; then
	ADDED="$(git diff "$BASE" -- 'src/*.php' 'tests/*.php' ':!src/vendor' | grep -E '^\+' | grep -vE '^\+\+\+' || true)"
	for pattern in '\b(var_dump|print_r|dd|dump)\(' '\bempty\(' '\$_(SERVER|GET|POST|COOKIE)\b' 'new \\?(GuzzleHttp\\)?Client\(' '\b(file_get_contents|curl_[a-z_]+)\('; do
		HITS="$(printf '%s\n' "$ADDED" | grep -nE "$pattern" || true)"
		if [ -n "$HITS" ]; then
			WARNED+=("added in the branch diff, banned by .claude/memory/standards/craft-php-standards.md ($pattern):"$'\n'"$HITS")
		fi
	done
	if git diff --name-only "$BASE" -- src/vendor/weglot | grep -q .; then
		WARNED+=("src/vendor/weglot changed: run composer dump-autoload in the consuming Craft project (/vendor-update)")
	fi
	if git diff --name-only "$BASE" -- src/resources-src | grep -q . && ! git diff --name-only "$BASE" -- src/resources | grep -q .; then
		WARNED+=("src/resources-src changed but no compiled file in src/resources did (/weglot-craft-build-and-qa § Assets)")
	fi
fi

echo
for warning in "${WARNED[@]+"${WARNED[@]}"}"; do
	echo "WARN $warning"
done
if [ "${#FAILED[@]}" -gt 0 ]; then
	echo "FAILED: ${FAILED[*]}"
	exit 1
fi
echo "All gates passed."
