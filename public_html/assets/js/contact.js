/* JSD Construction - contact form: client validation, reCAPTCHA v3 hook and
   progressive AJAX submit. Falls back to a normal POST if JS is disabled. */
(function () {
  'use strict';

  var form = document.getElementById('contact-form');
  if (!form) return;

  var status = document.getElementById('form-status');
  var submitBtn = form.querySelector('.contact__submit');
  var recaptchaKey = form.getAttribute('data-recaptcha-key') || '';

  var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function setError(field, message) {
    field.setAttribute('aria-invalid', 'true');
    var err = field.parentNode.querySelector('.field-error');
    if (!err) {
      err = document.createElement('span');
      err.className = 'field-error';
      field.parentNode.appendChild(err);
    }
    err.textContent = message;
  }

  function clearError(field) {
    field.removeAttribute('aria-invalid');
    var err = field.parentNode.querySelector('.field-error');
    if (err) err.remove();
  }

  function validate() {
    var ok = true;
    var name = form.name;
    var email = form.email;
    var phone = form.phone;

    [name, email, phone].forEach(clearError);

    if (!name.value.trim()) { setError(name, 'Please enter your name.'); ok = false; }
    if (!emailRe.test(email.value.trim())) { setError(email, 'Please enter a valid email.'); ok = false; }
    if (phone.value.replace(/[^0-9]/g, '').length < 6) { setError(phone, 'Please enter a valid phone number.'); ok = false; }

    return ok;
  }

  function setStatus(message, type) {
    if (!status) return;
    status.textContent = message;
    status.className = 'form-status' + (type ? ' is-' + type : '');
  }

  function withRecaptcha(cb) {
    if (recaptchaKey && window.grecaptcha && window.grecaptcha.ready) {
      window.grecaptcha.ready(function () {
        window.grecaptcha.execute(recaptchaKey, { action: 'contact' }).then(function (token) {
          var input = document.getElementById('recaptcha_token');
          if (input) input.value = token;
          cb();
        }).catch(cb);
      });
    } else {
      cb();
    }
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!validate()) {
      setStatus('Please fix the highlighted fields.', 'error');
      return;
    }
    setStatus('');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Sending...'; }

    withRecaptcha(function () {
      var data = new FormData(form);
      fetch(form.action, {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json().catch(function () { return { ok: false, message: 'Unexpected server response.' }; }); })
        .then(function (res) {
          if (res.ok) {
            form.reset();
            setStatus(res.message || 'Thanks. We will be in touch shortly.', 'success');
          } else {
            setStatus(res.message || 'Something went wrong. Please try again or call us.', 'error');
          }
        })
        .catch(function () {
          setStatus('Network error. Please try again or call us on 0424 475 767.', 'error');
        })
        .finally(function () {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Send Enquiry'; }
        });
    });
  });

  // Clear field errors as the user corrects them.
  form.addEventListener('input', function (e) {
    if (e.target.matches('input, select, textarea')) clearError(e.target);
  });
})();
