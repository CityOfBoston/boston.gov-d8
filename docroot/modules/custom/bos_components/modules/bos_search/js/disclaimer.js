(function ($, Drupal) {
  Drupal.behaviors.searchDisclaimer = {
    attach: function (context, settings) {

      const element = $('.aienabledsearchform', context);

      // Only display the disclaimer once per form display
      if (element.length > 0 && !element.attr('data-once-searchDisclaimer')) {
        element.attr('data-once-searchDisclaimer', true);

        // Callback to create the disclaimer.
        if (settings.disclaimerForm.triggerDisclaimerModal) {
          Drupal.ajax({
            url: settings.disclaimerForm.openModal,
          }).execute();

          $(document).on("ajaxComplete", function(event, xhr, settings) {
            // Fires when the disclaimer form is returned by ajax
            $('.aienableddisclaimerform .btn-submit').click(function (event) {
              event.preventDefault();
              Drupal.dialog("#drupal-modal").close();
            });
          });

        }

        // $.fn.openDisclaimerModal = function () {
          // Define the behavior to open your modal here.
          // For example, using Bootstrap modal:
          // $('#disclaimerModal').modal('show');
        // };

      }
    }
  };
})(jQuery, Drupal);
