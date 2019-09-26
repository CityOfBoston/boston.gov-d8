/**
 * @file
 */

/**
 * Manages radion buttons on Email/Phone tab on link dialog.
 */
(function ($, window, document) {
  'use strict';
  var linkitShowFieldSet = function (obj) {
    var index = $(obj).val().toString();
    $(".cob-radio-frameset").hide();
    $(".cob-radio-".concat(index)).show();
  };

  var linkitResetForm = function (obj) {
    var index = 0;
    $(obj).parent().children().each(function (i) {
      if (obj === this) {
        index = i;
      }
    });
    $(".cob-details-wrapper").each(function (j) {
      if (j !== index) {
        $(this).find("input").find('[type="text"], [type="textarea"]').val("");
      }
    });
    if (index === 1) {
      $(".cob-radio-0").show();
      $(".cob-radio-1").hide();
      $(".cob-radio-wrapper.form-radio").first().prop("checked", true);
    }
  };

  var linkitCreateLinks = function () {
    $(".bos-boxed-content-t .form-radio")
      .off('click touchstart')
      .on('click touchstart', function () {
        linkitShowFieldSet(this);
      });
    $(".horizontal-tab-button")
      .off('click touchstart')
      .on('click touchstart', function () {
        linkitResetForm(this);
        var index = $(".horizontal-tab-button.selected").index();
        $("#edit-current").val(index);
        $(document).trigger('scroll');
        $(window).trigger('resize');
      });
    $(".horizontal-tab-button-".concat($("#edit-current").val())).find("a").click();
    window.setTimeout(function () {
      $(".horizontal-tab-button-".concat($("#edit-current").val())).find("a").click();
    }, 500);
  };

  window.setTimeout(function () {
    linkitCreateLinks();
  }, 500);

  $(".cke_button__drupallink_icon").click(function () {
    window.setTimeout(function () {
      linkitCreateLinks();
    }, 2000);
  });

  $(window).trigger('resize');

})(jQuery, this, this.document);
