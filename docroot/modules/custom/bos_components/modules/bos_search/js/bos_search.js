(function ($, Drupal, once) {
  Drupal.behaviors.aiSearch = {
    attach: function (context, settings) {
      once('loadExample', '#drupal-modal .card', context).forEach(
        function(element){
          $(element).on("click", function(event) {
            $("#drupal-modal .search-bar").val($(element).find(".card-content").text());
            return submit_form();
          });
        }
      );
      once('aiSearch1', '#drupal-modal #search-bar-submit', context).forEach(
        function (element) {
          $(element).click(function (event) {
            return submit_form();
          });
        }
      );
      once('aiSearch2', '#drupal-modal .search-bar', context).forEach(
        function (element) {
          $(element).change(function (event) {
            return submit_form();
          });
        }
      );
      once('resetForm', '#drupal-modal', context).forEach(
        function(element){
          $(document).on("ajaxComplete", function(event, xhr, settings) {
            var responses = xhr.responseJSON;
            var mainAction;
            responses.forEach(function(element, index, array) {
              if(element.command === "insert" && typeof mainAction === "undefined"){
                mainAction = element;
              }
            });
            if (mainAction.command === "insert" && xhr.statusText.toString() === "success") {
              // Looks like the ajax command succeeded.
              if (mainAction.selector == "#drupal-modal") {
                // This is the first-time build for the modal.
              }
              else {
                // We are appending results.
                if ($('#drupal-modal').hasClass('no-welcome')) {
                }
                else {
                  $('#drupal-modal').addClass('no-welcome');
                }
              }
            }
            if ($(".search-results-outer-wrapper").length) {
              var $modal = $('#drupal-modal');
              var offsetHeight = (($('.search-results-outer-wrapper').last().offset().top) - ($('#edit-aisearchform').first().offset().top) + 10);
              $modal.animate({
                scrollTop: offsetHeight,
              }, 'fast')
                .find('.search-bar').val('');
            }
          });
        }
      );

    },

  };
  var submit_form = function () {
    $('#drupal-modal #edit-welcome').slideUp('slow', function() {
      var $modal = $('#drupal-modal');
      $modal.animate({
        scrollTop: $modal.prop('scrollHeight')
      }, 'fast');
    });
    return $("#drupal-modal input.form-submit").mousedown();
  }
})(jQuery, Drupal, once);
