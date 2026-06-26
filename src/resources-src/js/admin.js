// JavaScript
(function() {
    const apiKeyInput = document.querySelector('[data-weglot-api-key]');
    const statusDiv   = document.querySelector('[data-weglot-api-status]');
    const form        = apiKeyInput ? apiKeyInput.closest('form') : null;

    let isValid = apiKeyInput && apiKeyInput.value.trim() !== '' ? null : false;
    let successTimeout = null;
    function setStatus(ok, message) {
        if (!statusDiv) return;

        if (successTimeout) {
            clearTimeout(successTimeout);
            successTimeout = null;
        }

        const color = ok ? 'green' : '#B91C1C';
        statusDiv.innerHTML = '<span style="color:' + color + ';">' + (message || '') + '</span>';
        statusDiv.classList.add('show');
    }
    function clearStatus() {
        if (statusDiv) {
            statusDiv.classList.remove('show');
            setTimeout(function() {
                statusDiv.innerHTML = '';
            }, 300); // Attendre la fin de l'animation
        }
    }
    function updateSaveDisabled() {
        if (!form) return;
        const saveBtn = form.querySelector('button[type="submit"], .btn.submit');
        if (!saveBtn) return;
        if (isValid === false) { saveBtn.classList.add('disabled'); saveBtn.disabled = true; }
        else { saveBtn.classList.remove('disabled'); saveBtn.disabled = false; }
    }
    function checkKey(value) {
        if (!statusDiv) return Promise.resolve(false);
        if (!value || !value.trim()) {
            setStatus(false, Craft.t('weglot', 'Please enter an API key.'));
            return Promise.resolve(false);
        }
        statusDiv.textContent = Craft.t('weglot', 'Checking...');
        return new Promise(function(resolve) {
            Craft.postActionRequest(
                'weglot/api/validate-api-key',
                { apiKey: value },
                function(response, textStatus) {
                    const apiKeyField = document.getElementById('settings-apiKey');

                    if (textStatus !== 'success') {
                        setStatus(false, Craft.t('weglot', 'Communication error with the server.'));
                        if (apiKeyField) {
                            apiKeyField.classList.remove('is-valid');
                            apiKeyField.classList.add('is-invalid');
                        }
                        return resolve(false);
                    }
                    const ok = !(response.error || (response.succeeded && parseInt(response.succeeded) !== 1));
                    if (ok) {
                        var product = response.product != null ? String(response.product) : '';
                        setV1FieldsVisible(product.startsWith('1'));
                        setStatus(true, Craft.t('weglot', 'Success! The API key is valid.'));
                        if (apiKeyField) {
                            apiKeyField.classList.add('is-valid');
                            apiKeyField.classList.remove('is-invalid');
                        }
                        successTimeout = setTimeout(function() {
                            clearStatus();
                            const apiKeyField = document.getElementById('settings-apiKey');
                            if (apiKeyField) {
                                apiKeyField.classList.remove('is-valid');
                            }
                        }, 3000);
                    }
                    else {
                        setStatus(false, response.message || response.error || Craft.t('weglot', 'The API key is invalid.'));
                        if (apiKeyField) {
                            apiKeyField.classList.remove('is-valid');
                            apiKeyField.classList.add('is-invalid');
                        }
                    }
                    resolve(ok);
                }
            );
        });
    }
    function setV1FieldsVisible(show) {
        var container = document.querySelector('[data-weglot-v1-fields]');
        if (!container) return;
        container.style.display = show ? '' : 'none';
    }

    if (apiKeyInput) {
        apiKeyInput.addEventListener('input', function() {
            isValid = null; clearStatus(); updateSaveDisabled();
            setV1FieldsVisible(false);
        });
    }
    document.addEventListener('focusout', function(e) {
        if (e.target === apiKeyInput) {
            checkKey(apiKeyInput.value).then(function(ok) { isValid = ok; updateSaveDisabled(); });
        }
    });
    if (form) {
        form.addEventListener('submit', function(e) {
            if (isValid === false) {
                e.preventDefault();
                (Craft.cp && Craft.cp.displayError)
                    ? Craft.cp.displayError(Craft.t('weglot', 'The API key is invalid.'))
                    : alert(Craft.t('weglot', 'The API key is invalid.'));
                return;
            }
            if (isValid === null && apiKeyInput) {
                e.preventDefault();
                checkKey(apiKeyInput.value).then(function(ok) {
                    isValid = ok; updateSaveDisabled();
                    if (ok) { form.submit(); }
                    else {
                        (Craft.cp && Craft.cp.displayError)
                            ? Craft.cp.displayError(Craft.t('weglot', 'The API key is invalid.'))
                            : alert(Craft.t('weglot', 'The API key is invalid.'));
                    }
                });
            }
        });
    }

    function readLanguagesJson() {
        var el = document.getElementById('weglot-list-languages');
        if (!el) return { languages: [], limit: 0 };
        try {
            var data = JSON.parse(el.textContent || '{}') || {};
            if (!Array.isArray(data.languages)) data.languages = [];
            if (typeof data.limit !== 'number') data.limit = 0;
            return data;
        } catch (e) {
            return { languages: [], limit: 0 };
        }
    }

    function initDestinationSelectize() {
        var $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.selectize) return;

        var $input = $('input.weglot-select-destination');
        if (!$input.length) return;

        var data = readLanguagesJson();
        var catalog = data.languages || [];
        var limit = data.limit > 0 ? data.limit : 0;

        // Évite double init
        if ($input[0].selectize) {
            $input[0].selectize.destroy();
        }

        var options = catalog.map(function (l) {
            return {
                internal_code: l.internal_code,
                english: l.english,
                local: l.local,
                external_code: l.external_code
            };
        });

        var s = $input.selectize({
            delimiter: '|',
            persist: false,
            create: false,
            openOnFocus: true,
            closeAfterSelect: false,
            hideSelected: true,
            selectOnTab: true,
            maxItems: limit > 0 ? limit : null, // null = unlimited
            maxOptions: 1000,
            diacritics: true,
            valueField: 'internal_code',
            labelField: 'local',
            searchField: ['internal_code', 'english', 'local'],
            sortField: [{ field: 'english', direction: 'asc' }],
            options: options,
            plugins: ['remove_button'],
            render: {
                option: function (item, escape) {
                    var local = escape(item.local || '');
                    var external = escape(item.external_code || '');
                    return '<div class="weglot__choice__language">' +
                        '<span class="weglot__choice__language--local">' + local + ' [' + external + ']</span>' +
                        '</div>';
                },
                item: function (item, escape) {
                    var label = item.local || item.internal_code || '';
                    return '<div>' + escape(label) + '</div>';
                }
            },
            onInitialize: function () {
                var self = this;
                self.on('focus', function () {
                    self.search('');
                    self.refreshOptions(true);
                    self.open();
                });
            }
        })[0].selectize;

    }

    function initFirstSettingsPopup() {
        var popup = document.getElementById('settings-weglot-box-first-settings');
        if (!popup) {
            return;
        }
        function closePopup() {
            popup.style.opacity = '0';
            setTimeout(function() {
                popup.remove();
            }, 200);
        }

        var closeBtn = popup.querySelector('.weglot-btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closePopup();
            });
        }

        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                closePopup();
            }
        });

        var escapeHandler = function(e) {
            if (e.key === 'Escape') {
                closePopup();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }

    function initResetModal() {
        var btn = document.querySelector('[data-weglot-reset-btn]');
        var modal = document.querySelector('[data-weglot-reset-modal]');
        if (!btn || !modal) { return; }

        var confirmBtn = modal.querySelector('[data-weglot-reset-confirm]');
        var cancelBtn = modal.querySelector('[data-weglot-reset-cancel]');
        var errorEl = modal.querySelector('[data-weglot-reset-error]');

        function openModal() {
            modal.classList.remove('weglot-reset-modal--hidden');
            if (errorEl) { errorEl.classList.add('weglot-reset-modal--hidden'); }
        }
        function closeModal() {
            modal.classList.add('weglot-reset-modal--hidden');
        }

        btn.addEventListener('click', openModal);
        if (cancelBtn) { cancelBtn.addEventListener('click', closeModal); }
        modal.addEventListener('click', function(e) {
            if (e.target === modal) { closeModal(); }
        });

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                confirmBtn.disabled = true;
                confirmBtn.textContent = Craft.t('weglot', 'Resetting…');

                Craft.postActionRequest('weglot/api/reset-settings', {}, function(response, textStatus) {
                    if (textStatus === 'success' && response.success) {
                        window.location.reload();
                    } else {
                        if (errorEl) {
                            errorEl.textContent = Craft.t('weglot', 'An error occurred. Please try again.');
                            errorEl.classList.remove('weglot-reset-modal--hidden');
                        }
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = Craft.t('weglot', 'Yes, reset');
                    }
                });
            });
        }
    }

    function init() {
        initDestinationSelectize();

        setTimeout(function() {
            initFirstSettingsPopup();
            initResetModal();
        }, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    if (window.Garnish && Garnish.$doc) {
        Garnish.$doc.on('pjax:end', function() {
            setTimeout(function() {
                init();
            }, 100);
        });
    }
})();
