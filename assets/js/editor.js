/**
 * Rich Text Editor
 * Lightweight WYSIWYG editor for admin panel
 */

var editorArea = document.getElementById('editorArea');
var contentField = document.getElementById('contentField');
var sourceMode = false;

function execCmd(command, value) {
    if (sourceMode) return;

    if (command === 'formatBlock') {
        document.execCommand('formatBlock', false, '<' + value + '>');
    } else {
        document.execCommand(command, false, value || null);
    }
    editorArea.focus();
    syncContent();
}

function insertLink() {
    if (sourceMode) return;
    var url = prompt('Enter URL:', 'https://');
    if (url) {
        document.execCommand('createLink', false, url);
        syncContent();
    }
}

function insertImage() {
    if (sourceMode) return;
    var baseUrl = document.querySelector('meta[name="base-url"]');
    var defaultPath = (baseUrl ? baseUrl.content : '') + '/uploads/images/';
    var url = prompt('Enter image URL:', defaultPath);
    if (url) {
        document.execCommand('insertImage', false, url);
        syncContent();
    }
}

/**
 * Upload an image file and insert it into the editor.
 */
function uploadAndInsertImage(file) {
    if (sourceMode || !file || !file.type.startsWith('image/')) return;

    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    var formData = new FormData();
    formData.append('file', file);
    if (csrfToken) formData.append('csrf_token', csrfToken.content);

    // Show uploading indicator
    var placeholder = document.createElement('span');
    placeholder.textContent = '[Uploading image...]';
    placeholder.style.color = '#999';
    placeholder.style.fontStyle = 'italic';

    var sel = window.getSelection();
    if (sel.rangeCount) {
        sel.getRangeAt(0).insertNode(placeholder);
    } else {
        editorArea.appendChild(placeholder);
    }

    fetch(getBaseUrl() + '/admin/api/upload.php', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '' },
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.url) {
            var img = document.createElement('img');
            img.src = data.url;
            img.style.maxWidth = '100%';
            img.alt = file.name.replace(/\.[^.]+$/, '');
            placeholder.replaceWith(img);
        } else {
            placeholder.textContent = '[Upload failed]';
            setTimeout(function() { placeholder.remove(); }, 2000);
        }
        syncContent();
    })
    .catch(function() {
        placeholder.textContent = '[Upload failed]';
        setTimeout(function() { placeholder.remove(); }, 2000);
        syncContent();
    });
}

/**
 * Trigger file input for image upload in editor.
 */
function triggerImageUpload() {
    if (sourceMode) return;
    var input = document.getElementById('editorImageUpload');
    if (!input) {
        input = document.createElement('input');
        input.type = 'file';
        input.id = 'editorImageUpload';
        input.accept = 'image/*';
        input.multiple = true;
        input.style.display = 'none';
        document.body.appendChild(input);
    }
    input.value = '';
    input.onchange = function() {
        Array.from(this.files).forEach(function(f) { uploadAndInsertImage(f); });
    };
    input.click();
}

/**
 * Get base URL from meta tag.
 */
function getBaseUrl() {
    var meta = document.querySelector('meta[name="base-url"]');
    return meta ? meta.content : '';
}

/**
 * Enable drag-and-drop image upload on the editor area.
 */
if (editorArea) {
    editorArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        editorArea.style.outline = '2px dashed var(--color-primary, #2563EB)';
        editorArea.style.outlineOffset = '-4px';
    });
    editorArea.addEventListener('dragleave', function() {
        editorArea.style.outline = '';
        editorArea.style.outlineOffset = '';
    });
    editorArea.addEventListener('drop', function(e) {
        e.preventDefault();
        editorArea.style.outline = '';
        editorArea.style.outlineOffset = '';
        if (e.dataTransfer && e.dataTransfer.files) {
            Array.from(e.dataTransfer.files).forEach(function(f) {
                if (f.type.startsWith('image/')) uploadAndInsertImage(f);
            });
        }
    });

    // Paste image support
    editorArea.addEventListener('paste', function(e) {
        var items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                e.preventDefault();
                var file = items[i].getAsFile();
                uploadAndInsertImage(file);
                break;
            }
        }
    });
}

function toggleSource() {
    var sourceBtn = document.getElementById('sourceBtn');

    if (sourceMode) {
        // Switch from source to visual
        editorArea.innerHTML = editorArea.textContent;
        editorArea.contentEditable = 'true';
        sourceMode = false;
        if (sourceBtn) sourceBtn.style.background = '';
    } else {
        // Switch from visual to source
        editorArea.textContent = editorArea.innerHTML;
        editorArea.contentEditable = 'true';
        sourceMode = true;
        if (sourceBtn) sourceBtn.style.background = '#2563EB';
        if (sourceBtn) sourceBtn.style.color = '#fff';
    }
    syncContent();
}

function syncContent() {
    if (contentField) {
        if (sourceMode) {
            contentField.value = editorArea.textContent;
        } else {
            contentField.value = editorArea.innerHTML;
        }
    }
}

// Sync content on every change
if (editorArea) {
    editorArea.addEventListener('input', syncContent);
    editorArea.addEventListener('blur', syncContent);

    // Sync before form submit
    var form = editorArea.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            if (sourceMode) {
                editorArea.innerHTML = editorArea.textContent;
                sourceMode = false;
            }
            syncContent();
        });
    }
}
