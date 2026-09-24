# =========
# Variables
# =========
BUILD_VENDOR_SRC := build/vendor-src
SCOPED_DIR := build/scoped-vendor
DEST_DIR := src/vendor/weglot

TOKEN ?= $(GH_PAT)

# Repos
WEGLOT_PHP_REPO := https://$(TOKEN)@github.com/weglot/weglot-php.git
WEGLOT_PARSER_PHP_REPO := https://$(TOKEN)@github.com/weglot/weglot-parser-php.git
WEGLOT_TRANSLATION_DEFINITIONS_REPO := https://$(TOKEN)@github.com/weglot/weglot-translation-definitions.git
SIMPLE_HTML_DOM_REPO := https://$(TOKEN)@github.com/weglot/simple_html_dom.git
# Transitive dependency of weglot-php and weglot-parser-php (public, no token needed)
CRAWLER_DETECT_REPO := https://github.com/JayBizzle/Crawler-Detect.git

# Si tu veux forcer une "version minimale", mets une valeur ici (ex: 1.9.2, v2.7.0, 0.8.4)
# et laisse vide pour tout accepter.
WEGLOT_PHP_MIN :=
WEGLOT_TD_MIN :=
SIMPLE_HTML_DOM_MIN :=

# Force une branche/tag précis pour weglot-php (ex: api-v2-update).
# Laisse vide pour utiliser le dernier tag.
WEGLOT_PHP_REF := 1.9.9

# Tag/branche précis pour weglot-parser-php (lib de parsing extraite de weglot-php).
# Laisse vide pour utiliser le dernier tag.
WEGLOT_PARSER_PHP_REF := 0.1.1

# =========
# Cibles
# =========
.PHONY: all checkout scoper vendor-sync clean clean-src clean-build

all: checkout scoper vendor-sync

# ---------------------------
# 1) Checkout (dernier tag)
# ---------------------------
checkout: $(BUILD_VENDOR_SRC)/weglot-php $(BUILD_VENDOR_SRC)/weglot-parser-php $(BUILD_VENDOR_SRC)/weglot-translation-definitions $(BUILD_VENDOR_SRC)/simple_html_dom $(BUILD_VENDOR_SRC)/crawler-detect

$(BUILD_VENDOR_SRC)/weglot-php:
	mkdir -p $(BUILD_VENDOR_SRC)
ifneq ($(WEGLOT_PHP_REF),)
	@echo "➡️  Clonage weglot-php (ref: $(WEGLOT_PHP_REF))..."
	git clone --depth 1 --branch $(WEGLOT_PHP_REF) $(WEGLOT_PHP_REPO) $(BUILD_VENDOR_SRC)/weglot-php
else
	@echo "➡️  Clonage weglot-php (dernier tag)..."
	git clone --depth 1 $(WEGLOT_PHP_REPO) $(BUILD_VENDOR_SRC)/weglot-php
	cd $(BUILD_VENDOR_SRC)/weglot-php && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Dernier tag trouvé: $$LATEST"; \
	if [ -n "$(WEGLOT_PHP_MIN)" ]; then \
	  if [ "$$(printf '%s\n$(WEGLOT_PHP_MIN)\n' $$LATEST | sort -V | tail -n1)" = "$$LATEST" ]; then \
	    echo "   - OK (>= $(WEGLOT_PHP_MIN))"; \
	  else \
	    echo "   - ⚠️  Aucun tag >= $(WEGLOT_PHP_MIN) trouvé, fallback sur $(WEGLOT_PHP_MIN)"; \
	    LATEST="$(WEGLOT_PHP_MIN)"; \
	  fi; \
	fi; \
	git fetch --depth 1 origin $$LATEST && git checkout $$LATEST
endif

$(BUILD_VENDOR_SRC)/weglot-parser-php:
	mkdir -p $(BUILD_VENDOR_SRC)
ifneq ($(WEGLOT_PARSER_PHP_REF),)
	@echo "➡️  Clonage weglot-parser-php (ref: $(WEGLOT_PARSER_PHP_REF))..."
	git clone --depth 1 --branch $(WEGLOT_PARSER_PHP_REF) $(WEGLOT_PARSER_PHP_REPO) $(BUILD_VENDOR_SRC)/weglot-parser-php
else
	@echo "➡️  Clonage weglot-parser-php (dernier tag)..."
	git clone --depth 1 $(WEGLOT_PARSER_PHP_REPO) $(BUILD_VENDOR_SRC)/weglot-parser-php
	cd $(BUILD_VENDOR_SRC)/weglot-parser-php && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Dernier tag trouvé: $$LATEST"; \
	git fetch --depth 1 origin $$LATEST && git checkout $$LATEST
