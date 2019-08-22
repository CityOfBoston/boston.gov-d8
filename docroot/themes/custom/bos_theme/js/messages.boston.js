/**
 * @file
 * In-page menu functionality.
 *
 * Creates a button to enable the closing of message overlays..
 */

// Adds a click event to close messages window.
(function ($) {
  $(document).ready(function () {
    
    $(document).ajaxComplete(function () {
      $(".message--button").on("click", function () {
        $(".bos-messages").hide();
      });
    });

    $(".message--button").on("click", function () {
      $(".bos-messages").hide();
    });

  });
})(jQuery);
