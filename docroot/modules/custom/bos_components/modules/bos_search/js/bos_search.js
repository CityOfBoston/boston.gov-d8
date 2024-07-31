(function ($, Drupal, once) {
  Drupal.behaviors.aiSearch = {
    attach: function (context, settings) {
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
            if(xhr.responseJSON[1].command === "insert" && xhr.statusText.toString() === "success") {
              if ($(event.delegateTarget.activeElement).hasClass("aisearch-modal-form") && ! $('#drupal-modal').hasClass('no-welcome')) {
                $('#drupal-modal').addClass('no-welcome');
              }
            }
            var $modal = $('#drupal-modal');
            var offsetHeight = (($('.search-results-outer-wrapper').last().offset().top) - ($("#edit-aisearchform").first().offset().top) + 10);
            $modal.animate({
                scrollTop: offsetHeight
              }, 'fast')
              .find(".search-bar").val("");
          });
        }
      );
      once('loadExample', '#drupal-modal .card', context).forEach(
        function(element){
          $(element).on("click", function(event) {
            $("#drupal-modal .search-bar").val($(element).find(".card-content").text());
            return submit_form();
          });
        }
      );

    },

  };
  var submit_form = function () {
    var $modal = $('#drupal-modal');
    $modal.animate({
      scrollTop: $modal.prop('scrollHeight')
    }, 'fast');
    return $("#drupal-modal input.form-submit").mousedown();
  }
})(jQuery, Drupal, once);
