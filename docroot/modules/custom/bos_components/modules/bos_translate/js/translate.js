/**
 * @file
 * Script to run Google translate service.
 */

jQuery(document).ready( function () {

  'use strict';
  jQuery('.translate-link').click( function (e) {
    e.preventDefault();
    jQuery('.translate-dd').toggle('slow');
  });
});

function GoogleTranslateLink() {
  if (window.location.href.indexOf("translate.google.com") == -1) {
    var url = "https://translate.google.com/translate?js=y&prev=_t&hl=en&ie=UTF-8&layout=1&eotf=1&sl=en&tl=es&u=";
    url += escape(window.location.href);
    window.location.href = url;
  }
  else {
    //do nothing
  }
}

