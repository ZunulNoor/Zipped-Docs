(function () {
    'use strict';

    var IE = window.ZIPPED_DOCS_IE || {};
    var THEME = IE.themeColor || '#2563EB';
    var RED = '#DC2626';
    var GREEN = '#16A34A';
    var AMBER = '#F59E0B';

    var ImportExportModal = {
        _active: false,
        _resolve: null,
        _overlay: null,
        _modal: null,
        _overlayCss: 'position:fixed;inset:0;z-index:999999;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:opacity 0.2s ease,visibility 0.2s ease;-webkit-backdrop-filter:blur(2px);backdrop-filter:blur(2px);',
        _baseCss: 'background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.15),0 8px 20px rgba(0,0,0,0.08);max-height:90vh;overflow-y:auto;transform:scale(0.92) translateY(8px);transition:transform 0.25s cubic-bezier(0.34,1.56,0.64,1);padding:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;',

        open: function (opts) {
            var self = this;
            return new Promise(function (resolve) {
                self._resolve = resolve;
                self._closeHandlers = [];

                var overlay = document.createElement('div');
                overlay.style.cssText = self._overlayCss;
                var modal = document.createElement('div');
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                modal.style.cssText = self._baseCss + (opts.width ? 'max-width:' + opts.width + ';' : 'max-width:640px;') + 'width:calc(100% - 32px);';

                if (opts.html) { modal.innerHTML = opts.html; }
                if (opts.content) { modal.appendChild(opts.content); }

                overlay.appendChild(modal);
                document.body.appendChild(overlay);

                self._overlay = overlay;
                self._modal = modal;
                self._active = true;

                requestAnimationFrame(function () {
                    overlay.style.opacity = '1';
                    overlay.style.visibility = 'visible';
                    modal.style.transform = 'scale(1) translateY(0)';
                });

                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay && opts.closable !== false) { self.close(null); }
                });

                function escHandler(e) {
                    if (e.key === 'Escape' && self._active && opts.closable !== false) { e.preventDefault(); self.close(null); }
                }
                document.addEventListener('keydown', escHandler);
                self._closeHandlers.push(function () { document.removeEventListener('keydown', escHandler); });

                if (opts.onOpen) { opts.onOpen(modal, overlay); }
            });
        },

        close: function (value) {
            if (!this._active) return;
            this._active = false;
            var self = this;
            this._overlay.style.opacity = '0';
            this._overlay.style.visibility = 'hidden';
            this._modal.style.transform = 'scale(0.92) translateY(8px)';
            setTimeout(function () {
                if (self._resolve) { self._resolve(value); self._resolve = null; }
                self._closeHandlers.forEach(function (fn) { fn(); });
                self._closeHandlers = [];
                if (self._overlay && self._overlay.parentNode) { self._overlay.parentNode.removeChild(self._overlay); }
                self._overlay = null;
                self._modal = null;
            }, 200);
        },

        setContent: function (html) { if (this._modal) { this._modal.innerHTML = html; } },
        getModal: function () { return this._modal; },
        getOverlay: function () { return this._overlay; }
    };

    function openImportModal() {
        var supportedHtml = '';
        var labels = ['Zipped Docs JSON', 'WordPress Page JSON', 'WordPress Post JSON', 'Gutenberg Block JSON', 'Post/Page Import Export JSON'];
        labels.forEach(function (l) {
            supportedHtml += '<div style="display:flex;align-items:center;gap:8px;padding:4px 0;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' + GREEN + '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span>' + l + '</span></div>';
        });

        var html =
            '<div style="padding:24px 28px 0;">' +
            '<div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">' +
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="' + THEME + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>' +
            '<h2 style="margin:0;font-size:18px;font-weight:700;color:#1d2327;">' + IE.i18n.importTitle + '</h2>' +
            '</div>' +
            '<p style="margin:4px 0 12px;font-size:13px;color:#646970;">' + IE.i18n.supportedFormats + '</p>' +
            '<div style="background:#f6f7f7;border-radius:10px;padding:8px 16px;margin-bottom:16px;font-size:13px;">' + supportedHtml + '</div>' +
            '<p style="font-size:12px;color:#a7aaad;margin:0 0 16px;">' + IE.i18n.maxUploadSizeMsg + '</p>' +
            '</div>' +
            '<div style="padding:0 28px 24px;">' +
            '<div id="zipped-docs-dropzone" style="border:2px dashed #dcdcde;border-radius:12px;padding:40px 20px;text-align:center;cursor:pointer;transition:border-color 0.2s,background 0.2s;background:#fafafa;">' +
            '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>' +
            '<p style="margin:12px 0 4px;font-size:15px;color:#50575e;font-weight:500;">' + IE.i18n.dragDrop + '</p>' +
            '<p style="margin:0 0 16px;font-size:13px;color:#a7aaad;">' + IE.i18n.fileInfo + ': .json</p>' +
            '<button type="button" id="zipped-docs-browse-btn" class="button button-primary" style="background:' + THEME + ';border:none;padding:8px 24px;border-radius:8px;font-size:14px;height:auto;line-height:1.4;">' + IE.i18n.browseFile + '</button>' +
            '<input type="file" id="zipped-docs-file-input" accept=".json" style="display:none;">' +
            '</div>' +
            '<div id="zipped-docs-import-status" style="display:none;margin-top:16px;text-align:center;"></div>' +
            '</div>' +
            '<div style="display:flex;justify-content:flex-end;gap:8px;padding:16px 28px;border-top:1px solid #f0f0f1;">' +
            '<button type="button" class="button zipped-docs-ie-close" style="min-height:36px;padding:6px 16px;border-radius:8px;">' + IE.i18n.close + '</button>' +
            '</div>';

        ImportExportModal.open({ html: html, width: '580px' }).then(function () {});

        var modal = ImportExportModal.getModal();
        modal.querySelector('.zipped-docs-ie-close').addEventListener('click', function () { ImportExportModal.close(null); });

        var dropzone = modal.querySelector('#zipped-docs-dropzone');
        var fileInput = modal.querySelector('#zipped-docs-file-input');

        modal.querySelector('#zipped-docs-browse-btn').addEventListener('click', function () { fileInput.click(); });

        dropzone.addEventListener('dragover', function (e) {
            e.preventDefault(); e.stopPropagation();
            dropzone.style.borderColor = THEME; dropzone.style.background = '#eff6ff';
        });
        dropzone.addEventListener('dragleave', function (e) {
            e.preventDefault(); e.stopPropagation();
            dropzone.style.borderColor = '#dcdcde'; dropzone.style.background = '#fafafa';
        });
        dropzone.addEventListener('drop', function (e) {
            e.preventDefault(); e.stopPropagation();
            dropzone.style.borderColor = '#dcdcde'; dropzone.style.background = '#fafafa';
            var files = e.dataTransfer.files;
            if (files.length > 0) { previewImportFile(files[0], modal); }
        });
        fileInput.addEventListener('change', function () {
            if (fileInput.files.length > 0) { previewImportFile(fileInput.files[0], modal); }
        });
    }

    function previewImportFile(file, modal) {
        if (!file.name.toLowerCase().endsWith('.json')) {
            showImportError(modal, IE.i18n.invalidFile);
            return;
        }

        var status = modal.querySelector('#zipped-docs-import-status');
        status.style.display = 'block';
        status.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;gap:8px;color:' + THEME + ';font-weight:500;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="zipped-docs-spinner" style="animation:zipped-docs-spin 1s linear infinite;"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>' + IE.i18n.processing + '</div>';

        var formData = new FormData();
        formData.append('action', 'zipped_docs_import_preview');
        formData.append('_wpnonce', IE.importNonce);
        formData.append('zipped_docs_import_file', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', IE.ajaxUrl, true);
        xhr.onload = function () {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (!resp.success) {
                    showImportError(modal, resp.data && resp.data.message ? resp.data.message : IE.i18n.uploadError);
                    return;
                }
                showImportPreview(modal, resp.data, file);
            } catch (e) {
                showImportError(modal, IE.i18n.uploadError);
            }
        };
        xhr.onerror = function () { showImportError(modal, IE.i18n.uploadError); };
        xhr.send(formData);
    }

    function showImportPreview(modal, preview, file) {
        if (!preview.can_import) {
            var errMsg = preview.error_message || IE.i18n.unknownFormat;
            showImportError(modal, errMsg);
            return;
        }

        var detailsHtml = '';

        detailsHtml += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f1;"><span style="color:#646970;font-size:13px;">' + IE.i18n.detectedFormat + '</span><strong style="font-size:13px;">' + escapeHtml(preview.format_label) + '</strong></div>';
        detailsHtml += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f1;"><span style="color:#646970;font-size:13px;">' + IE.i18n.documents + '</span><strong style="font-size:13px;color:' + THEME + ';">' + preview.document_count + '</strong></div>';

        if (preview.total_categories > 0) {
            detailsHtml += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f1;"><span style="color:#646970;font-size:13px;">' + IE.i18n.categoriesFound + '</span><strong style="font-size:13px;">' + preview.total_categories + '</strong></div>';
        }
        if (preview.total_images > 0) {
            detailsHtml += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f1;"><span style="color:#646970;font-size:13px;">' + IE.i18n.imagesFound + '</span><strong style="font-size:13px;">' + preview.total_images + '</strong></div>';
        }

        detailsHtml += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f1;"><span style="color:#646970;font-size:13px;">' + IE.i18n.gutenbergSupported + '</span><strong style="font-size:13px;color:' + (preview.has_blocks ? GREEN : '#646970') + ';">' + (preview.has_blocks ? IE.i18n.supported : '—') + '</strong></div>';

        if (preview.has_meta) {
            detailsHtml += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f1;"><span style="color:#646970;font-size:13px;">' + IE.i18n.metadata + '</span><strong style="font-size:13px;color:' + GREEN + ';">' + IE.i18n.yes + '</strong></div>';
        }
        if (preview.has_dates) {
            detailsHtml += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f1;"><span style="color:#646970;font-size:13px;">' + IE.i18n.dates + '</span><strong style="font-size:13px;color:' + GREEN + ';">' + IE.i18n.yes + '</strong></div>';
        }
        if (preview.has_author) {
            detailsHtml += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f1;"><span style="color:#646970;font-size:13px;">' + IE.i18n.author + '</span><strong style="font-size:13px;color:' + GREEN + ';">' + IE.i18n.yes + '</strong></div>';
        }

        var sampleHtml = '';
        if (preview.sample_docs && preview.sample_docs.length > 0) {
            sampleHtml = '<div style="margin-top:12px;"><p style="font-size:12px;color:#646970;margin:0 0 6px;font-weight:600;">' + IE.i18n.documentsFound + ':</p>';
            preview.sample_docs.forEach(function (s) {
                sampleHtml += '<div style="font-size:13px;padding:3px 0;color:#1d2327;">' + escapeHtml(s.title) + '</div>';
            });
            if (preview.document_count > preview.sample_docs.length) {
                sampleHtml += '<div style="font-size:12px;color:#a7aaad;padding:3px 0;">+' + (preview.document_count - preview.sample_docs.length) + ' more</div>';
            }
            sampleHtml += '</div>';
        }

        var html =
            '<div style="padding:24px 28px 0;">' +
            '<div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">' +
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="' + THEME + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>' +
            '<h2 style="margin:0;font-size:18px;font-weight:700;color:#1d2327;">' + IE.i18n.previewTitle + '</h2>' +
            '</div>' +
            '<p style="margin:4px 0 16px;font-size:14px;color:#50575e;">' + preview.document_count + ' ' + IE.i18n.documents + ' ' + IE.i18n.readyToImport.toLowerCase() + '</p>' +
            '<div style="background:#f6f7f7;border-radius:10px;padding:12px 16px;font-size:13px;">' + detailsHtml + '</div>' +
            sampleHtml +
            '</div>' +
            '<div style="display:flex;justify-content:flex-end;gap:8px;padding:16px 28px;border-top:1px solid #f0f0f1;">' +
            '<button type="button" id="zipped-docs-preview-cancel" class="button" style="min-height:36px;padding:6px 16px;border-radius:8px;">' + IE.i18n.cancel + '</button>' +
            '<button type="button" id="zipped-docs-preview-proceed" class="button button-primary" style="min-height:36px;padding:6px 20px;border-radius:8px;border:none;background:' + THEME + ';color:#fff;font-weight:500;cursor:pointer;">' + IE.i18n.proceedImport + '</button>' +
            '</div>';

        ImportExportModal.setContent(html);

        modal.querySelector('#zipped-docs-preview-cancel').addEventListener('click', function () { ImportExportModal.close(null); });
        modal.querySelector('#zipped-docs-preview-proceed').addEventListener('click', function () {
            proceedImportAfterPreview(file, modal);
        });
    }

    function proceedImportAfterPreview(file, modal) {
        var status = modal.querySelector('#zipped-docs-import-status') || createStatusElement(modal);
        status.style.display = 'block';
        status.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;gap:8px;color:' + THEME + ';font-weight:500;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="zipped-docs-spinner" style="animation:zipped-docs-spin 1s linear infinite;"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>' + IE.i18n.processing + '</div>';

        var formData = new FormData();
        formData.append('action', 'zipped_docs_import_upload');
        formData.append('_wpnonce', IE.importNonce);
        formData.append('zipped_docs_import_file', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', IE.ajaxUrl, true);
        xhr.onload = function () {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (!resp.success) {
                    showImportError(modal, resp.data && resp.data.message ? resp.data.message : IE.i18n.uploadError);
                    return;
                }
                handleImportResult(modal, resp.data);
            } catch (e) {
                showImportError(modal, IE.i18n.uploadError);
            }
        };
        xhr.onerror = function () { showImportError(modal, IE.i18n.uploadError); };
        xhr.send(formData);
    }

    function createStatusElement(modal) {
        var status = document.createElement('div');
        status.id = 'zipped-docs-import-status';
        status.style.cssText = 'padding:20px 28px;text-align:center;';
        var footer = modal.querySelector('div[style*="border-top"]');
        if (footer) {
            modal.insertBefore(status, footer);
        } else {
            modal.appendChild(status);
        }
        return status;
    }

    function showImportError(modal, message) {
        var status = modal.querySelector('#zipped-docs-import-status');
        if (!status) { status = createStatusElement(modal); }
        status.style.display = 'block';
        status.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;gap:8px;color:' + RED + ';font-weight:500;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' + escapeHtml(message) + '</div>';
    }

    function handleImportResult(modal, data) {
        if (data.has_conflicts) {
            showConflictResolution(modal, data);
            return;
        }
        showImportSummary(modal, data);
    }

    function showConflictResolution(modal, data) {
        var conflicts = data.conflicts || [];

        var conflictItems = '';
        conflicts.forEach(function (c, i) {
            conflictItems +=
                '<div class="zipped-docs-conflict-item" style="background:#f6f7f7;border-radius:10px;padding:16px;margin-bottom:12px;">' +
                '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;flex-wrap:wrap;gap:8px;">' +
                '<div style="min-width:0;">' +
                '<strong style="display:block;font-size:14px;color:#1d2327;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px;">' + escapeHtml(c.doc.title || 'Untitled') + '</strong>' +
                '<span style="font-size:12px;color:#646970;">' + IE.i18n.existing + ': ' + escapeHtml(c.existing_title) + ' (ID: ' + c.existing_id + ')</span>' +
                '</div></div>' +
                '<div style="display:flex;gap:8px;flex-wrap:wrap;">' +
                '<label class="zipped-docs-conflict-option" style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:#fff;border:1px solid #dcdcde;border-radius:6px;cursor:pointer;font-size:13px;" data-value="create"><input type="radio" name="conflict_' + i + '" value="create" checked style="margin:0;accent-color:' + THEME + ';">' + IE.i18n.createNew + '</label>' +
                '<label class="zipped-docs-conflict-option" style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:#fff;border:1px solid #dcdcde;border-radius:6px;cursor:pointer;font-size:13px;" data-value="replace"><input type="radio" name="conflict_' + i + '" value="replace" style="margin:0;accent-color:' + THEME + ';">' + IE.i18n.replaceExisting + '</label>' +
                '<label class="zipped-docs-conflict-option" style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:#fff;border:1px solid #dcdcde;border-radius:6px;cursor:pointer;font-size:13px;" data-value="update"><input type="radio" name="conflict_' + i + '" value="update" style="margin:0;accent-color:' + THEME + ';">' + IE.i18n.updateExisting + '</label>' +
                '<label class="zipped-docs-conflict-option" style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:#fff;border:1px solid #dcdcde;border-radius:6px;cursor:pointer;font-size:13px;" data-value="skip"><input type="radio" name="conflict_' + i + '" value="skip" style="margin:0;accent-color:' + THEME + ';">' + IE.i18n.skip + '</label>' +
                '</div></div>';
        });

        var html =
            '<div style="padding:24px 28px 0;">' +
            '<div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">' +
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="' + AMBER + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>' +
            '<h2 style="margin:0;font-size:18px;font-weight:700;color:#1d2327;">' + IE.i18n.conflictsFound + '</h2>' +
            '</div>' +
            '<p style="margin:4px 0 8px;font-size:13px;color:#646970;">' + IE.i18n.conflictMessage + '</p>' +
            '<div style="border-bottom:1px solid #f0f0f1;padding-bottom:12px;margin-bottom:12px;">' +
            '<label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#50575e;cursor:pointer;"><input type="checkbox" id="zipped-docs-apply-all" style="accent-color:' + THEME + ';"> ' + IE.i18n.applyAll +
            '<select id="zipped-docs-apply-all-value" style="margin-left:4px;padding:2px 6px;font-size:12px;border-radius:4px;border:1px solid #dcdcde;" disabled>' +
            '<option value="create">' + IE.i18n.createNew + '</option>' +
            '<option value="replace">' + IE.i18n.replaceExisting + '</option>' +
            '<option value="update">' + IE.i18n.updateExisting + '</option>' +
            '<option value="skip">' + IE.i18n.skip + '</option>' +
            '</select></label></div>' +
            '<div style="max-height:320px;overflow-y:auto;padding-right:4px;">' + conflictItems + '</div>' +
            '</div>' +
            '<div style="display:flex;justify-content:flex-end;gap:8px;padding:16px 28px;border-top:1px solid #f0f0f1;">' +
            '<button type="button" class="button zipped-docs-ie-close" style="min-height:36px;padding:6px 16px;border-radius:8px;">' + IE.i18n.close + '</button>' +
            '<button type="button" id="zipped-docs-confirm-import" class="button button-primary" style="min-height:36px;padding:6px 20px;border-radius:8px;border:none;background:' + THEME + ';color:#fff;font-weight:500;cursor:pointer;">' + IE.i18n.importTitle + '</button>' +
            '</div>';

        ImportExportModal.setContent(html);

        var applyAllCheck = modal.querySelector('#zipped-docs-apply-all');
        var applyAllValue = modal.querySelector('#zipped-docs-apply-all-value');

        applyAllCheck.addEventListener('change', function () { applyAllValue.disabled = !applyAllCheck.checked; });

        applyAllValue.addEventListener('change', function () {
            if (applyAllCheck.checked) {
                var val = applyAllValue.value;
                modal.querySelectorAll('.zipped-docs-conflict-item input[type="radio"]').forEach(function (r) {
                    if (r.value === val) { r.checked = true; }
                });
            }
        });

        modal.querySelectorAll('.zipped-docs-conflict-option').forEach(function (label) {
            label.addEventListener('click', function () {
                var parent = label.closest('.zipped-docs-conflict-item');
                parent.querySelectorAll('.zipped-docs-conflict-option').forEach(function (l) { l.style.borderColor = '#dcdcde'; });
                label.style.borderColor = THEME;
            });
        });

        modal.querySelectorAll('.zipped-docs-conflict-option input:checked').forEach(function (r) {
            r.closest('.zipped-docs-conflict-option').style.borderColor = THEME;
        });

        modal.querySelector('#zipped-docs-confirm-import').addEventListener('click', function () {
            var decisions = { documents: [] };
            conflicts.forEach(function (c, i) {
                var selected = modal.querySelector('input[name="conflict_' + i + '"]:checked');
                var decision = selected ? selected.value : 'skip';
                decisions.documents.push({ doc: c.doc, decision: decision, existing_id: c.existing_id });
            });
            processImportDecisions(decisions, modal);
        });

        modal.querySelector('.zipped-docs-ie-close').addEventListener('click', function () { ImportExportModal.close(null); });
    }

    function processImportDecisions(decisions, modal) {
        var status = modal.querySelector('#zipped-docs-import-status');
        if (!status) { status = createStatusElement(modal); }
        status.style.display = 'block';
        status.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;gap:8px;color:' + THEME + ';font-weight:500;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="zipped-docs-spinner" style="animation:zipped-docs-spin 1s linear infinite;"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>' + IE.i18n.processing + '</div>';

        var formData = new FormData();
        formData.append('action', 'zipped_docs_import_process');
        formData.append('_wpnonce', IE.importNonce);
        formData.append('decisions', JSON.stringify(decisions));

        var xhr = new XMLHttpRequest();
        xhr.open('POST', IE.ajaxUrl, true);
        xhr.onload = function () {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (!resp.success) {
                    showImportError(modal, resp.data && resp.data.message ? resp.data.message : IE.i18n.uploadError);
                    return;
                }
                showImportSummary(modal, resp.data);
            } catch (e) { showImportError(modal, IE.i18n.uploadError); }
        };
        xhr.onerror = function () { showImportError(modal, IE.i18n.uploadError); };
        xhr.send(formData);
    }

    function showImportSummary(modal, data) {
        var imported = (data.imported || []).length;
        var replaced = (data.replaced || []).length;
        var updated = (data.updated || []).length;
        var skipped = (data.skipped || []).length;
        var errors = data.errors || [];
        var warnings = data.warnings || [];

        var resultsHtml = '';
        var total = imported + replaced + updated + skipped;

        if (imported > 0) {
            resultsHtml += '<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f1;"><span>' + IE.i18n.imported + '</span><strong style="color:' + GREEN + ';">' + imported + '</strong></div>';
        }
        if (replaced > 0) {
            resultsHtml += '<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f1;"><span>' + IE.i18n.replaced + '</span><strong style="color:' + THEME + ';">' + replaced + '</strong></div>';
        }
        if (updated > 0) {
            resultsHtml += '<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f1;"><span>' + IE.i18n.updated + '</span><strong style="color:' + THEME + ';">' + updated + '</strong></div>';
        }
        if (skipped > 0) {
            resultsHtml += '<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f1;"><span>' + IE.i18n.skipped + '</span><strong>' + skipped + '</strong></div>';
        }

        var errorHtml = '';
        if (errors.length > 0) {
            errorHtml = '<div style="margin-top:12px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;">' +
                '<strong style="color:' + RED + ';font-size:13px;">' + IE.i18n.errors + ' (' + errors.length + ')</strong>' +
                '<ul style="margin:8px 0 0;padding:0 0 0 16px;font-size:12px;color:#991b1b;">';
            errors.forEach(function (e) { errorHtml += '<li style="margin-bottom:4px;">' + escapeHtml(e) + '</li>'; });
            errorHtml += '</ul></div>';
        }

        var warningHtml = '';
        if (warnings.length > 0) {
            warningHtml = '<div style="margin-top:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;">' +
                '<strong style="color:#92400e;font-size:13px;">' + IE.i18n.missingMedia + ' (' + warnings.length + ')</strong>' +
                '<ul style="margin:8px 0 0;padding:0 0 0 16px;font-size:12px;color:#92400e;">';
            warnings.forEach(function (w) { warningHtml += '<li style="margin-bottom:4px;">' + escapeHtml(w) + '</li>'; });
            warningHtml += '</ul></div>';
        }

        var html =
            '<div style="padding:24px 28px 0;">' +
            '<div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">' +
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="' + GREEN + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
            '<h2 style="margin:0;font-size:18px;font-weight:700;color:#1d2327;">' + IE.i18n.importComplete + '</h2>' +
            '</div>' +
            '<p style="margin:4px 0 16px;font-size:14px;color:#50575e;">' + total + ' ' + IE.i18n.documents + '</p>' +
            '<div style="background:#f6f7f7;border-radius:10px;padding:12px 16px;font-size:13px;">' + resultsHtml + '</div>' +
            errorHtml + warningHtml +
            '</div>' +
            '<div style="display:flex;justify-content:flex-end;gap:8px;padding:16px 28px;border-top:1px solid #f0f0f1;">' +
            '<button type="button" id="zipped-docs-import-another" class="button" style="min-height:36px;padding:6px 16px;border-radius:8px;">' + IE.i18n.importAnother + '</button>' +
            '<a href="' + (window.adminUrl || 'admin.php?page=zipped-docs') + '" class="button button-primary" style="min-height:36px;padding:6px 20px;border-radius:8px;border:none;background:' + THEME + ';color:#fff;font-weight:500;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;">' + IE.i18n.viewDocs + '</a>' +
            '<button type="button" class="button zipped-docs-ie-close" style="min-height:36px;padding:6px 16px;border-radius:8px;">' + IE.i18n.close + '</button>' +
            '</div>';

        ImportExportModal.setContent(html);

        modal.querySelector('#zipped-docs-import-another').addEventListener('click', function () {
            ImportExportModal.close(null);
            setTimeout(function () { openImportModal(); }, 250);
        });
        modal.querySelector('.zipped-docs-ie-close').addEventListener('click', function () { ImportExportModal.close(null); });
    }

    function exportSelectedDocs() {
        var checkboxes = document.querySelectorAll('.zipped-docs-export-checkbox:checked');
        var ids = [];
        checkboxes.forEach(function (cb) { ids.push(parseInt(cb.value, 10)); });

        if (ids.length === 0) {
            ZippedDocsPopup.alert(IE.i18n.noDocsSelected, { type: 'warning', title: IE.i18n.exportTitle });
            return;
        }

        exportDocsByIds(ids);
    }

    function exportDocsByIds(ids) {
        var html =
            '<div style="padding:24px 28px;text-align:center;">' +
            '<div style="display:flex;align-items:center;justify-content:center;gap:8px;color:' + THEME + ';font-weight:500;margin-bottom:12px;">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="zipped-docs-spinner" style="animation:zipped-docs-spin 1s linear infinite;"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>' +
            IE.i18n.exporting + '</div>' +
            '<p style="font-size:13px;color:#646970;margin:0;">' + ids.length + ' ' + IE.i18n.documents + '</p>' +
            '</div>';

        ImportExportModal.open({ html: html, width: '480px', closable: false });

        var formData = new FormData();
        formData.append('action', 'zipped_docs_export');
        formData.append('_wpnonce', IE.exportNonce);
        formData.append('doc_ids', JSON.stringify(ids));

        var xhr = new XMLHttpRequest();
        xhr.open('POST', IE.ajaxUrl, true);
        xhr.onload = function () {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (!resp.success) {
                    showExportError(resp.data && resp.data.message ? escapeHtml(resp.data.message) : IE.i18n.uploadError);
                    return;
                }
                triggerDownload(resp.data);
            } catch (e) { showExportError(IE.i18n.uploadError); }
        };
        xhr.onerror = function () { showExportError(IE.i18n.uploadError); };
        xhr.send(formData);
    }

    function showExportError(message) {
        ImportExportModal.setContent(
            '<div style="padding:24px 28px;text-align:center;">' +
            '<div style="color:' + RED + ';font-weight:500;margin-bottom:8px;">' + message + '</div>' +
            '<button type="button" class="button zipped-docs-ie-close" style="min-height:36px;padding:6px 16px;border-radius:8px;">' + IE.i18n.close + '</button></div>'
        );
        ImportExportModal.getModal().querySelector('.zipped-docs-ie-close').addEventListener('click', function () { ImportExportModal.close(null); });
    }

    function triggerDownload(data) {
        var filename = 'zipped-docs-export-' + new Date().toISOString().slice(0, 10) + '.json';
        var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        var total = data.total_documents || 0;
        var html =
            '<div style="padding:24px 28px;">' +
            '<div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">' +
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="' + GREEN + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
            '<h2 style="margin:0;font-size:18px;font-weight:700;color:#1d2327;">' + IE.i18n.exportComplete + '</h2></div>' +
            '<p style="font-size:14px;color:#50575e;margin:8px 0 0;">' + total + ' ' + IE.i18n.documents + '</p>' +
            '</div>' +
            '<div style="display:flex;justify-content:flex-end;gap:8px;padding:16px 28px;border-top:1px solid #f0f0f1;">' +
            '<button type="button" class="button zipped-docs-ie-close" style="min-height:36px;padding:6px 16px;border-radius:8px;">' + IE.i18n.close + '</button></div>';

        ImportExportModal.setContent(html);
        ImportExportModal.getModal().querySelector('.zipped-docs-ie-close').addEventListener('click', function () { ImportExportModal.close(null); });
    }

    function escapeHtml(str) {
        if (typeof str !== 'string') return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function boot() {
        var style = document.createElement('style');
        style.textContent = '@keyframes zipped-docs-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        document.head.appendChild(style);

        var importBtn = document.getElementById('zipped-docs-import-btn');
        if (importBtn) {
            importBtn.addEventListener('click', function (e) { e.preventDefault(); openImportModal(); });
        }

        var exportBtn = document.getElementById('zipped-docs-export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', function (e) { e.preventDefault(); exportSelectedDocs(); });
        }

        var selectAll = document.getElementById('zipped-docs-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.zipped-docs-export-checkbox').forEach(function (cb) { cb.checked = selectAll.checked; });
            });
        }

        document.querySelectorAll('.zipped-docs-export-single').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var docId = parseInt(link.getAttribute('data-doc-id'), 10);
                if (docId) { exportDocsByIds([docId]); }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
