/**
 * script.js
 * ---------------------------------------------------------------------
 * Handles:
 *  1. Basic client-side validation of the donation form (a first line
 *     of defense — server-side validation in payments/initialize.php
 *     is what actually protects the app, since JS can be bypassed).
 *  2. Launching the Paystack Inline popup once the form is valid.
 *
 * Note: this file only runs on pages that include the donation form
 * (payments/donate.php). It quietly does nothing on other pages.
 * ---------------------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('donation-form');
    if (!form) return; // not on the donate page — nothing to do

    var payBtn = document.getElementById('pay-btn');

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var fullName = form.full_name.value.trim();
        var email = form.email.value.trim();
        var phone = form.phone.value.trim();
        var category = form.category.value;
        var amount = parseFloat(form.amount.value);

        var valid = true;

        if (fullName.length < 3) {
            showError('full_name', 'Please enter your full name.');
            valid = false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('email', 'Please enter a valid email address.');
            valid = false;
        }
        if (!/^[0-9+\s-]{7,15}$/.test(phone)) {
            showError('phone', 'Please enter a valid phone number.');
            valid = false;
        }
        if (!category) {
            showError('category', 'Please select a donation category.');
            valid = false;
        }
        if (isNaN(amount) || amount < 100) {
            showError('amount', 'Minimum donation amount is ₦100.');
            valid = false;
        }

        if (!valid) return;

        launchPaystack(fullName, email, phone, category, amount);
    });

    function showError(fieldName, message) {
        var field = form.querySelector('[name="' + fieldName + '"]');
        var errEl = document.createElement('div');
        errEl.className = 'error-text';
        errEl.textContent = message;
        errEl.dataset.errorFor = fieldName;
        field.insertAdjacentElement('afterend', errEl);
        field.style.borderColor = '#c0392b';
    }

    function clearErrors() {
        form.querySelectorAll('.error-text').forEach(function (el) { el.remove(); });
        form.querySelectorAll('input, select').forEach(function (el) { el.style.borderColor = ''; });
    }

    function launchPaystack(fullName, email, phone, category, amount) {
        payBtn.disabled = true;
        payBtn.textContent = 'Processing…';

        var reference = 'ASC-' + Date.now() + '-' + Math.floor(Math.random() * 100000);

        var handler = PaystackPop.setup({
            key: window.PAYSTACK_PUBLIC_KEY, // injected by donate.php from includes/config.php
            email: email,
            amount: Math.round(amount * 100), // Paystack expects kobo, not naira
            currency: 'NGN',
            ref: reference,
            metadata: {
                full_name: fullName,
                phone: phone,
                category: category,
                prayer_request: form.prayer_request.value.trim(),
                custom_fields: [
                    { display_name: 'Donation Category', variable_name: 'category', value: category }
                ]
            },
            callback: function (response) {
                // Payment popup succeeded on the client side. We now send
                // the reference to our OWN server to verify it directly
                // with Paystack's servers before trusting it.
                window.location.href = '/payments/verify.php?reference=' + encodeURIComponent(response.reference);
            },
            onClose: function () {
                payBtn.disabled = false;
                payBtn.textContent = 'Give Now';
            }
        });

        handler.openIframe();
    }
});
