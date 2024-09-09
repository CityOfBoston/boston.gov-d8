(function ($, Drupal, once) {
  Drupal.behaviors.aiSearch = {
    attach: function (context, settings) {
      once('loadExample', '.bos-search-aisearchform .card', context).forEach(
        function(element){
          $(element).on("click", function(event) {
            $(".bos-search-aisearchform .search-bar").val($(element).find(".card-content").text());
            return submit_form();
          });
        }
      );
      once('aiSearch1', '.bos-search-aisearchform #search-bar-submit', context).forEach(
        function (element) {
          $(element).click(function (event) {
            return submit_form();
          });
        }
      );
      once('aiSearch2', '.bos-search-aisearchform .search-bar', context).forEach(
        function (element) {
          $(element).change(function (event) {
            return submit_form();
          });
        }
      );
      once('resetForm', '.bos-search-aisearchform', context).forEach(
        function(element){
          $(document).on("ajaxComplete", function(event, xhr, settings) {
            var responses = xhr.responseJSON;
            var mainAction;
            responses.forEach(function(element, index, array) {
              if(element.command === "insert" && typeof mainAction === "undefined"){
                mainAction = element;
              }
            });
            if (mainAction && mainAction.command === "insert" && xhr.statusText.toString() === "success") {
              // Looks like the ajax command succeeded.
              if (mainAction.selector == "#drupal-modal") {
                // This is the first-time build for the modal.
              }
              else {
                // We are appending results.
                if ($('.bos-search-aisearchform').hasClass('no-welcome')) {
                }
                else {
                  $('.bos-search-aisearchform').addClass('no-welcome');
                }
              }
            }
            if ($(".search-results-outer-wrapper").length) {
              var ai_search = $('.aienabledsearchform');
              var offsetHeight = (($('.search-results-outer-wrapper').last().offset().top) - ($('#edit-aisearchform').first().offset().top) + 10);
              ai_search.animate({
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
    $('.bos-search-aisearchform #edit-welcome').slideUp('slow', function() {
      var ai_search = $('.aienabledsearchform');
      ai_search.animate({
        scrollTop: ai_search.prop('scrollHeight')
      }, 'fast');
    });
    return $(".bos-search-aisearchform input.form-submit").mousedown();
  }
})(jQuery, Drupal, once);
