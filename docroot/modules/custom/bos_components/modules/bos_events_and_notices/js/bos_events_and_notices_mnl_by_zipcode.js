(function (Drupal, $) {

  "use strict";

  Drupal.behaviors.mnlEventsByZip = {
    attach: function (context) {

      let eventsViewWrapperSelector = '.paragraphs-item-events-and-notices .views-element-container';
      let eventsViewTitleWrapperSelector = '.paragraphs-item-events-and-notices div.sh';

      function setEventTitleZipcodeInfo (zipcode) {

        if (!$('#mnl-events-title-zipcode-info').length) {
          $(eventsViewTitleWrapperSelector, context)
            .after(`
              <div class="g">
                <div id="mnl-events-title-zipcode-info" class="g--9"></div>
                <button id="local-events-toggle" class="btn btn--sm btn--100 g--3" style="align-self: flex-end;">Show Boston Events</button>
              </div>
            `);
        }

        if (zipcode && $('#mnl-events-title-zipcode-info').length) {

          if (zipcode === 'all') {
            $('#mnl-events-title-zipcode-info').text(`Showing all Events in Boston`);
          }else {
            $('#mnl-events-title-zipcode-info').text(`Showing Events for ${zipcode} zipcode`);
          }

        }
      }

      function toggleLocalEvents () {

        let toggleLocalEvents = localStorage.getItem('localized_events') ?
          localStorage.getItem('localized_events') : false;

        if (toggleLocalEvents === 'local') {
          localStorage.setItem('localized_events', 'city');
          $('#local-events-toggle').text(`Show Local Events`);
          getEventsViewByZip('all');
        }

        if (toggleLocalEvents === 'city') {
          localStorage.setItem('localized_events', 'local');
          $('#local-events-toggle').text(`Show Boston Events`);
          getEventsViewByZip(getZipcodeFromSamData());
        }

      }

      function setToggleLocalEventsOnClick () {
        // Update Events when a new address is clicked in the MNL Search
        $('div.paragraphs-item-events-and-notices').once().on('click', '#local-events-toggle', function( event ) {

          console.log("### Toggle Local Events - #local-events-toggle.click() called. ###" );
          toggleLocalEvents();

        });

      }

      function getZipcodeFromSamData() {

        if (localStorage.getItem('sam_zipcode')) {
          console.log("    # samZipcodeIsSet: " + localStorage.getItem('sam_zipcode'));
          return localStorage.getItem('sam_zipcode').toString();
        }

        if (localStorage.getItem('sam_data') && JSON.parse(localStorage.getItem('sam_data'))[0].sam_address) {

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
              $(eventsViewWrapperSelector).once('mnlEventsByZipHTML-' + zipcode).html(data);
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
            localStorage.setItem('localized_events', 'local');
          }
        });

      }

      function updateEventsOnInitLoad () {
        console.log('### INIT load for MNL Events by Zipcode ###');
        let samZip = getZipcodeFromSamData();
        if (samZip) {
          getEventsViewByZip(samZip);
          $('#local-events-toggle').once().text(`Boston Events`);
          localStorage.setItem('localized_events', 'local');
        }
      }

      function setUpdateEventsOnStorage () {
        window.onstorage = (event) => {
          console.log(event.newValue);
          if (event.key === 'sam_data' && event.newValue) {

            let samAddress = JSON.parse(event.newValue)[0].sam_address.toString();
            console.log("   STORAGE # samAddress: " + samAddress);

            let samZip = samAddress.substring(samAddress.length - 5);
            console.log("   STORAGE # samAddressZip: " + samZip);
            getEventsViewByZip(samZip);
          }
        };

      }


      // Run the code
      updateEventsOnInitLoad();
      setUpdateEventsOnClick();
      setUpdateEventsOnStorage();
      setToggleLocalEventsOnClick();
    }
  };
})
(Drupal, jQuery);
