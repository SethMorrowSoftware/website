/**
 * Form Handling & Multi-Step Order Form
 * Business Website CMS
 */

document.addEventListener('DOMContentLoaded', function() {

    // ---- Utility Functions ----
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function showFieldError(field, message) {
        clearFieldError(field);
        field.classList.add('field-error');
        var errorEl = document.createElement('div');
        errorEl.className = 'field-error-message';
        errorEl.textContent = message;
        field.parentNode.appendChild(errorEl);
    }

    function focusFirstError(container) {
        var first = container.querySelector('.field-error');
        if (first) first.focus();
    }

    function clearFieldError(field) {
        field.classList.remove('field-error');
        var existing = field.parentNode.querySelector('.field-error-message');
        if (existing) existing.remove();
    }

    function clearAllFieldErrors(container) {
        container.querySelectorAll('.field-error').forEach(function(f) {
            f.classList.remove('field-error');
        });
        container.querySelectorAll('.field-error-message').forEach(function(e) {
            e.remove();
        });
    }

    // ---- Multi-Step Order Form ----
    var orderForm = document.getElementById('orderForm');

    if (orderForm) {
        var currentStep = 1;
        var totalSteps = 5;
        var formSteps = orderForm.querySelectorAll('.form-step');
        var stepIndicators = orderForm.querySelectorAll('.step-indicator');
        var prevBtn = document.getElementById('prevStep');
        var nextBtn = document.getElementById('nextStep');
        var submitBtn = document.getElementById('submitOrder');

        // Service type / category selection
        var serviceOptions = orderForm.querySelectorAll('.service-option');
        serviceOptions.forEach(function(option) {
            option.addEventListener('click', function() {
                serviceOptions.forEach(function(o) { o.classList.remove('selected'); });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
                updateCategoryOptions();
            });
        });

        function updateCategoryOptions() {
            var selected = orderForm.querySelector('input[name="service_type"]:checked');

            // Hide all category-specific option panels
            var allCategoryOpts = orderForm.querySelectorAll('.category-options');
            allCategoryOpts.forEach(function(panel) {
                panel.classList.add('hidden');
            });

            if (selected) {
                var categorySlug = selected.value;
                var targetPanel = document.getElementById('categoryOptions_' + categorySlug);
                if (targetPanel) {
                    targetPanel.classList.remove('hidden');
                }
            }
        }

        function showStep(step) {
            formSteps.forEach(function(s) { s.classList.remove('active'); });
            stepIndicators.forEach(function(s, i) {
                s.classList.remove('active');
                s.classList.remove('completed');
                if (i + 1 < step) s.classList.add('completed');
                if (i + 1 === step) s.classList.add('active');
            });

            var targetStep = orderForm.querySelector('.form-step[data-step="' + step + '"]');
            if (targetStep) {
                targetStep.classList.add('active');
                // Scroll to top of form on step change for mobile
                orderForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // Show/hide buttons
            if (prevBtn) prevBtn.style.display = step > 1 ? '' : 'none';
            if (nextBtn) nextBtn.style.display = step < totalSteps ? '' : 'none';
            if (submitBtn) submitBtn.style.display = step === totalSteps ? '' : 'none';

            // Build review on last step
            if (step === totalSteps) {
                buildReview();
            }

            currentStep = step;
        }

        function validateCurrentStep() {
            clearAllFieldErrors(orderForm);

            if (currentStep === 1) {
                var serviceType = orderForm.querySelector('input[name="service_type"]:checked');
                if (!serviceType) {
                    // Highlight the service options area
                    var optionsArea = orderForm.querySelector('.service-options');
                    if (optionsArea) {
                        var errorEl = document.createElement('div');
                        errorEl.className = 'field-error-message';
                        errorEl.textContent = 'Please select a category.';
                        errorEl.style.textAlign = 'center';
                        errorEl.style.marginTop = 'var(--space-md)';
                        optionsArea.parentNode.insertBefore(errorEl, optionsArea.nextSibling);
                    }
                    return false;
                }
            }

            if (currentStep === 4) {
                var name = orderForm.querySelector('#order_name');
                var email = orderForm.querySelector('#order_email');
                var phone = orderForm.querySelector('#order_phone');
                var valid = true;

                if (name && !name.value.trim()) {
                    showFieldError(name, 'Please enter your name.');
                    valid = false;
                }
                if (email && !email.value.trim()) {
                    showFieldError(email, 'Please enter your email.');
                    valid = false;
                } else if (email && email.value && !isValidEmail(email.value)) {
                    showFieldError(email, 'Please enter a valid email address.');
                    valid = false;
                }
                if (phone && !phone.value.trim()) {
                    showFieldError(phone, 'Please enter your phone number.');
                    valid = false;
                }
                if (!valid) focusFirstError(orderForm);
                return valid;
            }

            return true;
        }

        function buildReview() {
            var review = document.getElementById('orderReview');
            if (!review) return;

            var serviceType = orderForm.querySelector('input[name="service_type"]:checked');
            var name = orderForm.querySelector('#order_name');
            var email = orderForm.querySelector('#order_email');
            var phone = orderForm.querySelector('#order_phone');
            var address = orderForm.querySelector('#delivery_address');
            var date = orderForm.querySelector('#preferred_date');

            var html = '<table style="width:100%; border-collapse:collapse;">';

            if (serviceType) {
                // Use the label text from the selected option
                var selectedOption = serviceType.closest('.service-option');
                var categoryLabel = selectedOption ? selectedOption.querySelector('h4').textContent : serviceType.value;
                html += reviewRow('Category', categoryLabel);
            }

            // Category-specific details
            if (serviceType) {
                var categorySlug = serviceType.value;
                var productSelect = orderForm.querySelector('.category-product-select[data-category="' + categorySlug + '"]');
                var quantityInput = orderForm.querySelector('.category-quantity-input[data-category="' + categorySlug + '"]');
                if (productSelect && productSelect.value) {
                    html += reviewRow('Product', productSelect.options[productSelect.selectedIndex].text);
                }
                if (quantityInput && quantityInput.value) {
                    html += reviewRow('Quantity / Details', quantityInput.value);
                }
            }

            // General description
            var descField = orderForm.querySelector('[name="product_details[description]"]');
            if (descField && descField.value) {
                html += reviewRow('Description', descField.value);
            }

            if (address && address.value) html += reviewRow('Delivery Address', address.value);
            if (date && date.value) html += reviewRow('Preferred Date', date.value);
            if (name && name.value) html += reviewRow('Name', name.value);
            if (email && email.value) html += reviewRow('Email', email.value);
            if (phone && phone.value) html += reviewRow('Phone', phone.value);

            html += '</table>';
            review.innerHTML = html;
        }

        function reviewRow(label, value) {
            return '<tr style="border-bottom: 1px solid var(--color-gray-200);">' +
                   '<td style="padding: 10px 0; font-weight: 600; color: var(--color-dark); width: 40%;">' + escapeHtml(label) + '</td>' +
                   '<td style="padding: 10px 0; color: var(--color-gray-600);">' + escapeHtml(value) + '</td></tr>';
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (validateCurrentStep()) {
                    showStep(currentStep + 1);
                }
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                showStep(currentStep - 1);
            });
        }
    } // end orderForm block

    // ---- Contact Form Validation ----
    var contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            clearAllFieldErrors(contactForm);
            var emailField = contactForm.querySelector('#email');
            if (emailField && !isValidEmail(emailField.value)) {
                e.preventDefault();
                showFieldError(emailField, 'Please enter a valid email address.');
                emailField.focus();
            }
        });

        // Clear errors on input
        contactForm.querySelectorAll('.form-control').forEach(function(field) {
            field.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    }

    // ---- Checkout Form Validation ----
    var checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            clearAllFieldErrors(checkoutForm);
            var nameField = checkoutForm.querySelector('#checkout_name');
            var emailField = checkoutForm.querySelector('#checkout_email');
            var valid = true;

            if (nameField && !nameField.value.trim()) {
                showFieldError(nameField, 'Please enter your name.');
                valid = false;
            }
            if (emailField && !isValidEmail(emailField.value)) {
                showFieldError(emailField, 'Please enter a valid email address.');
                valid = false;
            }
            if (!valid) {
                e.preventDefault();
                focusFirstError(checkoutForm);
            }
        });

        checkoutForm.querySelectorAll('.form-control').forEach(function(field) {
            field.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    }

});
