/**
 * Form Handling & Multi-Step Order Form
 * Business Website CMS
 */

document.addEventListener('DOMContentLoaded', function() {

    // ---- Multi-Step Order Form ----
    var orderForm = document.getElementById('orderForm');
    if (!orderForm) return;

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
        if (targetStep) targetStep.classList.add('active');

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
        if (currentStep === 1) {
            var serviceType = orderForm.querySelector('input[name="service_type"]:checked');
            if (!serviceType) {
                alert('Please select a category.');
                return false;
            }
        }

        if (currentStep === 4) {
            var name = orderForm.querySelector('#order_name');
            var email = orderForm.querySelector('#order_email');
            var phone = orderForm.querySelector('#order_phone');

            if (name && !name.value.trim()) {
                alert('Please enter your name.');
                name.focus();
                return false;
            }
            if (email && !email.value.trim()) {
                alert('Please enter your email.');
                email.focus();
                return false;
            }
            if (email && email.value && !isValidEmail(email.value)) {
                alert('Please enter a valid email address.');
                email.focus();
                return false;
            }
            if (phone && !phone.value.trim()) {
                alert('Please enter your phone number.');
                phone.focus();
                return false;
            }
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

    // ---- Contact Form Validation ----
    var contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            var email = contactForm.querySelector('#email');
            if (email && !isValidEmail(email.value)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                email.focus();
            }
        });
    }

    // ---- Category tabs on catalog page ----
    var categoryTabs = document.getElementById('categoryTabs');
    if (categoryTabs) {
        var tabs = categoryTabs.querySelectorAll('.category-tab');
        var sections = document.querySelectorAll('.product-category-section');

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                tabs.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');

                var category = this.getAttribute('data-category');

                sections.forEach(function(section) {
                    if (category === 'all') {
                        section.style.display = '';
                    } else {
                        section.style.display = section.getAttribute('data-category') === category ? '' : 'none';
                    }
                });
            });
        });
    }

    // ---- Utility Functions ----
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

});
