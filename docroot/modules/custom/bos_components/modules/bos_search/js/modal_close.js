(function ($, Drupal, once) {
  Drupal.behaviors.modal_close = {
    attach: function (context, settings) {
      once('modal_close', '#drupal-modal .modal-close', context).forEach(
        function (element) {
          $(element).click(function (event) {
            Drupal.dialog("#drupal-modal").close()
          });
        }
      );
    }
  };
})(jQuery, Drupal, once);
