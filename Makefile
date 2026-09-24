# =========
# Variables
# =========
BUILD_VENDOR_SRC := build/vendor-src
SCOPED_DIR := build/scoped-vendor
DEST_DIR := src/vendor/weglot

# The private repositories are cloned with the token from GH_PAT, fed through a one-shot
# credential helper: $$GH_PAT is expanded by the helper's shell, so make echoes the literal
# and the token never reaches the terminal, a transcript or build/vendor-src/*/.git/config.
GIT_AUTH = git -c credential.helper= -c credential.helper='!f() { echo username=x-access-token; echo "password=$$GH_PAT"; }; f'

# Repos
WEGLOT_PHP_REPO := https://github.com/weglot/weglot-php.git
WEGLOT_PARSER_PHP_REPO := https://github.com/weglot/weglot-parser-php.git
WEGLOT_TRANSLATION_DEFINITIONS_REPO := https://github.com/weglot/weglot-translation-definitions.git
SIMPLE_HTML_DOM_REPO := https://github.com/weglot/simple_html_dom.git
# Transitive dependency of weglot-php and weglot-parser-php (public, no token needed)
CRAWLER_DETECT_REPO := https://github.com/JayBizzle/Crawler-Detect.git

# To enforce a minimum version, set a value here (e.g. 1.9.2, v2.7.0, 0.8.4);
# leave empty to accept any.
WEGLOT_PHP_MIN :=
WEGLOT_TD_MIN :=
SIMPLE_HTML_DOM_MIN :=

# Pin an exact branch/tag for weglot-php (e.g. api-v2-update).
# Leave empty to use the latest tag.
WEGLOT_PHP_REF := 1.9.9

# Exact tag/branch for weglot-parser-php (parsing library extracted from weglot-php).
# Leave empty to use the latest tag.
WEGLOT_PARSER_PHP_REF := 0.1.1

# =========
# Targets
# =========
.PHONY: all checkout scoper vendor-sync clean clean-src clean-build

all: checkout scoper vendor-sync

# ---------------------------
# 1) Checkout (latest tag)
# ---------------------------
checkout: $(BUILD_VENDOR_SRC)/weglot-php $(BUILD_VENDOR_SRC)/weglot-parser-php $(BUILD_VENDOR_SRC)/weglot-translation-definitions $(BUILD_VENDOR_SRC)/simple_html_dom $(BUILD_VENDOR_SRC)/crawler-detect

$(BUILD_VENDOR_SRC)/weglot-php:
	mkdir -p $(BUILD_VENDOR_SRC)
ifneq ($(WEGLOT_PHP_REF),)
	@echo "➡️  Cloning weglot-php (ref: $(WEGLOT_PHP_REF))..."
	$(GIT_AUTH) clone --depth 1 --branch $(WEGLOT_PHP_REF) $(WEGLOT_PHP_REPO) $(BUILD_VENDOR_SRC)/weglot-php
else
	@echo "➡️  Cloning weglot-php (latest tag)..."
	$(GIT_AUTH) clone --depth 1 $(WEGLOT_PHP_REPO) $(BUILD_VENDOR_SRC)/weglot-php
	cd $(BUILD_VENDOR_SRC)/weglot-php && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Latest tag found: $$LATEST"; \
	if [ -n "$(WEGLOT_PHP_MIN)" ]; then \
	  if [ "$$(printf '%s\n$(WEGLOT_PHP_MIN)\n' $$LATEST | sort -V | tail -n1)" = "$$LATEST" ]; then \
	    echo "   - OK (>= $(WEGLOT_PHP_MIN))"; \
	  else \
	    echo "   - ⚠️  No tag >= $(WEGLOT_PHP_MIN) found, falling back to $(WEGLOT_PHP_MIN)"; \
	    LATEST="$(WEGLOT_PHP_MIN)"; \
	  fi; \
	fi; \
	$(GIT_AUTH) fetch --depth 1 origin $$LATEST && git checkout $$LATEST
endif

$(BUILD_VENDOR_SRC)/weglot-parser-php:
	mkdir -p $(BUILD_VENDOR_SRC)
ifneq ($(WEGLOT_PARSER_PHP_REF),)
	@echo "➡️  Cloning weglot-parser-php (ref: $(WEGLOT_PARSER_PHP_REF))..."
	$(GIT_AUTH) clone --depth 1 --branch $(WEGLOT_PARSER_PHP_REF) $(WEGLOT_PARSER_PHP_REPO) $(BUILD_VENDOR_SRC)/weglot-parser-php
