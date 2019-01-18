var BostonEmergencyAlerts = (function () {
  var form = jQuery('#bosAlertForm');
  var email,
    button,
    phone_number,
    first_name,
    last_name,
    call,
    zip,
    language,
    text;

  function handleAlertSignup(ev) {
    ev.preventDefault();

    var isValid = validateForm();

    if (isValid) {
      var data = form.serialize();

      button.attr('disabled', true).html('loading');

      jQuery.ajax({
        url: form.attr('action'),
        method: 'post',
        data: data,
        success: handleSuccess,
        error: function (req, err) {
          button.attr('disabled', false).html('Sign Up');

          if (req.responseJSON && req.responseJSON.errors) {
            jQuery('#message').append('<div class="t--subinfo t--err m-t100">' + req.responseJSON.errors + '</div>').show();
          } else {
            jQuery('#message').append('<div class="t--subinfo t--err m-t100">There was an error. Please try again or email <a href="mailto:feedback@boston.gov">feedback@boston.gov</a>.</div>').show();
          }
        },
      });
    }
  }

  function handleSuccess(data) {
    triggerSuccess(email, data.contact.email);
    triggerSuccess(phone_number, data.contact.phone_number);
    triggerSuccess(first_name, data.contact.first_name);
    triggerSuccess(last_name, data.contact.last_name);
    triggerSuccess(zip, data.contact.zip);
    triggerSuccess(language, data.contact.language_name);
    triggerSuccess(call, data.contact.call ? 'Yes' : 'No');
    triggerSuccess(text, data.contact.text ? 'Yes' : 'No');
    form.find('#message, #button').remove();
    form.find('.t--subinfo').remove();
    jQuery('#alert_content').remove();
    jQuery('#alert_success .t--intro').show();
  }

  function validateForm() {
    var valid = true;

    resetForm();

    if (email.val() == '' && phone_number.val() == '') {
      triggerError(email, "Please enter a valid email or phone number", 'txt-f--err');
      triggerError(phone_number, "Please enter a valid phone number or email", 'txt-f--err');
      valid = false;
    }

    if (first_name.val() == '' && last_name.val() == '') {
      triggerError(first_name, "Please enter your first or last name", 'txt-f--err');
      triggerError(last_name, "Please enter your first or last name", 'txt-f--err');
      valid = false;
    }

    return valid;
  }

  function resetForm() {
    jQuery('.t--err').remove();
    jQuery('.txt-l').css({color: ''});
    jQuery('.txt-f').css({borderColor: ''});
  }

  function triggerSuccess(el, msg) {
    var parent = el.closest('.txt, .sel');

    if (msg) {
      parent.find('input, .sel-c').remove();
      parent.append('<div class="t--info" style="text-transform: none">' + msg + '</div>');

      if (parent.hasClass('cb')) {
        parent.css({'display': 'block'});
        parent.find('.cb-l').css({'margin-left': 0});
      }
    } else {
      parent.remove();
    }
  }

  function triggerError(el, msg, className) {
    var el = jQuery(el);
    var parent = el.parent();

    parent.append('<div class="t--subinfo t--err m-t100">' + msg + '</div>');
    parent.find('.txt-l').css({color: '#fb4d42'});
    el.css({borderColor: '#fb4d42'});
  }

  function start() {
    email = jQuery('#email');
    phone_number = jQuery('#phone_number');
    first_name = jQuery('#first_name');
    last_name = jQuery('#last_name');
    call = jQuery('#checkbox-call');
    text = jQuery('#checkbox-text');
    zip = jQuery('#zip_code');
    language = jQuery('#emergency-alerts-language');
    button = jQuery('#alert_submit');
    form.submit(handleAlertSignup)
  }

  return {
    start: start
  }
})();

BostonEmergencyAlerts.start();
