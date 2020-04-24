/**
 * @file
 * Script to run Google translate service.
 */

console.log('it works');

jQuery(document).ready( function () {

  jQuery('.translate-link').click(function(e) {
    e.preventDefault();
    jQuery('.translate-dd').toggle('slow');
  });
});

API_KEY = "AIzaSyAJpLVlgN7wFOiPsSPU-RrJjjqw0iq_nB4"

// and replace with a translated version.
jQuery(".translate-dd-link").click(function () {
  'use strict'
  let title = jQuery( this ).attr( "data-lang" );
  console.log(title);
  // construct url to translate content in #translateText to the language selected by #targetLanguage
  let url = "https://translation.googleapis.com/language/translate/v2";
  url += "/?key=" + API_KEY;
  url += "&target=" + title; // https://stackoverflow.com/questions/50719010/google-translate-api-translate-page-using-js
  url += "&q=" + encodeURI(document.getElementById("page").innerHTML); // encodeURI converts strings to url-safe text

  // POST to google translate api
  let request = new XMLHttpRequest();
  request.open('POST', url);
  //request.setRequestHeader ('Content-type', 'application/json; charset=utf-8');

  // after the request is complete, extract translated text and replace in the web page
  request.onload = function () {
    let data = JSON.parse(this.response);

    if (request.status >= 200 && request.status < 400) {
      //jQuery.each(this.response, function(){
        document.getElementById("page").innerHTML = data.data.translations[0].translatedText;
      //});
    }
  }

  request.send();
});
