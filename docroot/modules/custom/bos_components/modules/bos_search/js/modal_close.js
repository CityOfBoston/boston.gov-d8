(function ($, Drupal, once) {
  Drupal.behaviors.modal_close = {
    attach: function (context, settings) {
      once('modal_close', '#drupal-modal .modal-close', context).forEach(
        function (element) {
          $(element).click(function (event) {
            Drupal.dialog("#drupal-modal").close();
          });
        }
      );
      once('modal_reset', '#drupal-modal .modal-reset', context).forEach(
        function (element) {
          $(element).click(function (event) {
            $('#drupal-modal').removeClass('no-welcome');
            $('#drupal-modal [name=conversation_id]').val("");
            $('#drupal-modal .search-results-outer-wrapper').empty();
          });
        }
      );
    },
  };
})(jQuery, Drupal, once);
