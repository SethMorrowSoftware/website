/**
 * AJAX Add-to-Cart & Toast Notification System
 * Business Website CMS
 */

(function() {
    'use strict';

    // ---- Inject Toast Styles ----
    var style = document.createElement('style');
    style.textContent = '' +
        '#toast-container {' +
            'position: fixed;' +
            'top: 20px;' +
            'right: 20px;' +
            'z-index: 10000;' +
            'display: flex;' +
            'flex-direction: column;' +
            'gap: 10px;' +
            'pointer-events: none;' +
        '}' +
        '.toast {' +
            'background: #fff;' +
            'border-radius: 6px;' +
            'box-shadow: 0 4px 12px rgba(0,0,0,0.15);' +
            'padding: 14px 40px 14px 18px;' +
            'min-width: 280px;' +
            'max-width: 400px;' +
            'display: flex;' +
            'align-items: center;' +
            'border-left: 4px solid #3b82f6;' +
            'position: relative;' +
            'pointer-events: auto;' +
            'animation: toastSlideIn 0.3s ease forwards;' +
            'font-family: inherit;' +
            'font-size: 14px;' +
            'line-height: 1.4;' +
            'color: #333;' +
        '}' +
        '.toast.toast-success {' +
            'border-left-color: #22c55e;' +
        '}' +
        '.toast.toast-error {' +
            'border-left-color: #ef4444;' +
        '}' +
        '.toast.toast-info {' +
            'border-left-color: #3b82f6;' +
        '}' +
        '.toast .toast-close {' +
            'position: absolute;' +
            'top: 50%;' +
            'right: 12px;' +
            'transform: translateY(-50%);' +
            'background: none;' +
            'border: none;' +
            'font-size: 18px;' +
            'cursor: pointer;' +
            'color: #999;' +
            'padding: 0 4px;' +
            'line-height: 1;' +
        '}' +
        '.toast .toast-close:hover {' +
            'color: #333;' +
        '}' +
        '.toast.toast-hiding {' +
            'animation: toastSlideOut 0.3s ease forwards;' +
        '}' +
        '@keyframes toastSlideIn {' +
            'from { opacity: 0; transform: translateX(100%); }' +
            'to { opacity: 1; transform: translateX(0); }' +
        '}' +
        '@keyframes toastSlideOut {' +
            'from { opacity: 1; transform: translateX(0); }' +
            'to { opacity: 0; transform: translateX(100%); }' +
        '}';
    document.head.appendChild(style);

    // ---- Toast Container ----
    var container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);

    /**
     * Show a toast notification.
     * @param {string} message - The message to display.
     * @param {string} type - 'success', 'error', or 'info'.
     */
    function showToast(message, type) {
        type = type || 'info';

        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;

        var text = document.createElement('span');
        text.textContent = message;
        toast.appendChild(text);

        var closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', 'Close notification');
        closeBtn.addEventListener('click', function() {
            dismissToast(toast);
        });
        toast.appendChild(closeBtn);

        container.appendChild(toast);

        // Auto-dismiss after 3 seconds
        var timer = setTimeout(function() {
            dismissToast(toast);
        }, 3000);

        toast._timer = timer;
    }

    function dismissToast(toast) {
        if (toast._dismissed) return;
        toast._dismissed = true;
        clearTimeout(toast._timer);
        toast.classList.add('toast-hiding');
        toast.addEventListener('animationend', function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        });
    }

    // Expose showToast globally
    window.showToast = showToast;

    // ---- AJAX Form Submission ----

    /**
     * Submit a form via AJAX (fetch POST) and handle the response.
     */
    function submitFormAjax(form, onSuccess) {
        var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        var originalText = '';

        // Prevent double-submit
        if (submitBtn) {
            if (submitBtn.disabled) return;
            submitBtn.disabled = true;
            originalText = submitBtn.textContent || submitBtn.value;
            if (submitBtn.tagName === 'BUTTON') {
                submitBtn.textContent = 'Please wait\u2026';
            } else {
                submitBtn.value = 'Please wait\u2026';
            }
        }

        var formData = new FormData(form);

        fetch(form.action || window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                showToast(data.message || 'Success!', 'success');
                if (onSuccess) {
                    onSuccess(data);
                }
            } else {
                showToast(data.message || 'Something went wrong.', 'error');
            }
        })
        .catch(function() {
            showToast('A network error occurred. Please try again.', 'error');
        })
        .finally(function() {
            if (submitBtn) {
                submitBtn.disabled = false;
                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.textContent = originalText;
                } else {
                    submitBtn.value = originalText;
                }
            }
        });
    }

    /**
     * Update the cart badge count in the header.
     */
    function updateCartBadge(count) {
        var cartLink = document.querySelector('.nav-icon-link[title="Shopping Cart"]');
        if (!cartLink) return;

        var badge = cartLink.querySelector('.nav-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'nav-badge';
                cartLink.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) {
            badge.textContent = '0';
        }
    }

    /**
     * Update the wishlist badge count in the header.
     */
    function updateWishlistBadge(count) {
        var wishlistLink = document.querySelector('.wishlist-link');
        if (!wishlistLink) return;

        var badge = wishlistLink.querySelector('.wishlist-badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'wishlist-badge';
                wishlistLink.appendChild(badge);
            }
            badge.textContent = count;
        } else if (badge) {
            badge.textContent = '0';
        }
    }

    // ---- Bind Forms on DOMContentLoaded ----
    document.addEventListener('DOMContentLoaded', function() {

        // Intercept add-to-cart forms
        var cartForms = document.querySelectorAll('.add-to-cart-form');
        cartForms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitFormAjax(form, function(data) {
                    if (data.cartCount !== undefined) {
                        updateCartBadge(data.cartCount);
                    }
                });
            });
        });

        // Intercept wishlist forms
        var wishlistForms = document.querySelectorAll('.wishlist-form');
        wishlistForms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitFormAjax(form, function(data) {
                    if (data.wishlistCount !== undefined) {
                        updateWishlistBadge(data.wishlistCount);
                    }
                });
            });
        });

    });

})();
