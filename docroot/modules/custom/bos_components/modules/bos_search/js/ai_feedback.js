(function ($, Drupal, once) {
  Drupal.behaviors.ai_search_feedback = {
    attach: function (context, settings) {
      once('feedback_thumbsup', '#drupal-modal .ai-feedback-item.thumbsup', context).forEach(
        function (element) {
          $(element).click(function (event) {
            window.alert("Excellent! Thanks for your feedback.");
          });
        }
      );
      once('feedback_thumbsdown', '#drupal-modal .ai-feedback-item.thumbsdown', context).forEach(
        function (element) {
          $(element).click(function (event) {
            window.prompt("We are sorry. Please let us know how we can improve this answer.");
          });
        }
      );
    },
  };
})(jQuery, Drupal, once);
