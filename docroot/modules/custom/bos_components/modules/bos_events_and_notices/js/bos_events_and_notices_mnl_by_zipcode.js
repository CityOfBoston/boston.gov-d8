(function (Drupal, $) {

  "use strict";

  Drupal.behaviors.mnlEventsByZip = {
    attach: function (context) {

      let eventsViewWrapperSelector = '.paragraphs-item-events-and-notices .views-element-container';
      let eventsViewTitleWrapperSelector = '.paragraphs-item-events-and-notices div.sh';

      function setEventTitleZipcodeInfo (zipcode) {

        if (!$('#mnl-events-title-zipcode-info').length) {
          $(eventsViewTitleWrapperSelector, context)
            .append(`<div id="mnl-events-title-zipcode-info" class="sh-contact"></div>`);
        }

        if (zipcode && $('#mnl-events-title-zipcode-info').length) {
          $('#mnl-events-title-zipcode-info').text(`Showing Events in the ${zipcode} zipcode`);
        }
      }

      function getZipcodeFromSamData() {

        if (localStorage.getItem('sam_zipcode')) {
          console.log("    # samZipcodeIsSet: " + localStorage.getItem('sam_zipcode'));
          return localStorage.getItem('sam_zipcode').toString();
        }

        if (JSON.parse(localStorage.getItem('sam_data'))[0].sam_address) {

          let samAddress = JSON.parse(localStorage.getItem('sam_data'))[0].sam_address.toString();
          console.log("    # samAddress: " + samAddress);

          let samZip = samAddress.substring(samAddress.length - 5);
          console.log("    # samAddressZip: " + samZip);

          return samZip || false;
        }

        return false;
      }

      function getEventsViewByZip(zipcode) {

        $.ajax({
            'url': Drupal.url('bos_events_and_notices/mnl_by_zipcode/' + zipcode),
            'type': 'GET',
            'dataType': 'html',
            'async': true,
            'success': function (data) {

              //
              // $(eventsViewTitleWrapperSelector, context)
              //   .append(`<div class="sh-contact">Showing Events in the ${zipcode} zipcode.</div>`);

              console.log(`### GET new Events for ${zipcode} ###`);
              // console.log(data);
              setEventTitleZipcodeInfo(zipcode);
              $(eventsViewWrapperSelector, context).once('mnlEventsByZipHTML-' + zipcode).html(data);
            }
          }
        );
      }

      function setUpdateEventsOnClick () {
        // Update Events when a new address is clicked in the MNL Search
        $('div.mnl').on('click', 'div.mnl-address',function( event ) {

          console.log("### Handler for .mnl-address.click() called. ###" );
          console.log("    " + $(event.target).text());

          let inputAddress = $(event.target).text();
          let inputZip = inputAddress.substring(inputAddress.length - 5);

          if (!isNaN(inputZip) && inputZip.length === 5) {
            getEventsViewByZip(inputZip);
          }
        });

      }

      function updateEventsOnInitLoad () {
        console.log('### INIT load for MNL Events by Zipcode ###');
        let samZip = getZipcodeFromSamData();
        if (samZip) {
          getEventsViewByZip(samZip);
        }
      }


      // Run the code
      updateEventsOnInitLoad();
      setUpdateEventsOnClick();
    }
  };
})
(Drupal, jQuery);
