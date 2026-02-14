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
    var url = prompt('Enter image URL:', '/uploads/images/');
    if (url) {
        document.execCommand('insertImage', false, url);
        syncContent();
    }
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
        if (sourceBtn) sourceBtn.style.background = '#1B4D3E';
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