endif

$(BUILD_VENDOR_SRC)/weglot-translation-definitions:
	@echo "➡️  Clonage weglot-translation-definitions (dernier tag)..."
	mkdir -p $(BUILD_VENDOR_SRC)
	git clone --depth 1 $(WEGLOT_TRANSLATION_DEFINITIONS_REPO) $(BUILD_VENDOR_SRC)/weglot-translation-definitions
	cd $(BUILD_VENDOR_SRC)/weglot-translation-definitions && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Dernier tag trouvé: $$LATEST"; \
	if [ -n "$(WEGLOT_TD_MIN)" ]; then \
	  if [ "$$(printf '%s\n$(WEGLOT_TD_MIN)\n' $$LATEST | sort -V | tail -n1)" = "$$LATEST" ]; then \
	    echo "   - OK (>= $(WEGLOT_TD_MIN))"; \
	  else \
	    echo "   - ⚠️  Aucun tag >= $(WEGLOT_TD_MIN) trouvé, fallback sur $(WEGLOT_TD_MIN)"; \
	    LATEST="$(WEGLOT_TD_MIN)"; \
	  fi; \
	fi; \
	git fetch --depth 1 origin $$LATEST && git checkout $$LATEST

$(BUILD_VENDOR_SRC)/simple_html_dom:
	@echo "➡️  Clonage simple_html_dom (dernier tag)..."
	mkdir -p $(BUILD_VENDOR_SRC)
	git clone --depth 1 $(SIMPLE_HTML_DOM_REPO) $(BUILD_VENDOR_SRC)/simple_html_dom
	cd $(BUILD_VENDOR_SRC)/simple_html_dom && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Dernier tag trouvé: $$LATEST"; \
	if [ -n "$(SIMPLE_HTML_DOM_MIN)" ]; then \
	  if [ "$$(printf '%s\n$(SIMPLE_HTML_DOM_MIN)\n' $$LATEST | sort -V | tail -n1)" = "$$LATEST" ]; then \
	    echo "   - OK (>= $(SIMPLE_HTML_DOM_MIN))"; \
	  else \
	    echo "   - ⚠️  Aucun tag >= $(SIMPLE_HTML_DOM_MIN) trouvé, fallback sur $(SIMPLE_HTML_DOM_MIN)"; \
	    LATEST="$(SIMPLE_HTML_DOM_MIN)"; \
	  fi; \
	fi; \
	git fetch --depth 1 origin $$LATEST && git checkout $$LATEST

$(BUILD_VENDOR_SRC)/crawler-detect:
	@echo "➡️  Clonage crawler-detect (dernier tag)..."
	mkdir -p $(BUILD_VENDOR_SRC)
	git clone --depth 1 $(CRAWLER_DETECT_REPO) $(BUILD_VENDOR_SRC)/crawler-detect
	cd $(BUILD_VENDOR_SRC)/crawler-detect && \
	LATEST=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	echo "   - Dernier tag trouvé: $$LATEST"; \
	git fetch --depth 1 origin $$LATEST && git checkout $$LATEST

# ---------------------------
# 2) Lancer php-scoper
# ---------------------------
scoper:
	@echo "🧪 Lancement php-scoper via Composer…"
	composer run php-scoper

# ---------------------------
# 3) (Re)créer et copier vers src/vendor/weglot
# ---------------------------
vendor-sync:
	@echo "🗂️  Sync des fichiers vers $(DEST_DIR)"
	test -d $(SCOPED_DIR) || { echo "❌ $(SCOPED_DIR) introuvable. As-tu bien exécuté 'composer run php-scoper' ?"; exit 1; }
	rm -rf $(DEST_DIR)
	mkdir -p $(DEST_DIR)
	# rsync si dispo (plus sûr, supprime les fichiers obsolètes), sinon fallback cp -a
	{ command -v rsync >/dev/null 2>&1 && rsync -a --delete "$(SCOPED_DIR)/" "$(DEST_DIR)/"; } || cp -a "$(SCOPED_DIR)/." "$(DEST_DIR)/"
	@echo "✅ Copie terminée vers $(DEST_DIR)"

# Utilitaires
clean-src:
	rm -rf $(DEST_DIR)

clean-build:
	rm -rf $(BUILD_VENDOR_SRC) $(SCOPED_DIR)

clean: clean-src clean-build
	@echo "🧹 Clean terminé."