else
	@echo "➡️  Cloning weglot-parser-php (latest tag)..."
	$(GIT_AUTH) clone --depth 1 $(WEGLOT_PARSER_PHP_REPO) $(BUILD_VENDOR_SRC)/weglot-parser-php
	cd $(BUILD_VENDOR_SRC)/weglot-parser-php && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Latest tag found: $$LATEST"; \
	$(GIT_AUTH) fetch --depth 1 origin $$LATEST && git checkout $$LATEST
endif

$(BUILD_VENDOR_SRC)/weglot-translation-definitions:
	@echo "➡️  Cloning weglot-translation-definitions (latest tag)..."
	mkdir -p $(BUILD_VENDOR_SRC)
	$(GIT_AUTH) clone --depth 1 $(WEGLOT_TRANSLATION_DEFINITIONS_REPO) $(BUILD_VENDOR_SRC)/weglot-translation-definitions
	cd $(BUILD_VENDOR_SRC)/weglot-translation-definitions && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Latest tag found: $$LATEST"; \
	if [ -n "$(WEGLOT_TD_MIN)" ]; then \
	  if [ "$$(printf '%s\n$(WEGLOT_TD_MIN)\n' $$LATEST | sort -V | tail -n1)" = "$$LATEST" ]; then \
	    echo "   - OK (>= $(WEGLOT_TD_MIN))"; \
	  else \
	    echo "   - ⚠️  No tag >= $(WEGLOT_TD_MIN) found, falling back to $(WEGLOT_TD_MIN)"; \
	    LATEST="$(WEGLOT_TD_MIN)"; \
	  fi; \
	fi; \
	$(GIT_AUTH) fetch --depth 1 origin $$LATEST && git checkout $$LATEST

$(BUILD_VENDOR_SRC)/simple_html_dom:
	@echo "➡️  Cloning simple_html_dom (latest tag)..."
	mkdir -p $(BUILD_VENDOR_SRC)
	$(GIT_AUTH) clone --depth 1 $(SIMPLE_HTML_DOM_REPO) $(BUILD_VENDOR_SRC)/simple_html_dom
	cd $(BUILD_VENDOR_SRC)/simple_html_dom && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Latest tag found: $$LATEST"; \
	if [ -n "$(SIMPLE_HTML_DOM_MIN)" ]; then \
	  if [ "$$(printf '%s\n$(SIMPLE_HTML_DOM_MIN)\n' $$LATEST | sort -V | tail -n1)" = "$$LATEST" ]; then \
	    echo "   - OK (>= $(SIMPLE_HTML_DOM_MIN))"; \
	  else \
	    echo "   - ⚠️  No tag >= $(SIMPLE_HTML_DOM_MIN) found, falling back to $(SIMPLE_HTML_DOM_MIN)"; \
	    LATEST="$(SIMPLE_HTML_DOM_MIN)"; \
	  fi; \
	fi; \
	$(GIT_AUTH) fetch --depth 1 origin $$LATEST && git checkout $$LATEST

$(BUILD_VENDOR_SRC)/crawler-detect:
	@echo "➡️  Cloning crawler-detect (latest tag)..."
	mkdir -p $(BUILD_VENDOR_SRC)
	git clone --depth 1 $(CRAWLER_DETECT_REPO) $(BUILD_VENDOR_SRC)/crawler-detect
	cd $(BUILD_VENDOR_SRC)/crawler-detect && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Latest tag found: $$LATEST"; \
	git fetch --depth 1 origin $$LATEST && git checkout $$LATEST

# ---------------------------
# 2) Run php-scoper
# ---------------------------
scoper:
	@echo "🧪 Running php-scoper through Composer…"
	composer run php-scoper

# ---------------------------
# 3) (Re)create and copy into src/vendor/weglot
# ---------------------------
vendor-sync:
	@echo "🗂️  Syncing files into $(DEST_DIR)"
	test -d $(SCOPED_DIR) || { echo "❌ $(SCOPED_DIR) not found. Did you run 'composer run php-scoper'?"; exit 1; }
	rm -rf $(DEST_DIR)
	mkdir -p $(DEST_DIR)
	# rsync when available (safer, deletes stale files), otherwise fall back to cp -a
	{ command -v rsync >/dev/null 2>&1 && rsync -a --delete "$(SCOPED_DIR)/" "$(DEST_DIR)/"; } || cp -a "$(SCOPED_DIR)/." "$(DEST_DIR)/"
	@echo "✅ Copy finished into $(DEST_DIR)"

# Utilities
clean-src:
	rm -rf $(DEST_DIR)

clean-build:
	rm -rf $(BUILD_VENDOR_SRC) $(SCOPED_DIR)

clean: clean-src clean-build
	@echo "🧹 Clean finished."
