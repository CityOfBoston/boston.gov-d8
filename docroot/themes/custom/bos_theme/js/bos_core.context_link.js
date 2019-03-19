/**
 * @file
 * Extends context_edit menu functionality.
 * if there is only one menu item causes a click on the context edit button to trigger first menu item.
 *
 */

(function ($, window, document) {
  'use strict';
  $(document).ready(function () {
    var links = $("button.trigger").parent().find("[class^=entitynode]");
    var type = "Node";

    /*
      Attempts to determine what sort of node this is from the classes applied.
     */
    var findNode = function() {
      var classes = links.parents("article").attr("class").split(/\s+/);
      $(classes).each(function(key, value){
        if (value == "node-article") {
          type = "Article";
        }
      });
    };

    // Adds a tooltip to the button, changes the click funtion of the contextual button when
    // only one link and adds the node type to the links list.
    if (links.length == 1) {
      findNode();
      links.parents("ul").hide();
      links.parents(".contextual").attr({"title": "Click to Edit " + type + "."});
      links.parent().parent().click(function () {
        window.location.href = links.first().find("a").prop("href");
      });
    }
    else if (links.length > 1) {
      findNode();
      links.each(function(key, listItem) {
        $(listItem).find("a").text($(listItem).find("a").text() + " " + type);
      });
      links.parents(".contextual").attr({"title": "Click for " + type + " admin options."});
    }
  });
})(jQuery, this, this.document);