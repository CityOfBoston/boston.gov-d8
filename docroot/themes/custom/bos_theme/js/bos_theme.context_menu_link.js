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
    var contextButtons = $("button.trigger"); //.parent().find("[class^=entitynode]");
    // Create a default type for dropdowns.
    var type = "Element";

    // Adds a tooltip to the button, changes the click funtion of the contextual button when
    // only one link and adds the node type to the links list.
    if (contextButtons.length == 1) {
      var links = contextButtons.parent().find("[class^=entitynode]");
      if (links.length == 0 ) {
        links = contextButtons.parent().find("[class^=paragraphs]");
      }
      links.parents("ul").hide();
      links.parents(".contextual").attr({"title": "Click to Edit " + type + "."});
      links.parent().parent().click(function () {
        window.location.href = links.first().find("a").prop("href");
      });
    }
    else if (contextButtons.length > 1) {
      contextButtons.each(function (key, button) {
        type = $(button).parents(".contextual-region").attr("bos_context_type");
        if (typeof type === "undefined") {type = "Element";}
        var links = $(button).parent().find("[class^=entitynode]");
        if (links.length == 0 ) {
          links = contextButtons.parent().find("[class^=paragraphs]");
        }
        links.each(function (key, listItem) {
          $(listItem).find("a").text($(listItem).find("a").text() + " " + type);
        });
        links.parents(".contextual").attr({"title": "Click for " + type + " admin options."});
      });
    }
  });
})(jQuery, this, this.document);
