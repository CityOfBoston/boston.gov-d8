/**
 * @file
 * Retrieves/updates the city score data.
 */

var CityScore = (function (window, undefined) {
  var numberDisplay = document.querySelector('.cs--chartAmount--value');
  var numberContainer = document.querySelector('.cs--chartAmount');
  var dateContainer = document.querySelector('.brc-lu');
  var dateDisplay = document.querySelector('.date-display-single');
  var todaysScore = false;

  // Hide the date container.
  dateContainer.style.display = 'none';

  function handleResize() {
    renderTodaysScore(todaysScore);
  }

  function verfiyPageElements() {
    if (typeof dateContainer === "undefined" || dateContainer == null) {
      jQuery(".department-components").append(
        jQuery("<div>").addClass("brc-lu").css("display", "block").addClass("hidden")
      );
      dateContainer = document.querySelector('.date-display-single');
    }
    if (typeof dateDisplay === "undefined" || dateDisplay == null) {
      jQuery(".department-components").append(
        jQuery("<span>").addClass("date-display-single").prop("property", "dc:date").attr("datatype", "xsd:dateTime").addClass("hidden")
      );
      dateDisplay = document.querySelector('.date-display-single');
    }
  }

  function loadScores() {
    jQuery.ajax({
      url: "//cob-cityscore.herokuapp.com/scores/latest",
      type: 'GET',
      contentType: 'text/plain',
      dataType: "html",
      success: function (html) {
        jQuery('#scoreTable').html(html);
      }
    });
  }

  function loadTodaysScore() {
    jQuery.getJSON("//cob-cityscore.herokuapp.com/totals/latest")
      .done(function (json) {
        if (json.day) {
          todaysScore = json.day;
          renderDateUpdated(json.date_posted);
          renderTodaysScore(json.day);

          // Then start to load other scores.
          loadScores();
        }
        else {
          renderError("The day value is missing from the total response");
        }
      })
      .fail(function (jqxhr, textStatus, error) {
        var err = textStatus + ", " + error;
        console.log("Request Failed: " + err);
        dateContainer.style.display = 'block';
      });
  }

  function percentIt(num) {
    var num = num;

    if (num > 1) {
      num = "100%";
    }
    else if (num < 0) {
      num = "0%";
    }
    else {
      num = (num * 100) + "%";
    }

    return num;
  }

  function renderDateUpdated(date) {
    dateDisplay.innerHTML = date;
    dateContainer.style.display = 'block';
  }

  function renderTodaysScore(score) {
    var score = roundIt(score);
    var percentage = percentIt(score / 2);

    numberDisplay.innerHTML = score;

    if (document.body.clientWidth > 767) {
      numberContainer.style.top = 'auto';
      numberContainer.style.left = percentage;
    }
    else {
      numberContainer.style.left = '50px';
      numberContainer.style.top = percentage;
    }
  }

  function renderError(msg) {
    console.log(msg)
  }

  function roundIt(num) {
    return Math.round(num * 100) / 100;
  }

  function init() {
    jQuery.support.cors = true;
    verfiyPageElements();
    loadTodaysScore();
  }

  return {
    init: init,
    handleResize: handleResize
  };

})(window);

jQuery(document).ready(CityScore.init);
jQuery(window).resize(CityScore.handleResize);
