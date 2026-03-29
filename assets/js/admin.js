/**
 * Admin Panel JavaScript
 * Business Website CMS
 */

document.addEventListener('DOMContentLoaded', function() {

    // ---- CSRF Token for AJAX ----
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    // Patch XMLHttpRequest to include CSRF header on same-origin requests
    var origOpen = XMLHttpRequest.prototype.open;
    var origSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function(method) {
        this._method = method;
        return origOpen.apply(this, arguments);
    };
    XMLHttpRequest.prototype.send = function() {
        if (this._method && this._method.toUpperCase() !== 'GET' && csrfToken) {
            this.setRequestHeader('X-CSRF-Token', csrfToken);
        }
        return origSend.apply(this, arguments);
    };

    // Patch fetch to include CSRF header on non-GET requests
    var origFetch = window.fetch;
    window.fetch = function(url, opts) {
        opts = opts || {};
        var method = (opts.method || 'GET').toUpperCase();
        if (method !== 'GET' && csrfToken) {
            opts.headers = opts.headers || {};
            if (opts.headers instanceof Headers) {
                opts.headers.set('X-CSRF-Token', csrfToken);
            } else {
                opts.headers['X-CSRF-Token'] = csrfToken;
            }
        }
        return origFetch.call(this, url, opts);
    };

    // ---- Sidebar Toggle (Mobile) ----
    var sidebar = document.getElementById('adminSidebar');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarClose = document.getElementById('sidebarClose');

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', 'false');
        }
    }

    function toggleSidebar() {
        if (!sidebar) return;
        var isOpen = sidebar.classList.toggle('open');
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            toggleSidebar();
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
            closeSidebar();
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('open')) {
            var clickedToggle = sidebarToggle && (e.target === sidebarToggle || sidebarToggle.contains(e.target));
            if (!sidebar.contains(e.target) && !clickedToggle) {
                closeSidebar();
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
            if (sidebarToggle) sidebarToggle.focus();
        }
    });

    // ---- Color Input Sync ----
    var colorInputs = document.querySelectorAll('.color-input');
    colorInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            var textInput = this.parentElement.querySelector('.color-text');
            if (textInput) textInput.value = this.value;
        });
    });

    // ---- Auto-dismiss alerts ----
    var alerts = document.querySelectorAll('.admin-alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 5000);
    });

    // ---- Confirm delete actions ----
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

});
