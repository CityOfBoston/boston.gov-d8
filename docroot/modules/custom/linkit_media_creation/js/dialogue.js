/**
 * @file dialogue.js
 */

(function ($, Drupal) {
  "use strict";

  /**
   * Drupal behavior to handle url input integration.
   */
  Drupal.behaviors.linkitMediaUrlInput = {
    attach: function (context, settings) {
      $(".linkit-media-creation-url-input", context)
        .not(".linkit-media-creation-url-input-processed")
        .addClass("linkit-media-creation-url-input-processed")
        .each(linkitMediaInput.processUrlInput);
    }
  };

  /**
   * Global container for integration helpers.
   */
  var linkitMediaInput = (window.linkitMediaInput = window.linkitMediaInput || {
    /**
     * Processes an url input.
     */
    processUrlInput: function (i, el) {
      var button = linkitMediaInput.createUrlButton(el.id);
      el.parentNode.insertBefore(button, el);
    },

    /**
     * Creates an url input button.
     */
    createUrlButton: function (inputId) {
      var button = document.createElement("a");
      button.href = "#";
      button.className = "linkit-media-creation-url-button";
      button.innerHTML = "<span>" + Drupal.t("Create new document") + "</span>";
      button.onclick = linkitMediaInput.urlButtonClick;
      button.InputId =
        inputId || "inkit-media-creation-" + (Math.random() + "").substr(2);
      button.InputType = "link";
      return button;
    },

    /**
     * Click event of an url button.
     */
    urlButtonClick: function (e) {
      var url = Drupal.url("admin/linkit-media-creation/dialogue");
      url += (url.indexOf("?") === -1 ? "?" : "&") + "inputId=" + this.InputId;
      $("#" + this.InputId).focus();
      window.open(
        url,
        "",
        "width=" +
        Math.min(750, parseInt(screen.availWidth * 0.8, 10)) +
        ",height=" +
        Math.min(400, parseInt(screen.availHeight * 0.8, 10)) +
        ",resizable=1"
      );
      return false;
    }
  });
})(jQuery, Drupal);
