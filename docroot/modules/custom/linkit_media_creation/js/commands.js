/**
 * @file commands.js
 *
 * Copy values back into linkit form.
 */

(function ($, Drupal) {
  /**
   * Submit data back to linkit form.
   */
  Drupal.AjaxCommands.prototype.returnToLinkit = function (
    ajax,
    response,
    status
  ) {
    var Formfields = {
      "edit-attributes-href": response.returnValue,
      "edit-attributes-data-entity-type": response.entityType,
      "edit-attributes-data-entity-uuid": response.entityUUID,
      "edit-attributes-data-entity-substitution": response.entitySubstitution,
      "edit-href-dirty-check": response.returnValue
    };

    var form = $("#" + response.inputId, opener.document);

    if (form.length > 0) {
      for (var key in Formfields) {
        if (Formfields.hasOwnProperty(key)) {
          var el = $('[data-drupal-selector="' + key + '"]', form);
          el.val(Formfields[key]).change();
          if (key === "edit-attributes-href") {
            el.focus();
          }
        }
      }
    }

    window.close();
  };
})(jQuery, Drupal);
