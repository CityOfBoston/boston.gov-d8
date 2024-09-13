(function ($, Drupal, once) {
  Drupal.behaviors.ai_search_feedback = {
    attach: function (context, settings) {
      once('feedbackForm', '.ai-feedback-wrapper', context).forEach(
        function (element) {
          $(document).on("ajaxComplete", function (event, xhr, settings) {
            if (xhr.statusText.toString() === 'success') {
              var thisdialog = $('.feedback-dialog');
              if (settings.url.toString().startsWith("/form/ai-search-feedback") && thisdialog.length > 0) {
                if (thisdialog.find(".text-count-message").length > 0) {
                  var targetwidth = ($(document).width() < 480) ? 345 : 410;
                  var currentdialogwidth = parseInt(thisdialog.css("width"));
                  var widthdiff = targetwidth - currentdialogwidth;
                  var targetleft = parseInt(thisdialog.css("left")) - widthdiff;
                  thisdialog.css({ "width": targetwidth, "left" : targetleft});
                  var more = thisdialog.find('textarea[name=tell_us_more]');
                  more.on("keyup", function(element){textarea_counter(element.target, thisdialog);})
                }
                else {
                  var message = thisdialog.text().trim("\n");
                  $(".aienabledsearchform .ai-feedback-confirm").last().text(message).show();
                  $(".aienabledsearchform .ai-feedback-buttons").last().hide();
                  var searchform = $('.aienabledsearchform');
                  var results = $('.search-results-wrapper').last();
                  fb_move_div_to_top(searchform, results);
                  thisdialog.dialog("close");
                }
              }
            }
          });
        }
      );
    }
  };

  var textarea_counter = function (element, thisdialog) {
    var textbox = $(element).val();
    var count = parseInt(textbox.length);
    if (!count) {
      thisdialog.find('.text-count-message').text('200 characters allowed');
    }
    else {
      thisdialog.find('.text-count-message').text((200 - count) + ' characters remaining');
    }
  };

  var fb_move_div_to_top = function(searchform, div) {
    if ($(".search-response-wrapper").length) {
      var offsetHeight = ((div.offset().top) - (searchform.offset().top) - window.height);
      var scroll_layer = $("html, body");
      if (searchform.hasClass("aisearch-modal-form")) {
        scroll_layer = searchform;
        offsetHeight = ((div.offset().top) - (searchform.offset().top) - window.height );
      }
      scroll_layer.animate({
        scrollTop: offsetHeight,
      }, 'fast');
    }
  }

})(jQuery, Drupal, once);
