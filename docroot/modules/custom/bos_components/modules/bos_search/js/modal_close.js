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
      once('modal_reset', '#drupal-modal .ai-form-reset', context).forEach(
        function (element) {
          $(element).click(function (event) {
            // $('.bos-search-aisearchform').removeClass('no-welcome');
            var searchform = $('.aienabledsearchform');
            searchform.find('[name=conversation_id]').val("");
            searchform.find('.search-results-outer-wrapper').empty();
          });
        }
      );
      once('resetAi', '.aienabledsearchform', context).forEach(
        function(element){
          $(document).on("ajaxComplete", function(event, xhr, settings) {
            var searchform = $('.aienabledsearchform');
            if (xhr.statusText.toString() === 'success' &&
               drupalSettings.has_results) {
              if (!searchform.hasClass('has-results')) {
                searchform
                  .addClass('has-results')
                  // .find(".modal-close-wrapper")
                  //   .css({"top": searchform.find(".modal-close-wrapper").offset().top + "px"});
              }
            }
          });
        }
      );

    },
  };
})(jQuery, Drupal, once);
