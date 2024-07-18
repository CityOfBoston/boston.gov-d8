(function ($, Drupal, once) {
  Drupal.behaviors.aiSearch = {
    attach: function (context, settings) {
      once('aiSearch', '#drupal-modal #search-bar-submit', context).forEach(
        function (element) {
          $(element).click(function (event) {
              console.log("bang");
              return $("#drupal-modal input.form-submit").mousedown();
            });
        }
      );
      once('aiSearch', '#drupal-modal .search-bar', context).forEach(
        function (element) {
          $(element).change(function (event) {
            console.log("bang");
            return $("#drupal-modal input.form-submit").mousedown();
          });
        }
      );
    }
  };
})(jQuery, Drupal, once);
