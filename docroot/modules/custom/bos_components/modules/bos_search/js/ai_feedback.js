(function ($, Drupal, once) {
  Drupal.behaviors.ai_search_feedback = {
    attach: function (context, settings) {
      once('feedbackForm', '.ai-feedback-wrapper', context).forEach(
        function (element) {
          $(document).on("ajaxComplete", function (event, xhr, settings) {
            if (xhr.statusText.toString() === 'success') {
              var dialog = $('.feedback-dialog');
              if (dialog.length > 0) {
                var more = dialog.find('textarea[name=tell_us_more]');
                more.on("keyup", function(element){
                  var textbox = $(element.target).val();
                  var count = parseInt(textbox.length);
                  if (!count) {
                    dialog.find('.text-count-message').text('200 characters allowed');
                  }
                  else {
                    dialog.find('.text-count-message').text((200 - count) + ' characters remaining');
                  }
                });
              }
            }
          });
        }
      );
    }
  };
})(jQuery, Drupal, once);
