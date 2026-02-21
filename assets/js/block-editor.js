/**
 * Block Editor — Admin drag-and-drop block content builder.
 *
 * Reads block type definitions from window.blockTypeDefinitions
 * and initial blocks from window.initialBlocks.
 * Serializes to the hidden #blocksJsonField on form submit.
 */

(function () {
    'use strict';

    const blockList = document.getElementById('blockList');
    const blocksJsonField = document.getElementById('blocksJsonField');
    const addBlockBtn = document.getElementById('addBlockBtn');
    const blockPickerModal = document.getElementById('blockPickerModal');
    const pageForm = document.getElementById('pageForm');
    const typeDefs = window.blockTypeDefinitions || {};
    let blocks = window.initialBlocks || [];

    // ============================================================
    // Rendering
    // ============================================================

    function renderAllBlocks() {
        blockList.innerHTML = '';
        blocks.forEach(function (block, index) {
            blockList.appendChild(createBlockElement(block, index));
        });
        if (blocks.length === 0) {
            blockList.innerHTML = '<div class="block-editor-empty"><i class="fas fa-layer-group"></i><p>No blocks yet. Click "Add Block" to get started.</p></div>';
        }
    }

    function createBlockElement(block, index) {
        var def = typeDefs[block.type];
        if (!def) return document.createElement('div');

        var el = document.createElement('div');
        el.className = 'block-editor-item';
        el.dataset.index = index;
        el.setAttribute('draggable', 'true');

        // Header bar
        var header = document.createElement('div');
        header.className = 'block-editor-item-header';
        header.innerHTML =
            '<div class="block-editor-item-drag"><i class="fas fa-grip-vertical"></i></div>' +
            '<div class="block-editor-item-title"><i class="' + escHtml(def.icon) + '"></i> ' + escHtml(def.label) + '</div>' +
            '<div class="block-editor-item-actions">' +
            '<button type="button" class="block-btn-toggle" title="Toggle"><i class="fas fa-chevron-down"></i></button>' +
            '<button type="button" class="block-btn-move-up" title="Move Up"><i class="fas fa-arrow-up"></i></button>' +
            '<button type="button" class="block-btn-move-down" title="Move Down"><i class="fas fa-arrow-down"></i></button>' +
            '<button type="button" class="block-btn-duplicate" title="Duplicate"><i class="fas fa-copy"></i></button>' +
            '<button type="button" class="block-btn-delete" title="Delete"><i class="fas fa-trash"></i></button>' +
            '</div>';

        // Action handlers
        header.querySelector('.block-btn-toggle').addEventListener('click', function () {
            var body = el.querySelector('.block-editor-item-body');
            var icon = this.querySelector('i');
            if (body.style.display === 'none') {
                body.style.display = 'block';
                icon.className = 'fas fa-chevron-down';
            } else {
                body.style.display = 'none';
                icon.className = 'fas fa-chevron-right';
            }
        });
        header.querySelector('.block-btn-move-up').addEventListener('click', function () { moveBlock(index, -1); });
        header.querySelector('.block-btn-move-down').addEventListener('click', function () { moveBlock(index, 1); });
        header.querySelector('.block-btn-duplicate').addEventListener('click', function () { duplicateBlock(index); });
        header.querySelector('.block-btn-delete').addEventListener('click', function () { deleteBlock(index); });

        el.appendChild(header);

        // Body — field editors
        var body = document.createElement('div');
        body.className = 'block-editor-item-body';

        var fields = def.fields || {};
        for (var fieldName in fields) {
            if (!fields.hasOwnProperty(fieldName)) continue;
            var fieldDef = fields[fieldName];
            var value = (block.data && block.data[fieldName]) || fieldDef.default || '';
            body.appendChild(createFieldEditor(fieldName, fieldDef, value, index));
        }

        el.appendChild(body);

        // Drag events
        el.addEventListener('dragstart', handleDragStart);
        el.addEventListener('dragover', handleDragOver);
        el.addEventListener('drop', handleDrop);
        el.addEventListener('dragend', handleDragEnd);

        return el;
    }

    function createFieldEditor(fieldName, fieldDef, value, blockIndex) {
        var group = document.createElement('div');
        group.className = 'form-group';

        var label = document.createElement('label');
        label.textContent = fieldDef.label || fieldName;
        group.appendChild(label);

        var input;
        var type = fieldDef.type || 'text';

        switch (type) {
            case 'text':
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.value = value;
                input.addEventListener('input', function () {
                    updateBlockData(blockIndex, fieldName, this.value);
                });
                group.appendChild(input);
                break;

            case 'number':
                input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control';
                input.value = value;
                input.addEventListener('input', function () {
                    updateBlockData(blockIndex, fieldName, parseInt(this.value) || 0);
                });
                group.appendChild(input);
                break;

            case 'textarea':
            case 'richtext':
            case 'code':
                input = document.createElement('textarea');
                input.className = 'form-control';
                input.rows = type === 'code' ? 8 : 4;
                input.value = value;
                if (type === 'code') input.style.fontFamily = 'monospace';
                input.addEventListener('input', function () {
                    updateBlockData(blockIndex, fieldName, this.value);
                });
                group.appendChild(input);
                break;

            case 'select':
                input = document.createElement('select');
                input.className = 'form-control';
                var options = fieldDef.options || [];
                options.forEach(function (opt) {
                    var option = document.createElement('option');
                    option.value = opt;
                    option.textContent = opt;
                    if (opt === value) option.selected = true;
                    input.appendChild(option);
                });
                input.addEventListener('change', function () {
                    updateBlockData(blockIndex, fieldName, this.value);
                });
                group.appendChild(input);
                break;

            case 'image':
                var wrapper = document.createElement('div');
                wrapper.className = 'block-image-field';

                input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.value = value;
                input.placeholder = 'Image URL or upload';
                input.addEventListener('input', function () {
                    updateBlockData(blockIndex, fieldName, this.value);
                    updatePreview(this);
                });

                var uploadBtn = document.createElement('button');
                uploadBtn.type = 'button';
                uploadBtn.className = 'btn-admin btn-outline btn-sm';
                uploadBtn.innerHTML = '<i class="fas fa-upload"></i>';
                uploadBtn.addEventListener('click', function () {
                    var fileInput = document.createElement('input');
                    fileInput.type = 'file';
                    fileInput.accept = 'image/*';
                    fileInput.addEventListener('change', function () {
                        if (this.files[0]) {
                            uploadImage(this.files[0], function (url) {
                                input.value = url;
                                updateBlockData(blockIndex, fieldName, url);
                                updatePreview(input);
                            });
                        }
                    });
                    fileInput.click();
                });

                wrapper.appendChild(input);
                wrapper.appendChild(uploadBtn);

                if (value) {
                    var preview = document.createElement('img');
                    preview.src = value;
                    preview.className = 'block-image-preview';
                    preview.style.maxHeight = '100px';
                    preview.style.marginTop = '0.5rem';
                    preview.style.borderRadius = '4px';
                    wrapper.appendChild(preview);
                }

                group.appendChild(wrapper);
                break;

            case 'repeater':
                var repeaterFields = fieldDef.fields || {};
                var items = Array.isArray(value) ? value : [];
                var repeaterContainer = document.createElement('div');
                repeaterContainer.className = 'block-repeater';

                function renderRepeaterItems() {
                    repeaterContainer.innerHTML = '';
                    items.forEach(function (item, itemIdx) {
                        var itemEl = document.createElement('div');
                        itemEl.className = 'block-repeater-item';

                        for (var rField in repeaterFields) {
                            if (!repeaterFields.hasOwnProperty(rField)) continue;
                            var rDef = repeaterFields[rField];
                            var rVal = item[rField] || '';

                            var rLabel = document.createElement('label');
                            rLabel.textContent = rDef.label || rField;
                            rLabel.style.fontSize = '0.85rem';
                            itemEl.appendChild(rLabel);

                            var rInput;
                            if (rDef.type === 'textarea') {
                                rInput = document.createElement('textarea');
                                rInput.rows = 2;
                            } else if (rDef.type === 'image') {
                                rInput = document.createElement('input');
                                rInput.type = 'text';
                                rInput.placeholder = 'Image URL';
                            } else {
                                rInput = document.createElement('input');
                                rInput.type = 'text';
                            }
                            rInput.className = 'form-control';
                            rInput.style.marginBottom = '0.5rem';
                            rInput.value = rVal;
                            (function (rf, ii) {
                                rInput.addEventListener('input', function () {
                                    items[ii][rf] = this.value;
                                    updateBlockData(blockIndex, fieldName, items);
                                });
                            })(rField, itemIdx);
                            itemEl.appendChild(rInput);
                        }

                        var removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn-admin btn-outline btn-sm';
                        removeBtn.style.marginTop = '0.25rem';
                        removeBtn.innerHTML = '<i class="fas fa-trash"></i> Remove';
                        removeBtn.addEventListener('click', function () {
                            items.splice(itemIdx, 1);
                            updateBlockData(blockIndex, fieldName, items);
                            renderRepeaterItems();
                        });
                        itemEl.appendChild(removeBtn);

                        repeaterContainer.appendChild(itemEl);
                    });

                    var addBtn = document.createElement('button');
                    addBtn.type = 'button';
                    addBtn.className = 'btn-admin btn-outline btn-sm';
                    addBtn.innerHTML = '<i class="fas fa-plus"></i> Add Item';
                    addBtn.addEventListener('click', function () {
                        var newItem = {};
                        for (var rf in repeaterFields) {
                            newItem[rf] = '';
                        }
                        items.push(newItem);
                        updateBlockData(blockIndex, fieldName, items);
                        renderRepeaterItems();
                    });
                    repeaterContainer.appendChild(addBtn);
                }

                renderRepeaterItems();
                group.appendChild(repeaterContainer);
                break;

            default:
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.value = value;
                input.addEventListener('input', function () {
                    updateBlockData(blockIndex, fieldName, this.value);
                });
                group.appendChild(input);
        }

        return group;
    }

    function updatePreview(input) {
        var wrapper = input.closest('.block-image-field');
        if (!wrapper) return;
        var existing = wrapper.querySelector('.block-image-preview');
        if (existing) existing.remove();
        if (input.value) {
            var preview = document.createElement('img');
            preview.src = input.value;
            preview.className = 'block-image-preview';
            preview.style.maxHeight = '100px';
            preview.style.marginTop = '0.5rem';
            preview.style.borderRadius = '4px';
            wrapper.appendChild(preview);
        }
    }

    // ============================================================
    // Block Operations
    // ============================================================

    function updateBlockData(blockIndex, fieldName, value) {
        if (!blocks[blockIndex].data) blocks[blockIndex].data = {};
        blocks[blockIndex].data[fieldName] = value;
        syncToHiddenField();
    }

    function moveBlock(index, direction) {
        var newIndex = index + direction;
        if (newIndex < 0 || newIndex >= blocks.length) return;
        var temp = blocks[index];
        blocks[index] = blocks[newIndex];
        blocks[newIndex] = temp;
        renderAllBlocks();
    }

    function duplicateBlock(index) {
        var clone = JSON.parse(JSON.stringify(blocks[index]));
        blocks.splice(index + 1, 0, clone);
        renderAllBlocks();
    }

    function deleteBlock(index) {
        if (!confirm('Delete this block?')) return;
        blocks.splice(index, 1);
        renderAllBlocks();
    }

    function syncToHiddenField() {
        blocksJsonField.value = JSON.stringify(blocks);
    }

    // ============================================================
    // Block Picker
    // ============================================================

    window.addBlock = function (type) {
        blocks.push({ type: type, data: {} });
        closeBlockPicker();
        renderAllBlocks();
        syncToHiddenField();
        // Scroll to new block
        var lastBlock = blockList.lastElementChild;
        if (lastBlock) lastBlock.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    window.closeBlockPicker = function () {
        blockPickerModal.style.display = 'none';
    };

    addBlockBtn.addEventListener('click', function () {
        blockPickerModal.style.display = 'flex';
    });

    // ============================================================
    // Drag and Drop
    // ============================================================

    var dragSrcIndex = null;

    function handleDragStart(e) {
        dragSrcIndex = parseInt(this.dataset.index);
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragSrcIndex);
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        this.classList.add('drag-over');
    }

    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        var targetIndex = parseInt(this.dataset.index);
        if (dragSrcIndex === null || dragSrcIndex === targetIndex) return;

        var movedBlock = blocks.splice(dragSrcIndex, 1)[0];
        blocks.splice(targetIndex, 0, movedBlock);
        renderAllBlocks();
        syncToHiddenField();
    }

    function handleDragEnd() {
        this.classList.remove('dragging');
        document.querySelectorAll('.drag-over').forEach(function (el) {
            el.classList.remove('drag-over');
        });
        dragSrcIndex = null;
    }

    // ============================================================
    // Image Upload
    // ============================================================

    function uploadImage(file, callback) {
        var formData = new FormData();
        formData.append('file', file);

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) formData.append('csrf_token', csrfMeta.content);

        var baseUrl = document.querySelector('meta[name="base-url"]');
        var uploadUrl = (baseUrl ? baseUrl.content : '') + '/admin/api/upload.php';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.url) callback(resp.url);
                } catch (e) {
                    alert('Upload failed: Invalid response');
                }
            } else {
                alert('Upload failed: ' + xhr.status);
            }
        };
        xhr.send(formData);
    }

    // ============================================================
    // Form Submit
    // ============================================================

    pageForm.addEventListener('submit', function () {
        syncToHiddenField();
    });

    // ============================================================
    // Utilities
    // ============================================================

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ============================================================
    // Initialize
    // ============================================================

    renderAllBlocks();
    syncToHiddenField();

})();
