/**
* @file
*/

/**
 *
 */
(function ($, window, document) {
  'use strict';
  $(document).ready(function () {
    var actions = $(".bos_admin .layout-region-node-main #edit-actions");
    if (actions.length) {
      var showActions = function () {
        $(".bos_admin .vertical-tabs__pane").each(function () {
          var blockTop = $(".bos_admin .layout-region-node-main").position().top;
          if ($(this).is(":visible")) {

            if (($(this).height() + blockTop) > $(window).height()) {
              actions.slideDown();
            }
            else {
              actions.slideUp();
            }
          }
        });
      };
      $(".bos_admin .vertical-tabs__menu-item").click(function () {
        showActions();
      });
      showActions();
    }
  });
})(jQuery, this, this.document);

/**
 * Adds a checkbox to datetime_range on public notices.
 */
(function ($, window, document) {
  'use strict';
  $(document).ready(function () {
    var actions = $(".field--name-field-public-notice-date #cbx-field-end-date");
    if (actions.length) {
      actions.click(function () {
        $(this).parent().next().show();
        $(this).parent().hide();
      });
    }
  });
})(jQuery, this, this.document);
