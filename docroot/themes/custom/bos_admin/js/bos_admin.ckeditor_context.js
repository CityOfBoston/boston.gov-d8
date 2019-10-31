/**
 * @file
 */

/*
 Passes classes into the CKEditor iframe -allows stylesheets to affect content and theme similar to the actual site.
 */

(function ($, window, document, drupalSettings) {
  'use strict';
  $(document).ready(function () {

    var setIframeBodyClass = function (elem, style) {
      elem.addClass(style);
    };

    var iframeContext = function () {
      $.each(drupalSettings.ckeditor.cob_styles, function (field, elements) {

        let sfield = ".field--name-" + field.replace(/_/g, "-");
        sfield = $(sfield + " iframe.cke_wysiwyg_frame");

        if (sfield.length > 0) {

          $.each(elements, function (element, style) {
            let elem = sfield.contents().find(element);
            if (elem.length > 0) {
              setIframeBodyClass(elem, style);
            }
            else {
              console.log("missed");
            }
          });

        }

      });
    };

    CKEDITOR.on("instanceReady", function () {
      $.each(CKEDITOR.instances, function () {
      });
      iframeContext();
    });

  });
})(jQuery, this, this.document, drupalSettings);
