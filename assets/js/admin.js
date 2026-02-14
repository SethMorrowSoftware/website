/**
 * Admin Panel JavaScript
 * Hudson Valley Supply & Recycling LLC
 */

document.addEventListener('DOMContentLoaded', function() {

    // ---- Sidebar Toggle (Mobile) ----
    var sidebar = document.getElementById('adminSidebar');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarClose = document.getElementById('sidebarClose');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('open');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove('open');
            }
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
