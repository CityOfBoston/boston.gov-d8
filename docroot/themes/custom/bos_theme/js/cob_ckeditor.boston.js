/**
 * @file
 * CKeditor  Functionality.
 *
 * Changes the height of an image via media query API.
 */

'use strict';

const x = window.matchMedia("(max-width: 1100px)");
function screenSize(x) {
  let changeHeight = document.querySelectorAll(".cob-ckeditor .cob-ckeditor-bg");

  if (x.matches) { // If media query matches

    document.body.style.backgroundColor = "yellow";
    for( let i=0; i<changeHeight.length; i++) {
      let show = changeHeight[i].getAttribute("data-cob-ckeditor");
      changeHeight[i].style.height = show - 100 + "px";
      changeHeight[i].style.minHeight = show - 100 + "px";
      //console.log(changeHeight[i].style.height = show - 100);
    }

  } else {
    x = window.matchMedia("(min-width: 1101px)");
    if (x.matches) {
      for( let i=0; i<changeHeight.length; i++) {
        let show = changeHeight[i].getAttribute("data-cob-ckeditor");
        changeHeight[i].style.height = show + "px";
        changeHeight[i].style.minHeight = show + "px";
      }
    }
  }
}

//screenSize(x); // Call listener function at run time
x.addEventListener("change", () => {
  screenSize(x);
});


