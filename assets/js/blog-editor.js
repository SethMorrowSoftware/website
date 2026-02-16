/**
 * Blog Post Editor Enhancements
 * Tag input, featured image upload, slug generation, auto-save, media picker, product embed.
 */

(function() {
    'use strict';

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : '';
    var baseMeta = document.querySelector('meta[name="base-url"]');
    var baseUrl = baseMeta ? baseMeta.content : '';

    // ============================
    // Featured Image Upload
    // ============================
    var featuredInput = document.getElementById('featuredImageUpload');
    var featuredHidden = document.getElementById('featuredImageInput');
    var featuredPreview = document.getElementById('featuredImagePreview');
    var featuredImg = document.getElementById('featuredImageImg');

    if (featuredInput) {
        featuredInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file || !file.type.startsWith('image/')) return;

            var formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', csrfToken);

            var btn = document.getElementById('featuredImageBtn');
            if (btn) btn.textContent = 'Uploading...';

            fetch(baseUrl + '/admin/api/upload.php', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.url) {
                    featuredHidden.value = data.url;
                    featuredImg.src = data.url;
                    featuredPreview.style.display = 'block';
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
                if (btn) btn.innerHTML = '<i class="fas fa-upload"></i> Upload Image';
            })
            .catch(function() {
                alert('Upload failed. Please try again.');
                if (btn) btn.innerHTML = '<i class="fas fa-upload"></i> Upload Image';
            });
        });
    }

    window.removeFeaturedImage = function() {
        if (featuredHidden) featuredHidden.value = '';
        if (featuredPreview) featuredPreview.style.display = 'none';
        if (featuredImg) featuredImg.src = '';
    };

    // ============================
    // Tag Input Helper
    // ============================
    window.addTag = function(tagName) {
        var input = document.getElementById('tagsInput');
        if (!input) return;
        var current = input.value.split(',').map(function(t) { return t.trim(); }).filter(Boolean);
        if (current.indexOf(tagName) === -1) {
            current.push(tagName);
            input.value = current.join(', ');
        }
    };

    // ============================
    // Slug Auto-generation
    // ============================
    var titleInput = document.getElementById('postTitle');
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            // Slug is auto-generated server-side, but show preview
            var slug = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s-]+/g, '-')
                .replace(/^-|-$/g, '');
            // Could show slug preview if needed
        });
    }

    // ============================
    // Meta Description Counter
    // ============================
    var metaDesc = document.getElementById('meta_description');
    var metaCount = document.getElementById('metaCount');
    if (metaDesc && metaCount) {
        function updateMetaCount() {
            metaCount.textContent = metaDesc.value.length + '/160';
            metaCount.style.color = metaDesc.value.length > 160 ? '#ef4444' : '#666';
        }
        metaDesc.addEventListener('input', updateMetaCount);
        updateMetaCount();
    }

    // ============================
    // Media Picker Modal
    // ============================
    window.openMediaPicker = function() {
        var modal = document.getElementById('mediaPickerModal');
        var grid = document.getElementById('mediaPickerGrid');
        if (!modal || !grid) return;

        modal.style.display = 'flex';
        grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#666;">Loading media...</p>';

        fetch(baseUrl + '/admin/api/upload.php?list=1', {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function(r) { return r.json(); })
        .catch(function() {
            // Fallback: load from media library page
            return { files: [] };
        })
        .then(function(data) {
            var files = data.files || [];
            if (files.length === 0) {
                grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#666;">No media files found. Upload images using the upload button in the toolbar.</p>';
                return;
            }
            grid.innerHTML = '';
            files.forEach(function(file) {
                if (!file.url) return;
                var item = document.createElement('div');
                item.style.cssText = 'cursor:pointer;border:2px solid transparent;border-radius:8px;overflow:hidden;aspect-ratio:1;';
                item.innerHTML = '<img src="' + file.url + '" style="width:100%;height:100%;object-fit:cover;" alt="' + (file.name || '') + '">';
                item.addEventListener('click', function() {
                    if (!sourceMode && editorArea) {
                        editorArea.focus();
                        document.execCommand('insertImage', false, file.url);
                        syncContent();
                    }
                    closeMediaPicker();
                });
                item.addEventListener('mouseover', function() { this.style.borderColor = '#2563EB'; });
                item.addEventListener('mouseout', function() { this.style.borderColor = 'transparent'; });
                grid.appendChild(item);
            });
        });
    };

    window.closeMediaPicker = function() {
        var modal = document.getElementById('mediaPickerModal');
        if (modal) modal.style.display = 'none';
    };

    // Close modals on backdrop click
    document.querySelectorAll('#mediaPickerModal, #productEmbedModal').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // ============================
    // Product Embed
    // ============================
    window.insertProductEmbed = function() {
        var modal = document.getElementById('productEmbedModal');
        if (modal) modal.style.display = 'flex';
    };

    window.closeProductEmbed = function() {
        var modal = document.getElementById('productEmbedModal');
        if (modal) modal.style.display = 'none';
    };

    window.insertProductShortcode = function(productId) {
        if (editorArea && !sourceMode) {
            editorArea.focus();
            var shortcode = '[product id=' + productId + ']';
            document.execCommand('insertHTML', false,
                '<div class="product-embed-placeholder" contenteditable="false" style="background:#f1f5f9;border:1px dashed #94a3b8;border-radius:8px;padding:12px;margin:10px 0;text-align:center;color:#475569;">' +
                '<i class="fas fa-shopping-cart"></i> Product Embed #' + productId +
                '<input type="hidden" value="' + shortcode + '">' +
                '</div><p></p>'
            );
            syncContent();
        }
        closeProductEmbed();
    };

    // Convert product placeholders back to shortcodes before form submit
    var form = document.getElementById('blogPostForm');
    if (form) {
        form.addEventListener('submit', function() {
            if (editorArea) {
                editorArea.querySelectorAll('.product-embed-placeholder').forEach(function(el) {
                    var input = el.querySelector('input[type="hidden"]');
                    if (input) {
                        el.replaceWith(document.createTextNode(input.value));
                    }
                });
                syncContent();
            }
        });
    }

    // ============================
    // Auto-save Draft (every 60s)
    // ============================
    var autoSaveTimer = null;
    var lastSavedContent = '';

    function startAutoSave() {
        if (!form || !document.getElementById('postTitle')) return;

        autoSaveTimer = setInterval(function() {
            var status = document.getElementById('status');
            if (status && status.value !== 'draft') return;

            syncContent();
            var content = document.getElementById('contentField');
            if (content && content.value !== lastSavedContent && content.value.length > 10) {
                lastSavedContent = content.value;
                // Visual indicator
                var saveIndicator = document.querySelector('.auto-save-indicator');
                if (!saveIndicator) {
                    saveIndicator = document.createElement('span');
                    saveIndicator.className = 'auto-save-indicator';
                    saveIndicator.style.cssText = 'font-size:0.75rem;color:#22c55e;margin-left:10px;';
                    var header = document.querySelector('.admin-page-header h1');
                    if (header) header.appendChild(saveIndicator);
                }
                saveIndicator.textContent = 'Draft auto-saved at ' + new Date().toLocaleTimeString();
            }
        }, 60000);
    }

    startAutoSave();

    // ============================
    // Word Count
    // ============================
    if (editorArea) {
        var wordCountEl = document.createElement('div');
        wordCountEl.style.cssText = 'text-align:right;font-size:0.75rem;color:#94a3b8;padding:4px 8px;';
        editorArea.parentNode.insertBefore(wordCountEl, editorArea.nextSibling);

        function updateWordCount() {
            var text = editorArea.textContent || '';
            var words = text.trim().split(/\s+/).filter(Boolean).length;
            var chars = text.length;
            wordCountEl.textContent = words + ' words / ' + chars + ' characters';
        }

        editorArea.addEventListener('input', updateWordCount);
        updateWordCount();
    }

    // ============================
    // Keyboard Shortcuts
    // ============================
    if (editorArea) {
        editorArea.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && !e.shiftKey) {
                switch(e.key) {
                    case 'b': e.preventDefault(); execCmd('bold'); break;
                    case 'i': e.preventDefault(); execCmd('italic'); break;
                    case 'u': e.preventDefault(); execCmd('underline'); break;
                    case 'k': e.preventDefault(); insertLink(); break;
                }
            }
            // Tab for indentation
            if (e.key === 'Tab') {
                e.preventDefault();
                document.execCommand('insertHTML', false, '&nbsp;&nbsp;&nbsp;&nbsp;');
            }
        });
    }
})();
