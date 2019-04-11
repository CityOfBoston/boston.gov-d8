/**
 * @file
 * Extends context_edit menu functionality.
 *
 * If there is only one menu item causes a click on the context edit button to trigger first menu item.
 */

(function ($, window, document) {
  'use strict';
  $(document).ready(function () {
    // Var contextButtons are a list of context buttons on the page.
    var contextButtons = $("button.trigger");
    // Create a default type for dropdowns.
    var type = "Element";
    var links;
    var entityType;

    // Adds a tooltip to the button, changes the click funtion of the contextual button when
    // only one link and adds the node type to the links list.
    contextButtons.each(function (key, button) {
      links = $(button).parent().find("[class^=entitynode]");
      if (links.length == 0) {
        links = $(button).parent().find("[class^=paragraphs]");
        entityType = "paragraph";
      }
      else {
        entityType = "node";
      }
      type = $(button).parents(".contextual-region").attr("bos_context_type")
      if (typeof type === "undefined") {
        type = "Element";
      }
      type = type + " (" + entityType + ")";

      if (links.length != 0 ) {
        if (links.length == 1) {
          links.parents("ul").hide();
          links.parents(".contextual").attr({"title": "Click to Edit " + type + "."});
          links.parent().parent().click(function () {
            window.location.href = links.first().find("a").prop("href");
          });
        }
        else if (links.length > 1) {
          links.each(function (key, listItem) {
            $(listItem).find("a").text($(listItem).find("a").text() + " " + type);
          });
          links.parents(".contextual").attr({"title": "Click for editor options (" + entityType + ")."});
        }
      }
    });
  });
})(jQuery, this, this.document);
