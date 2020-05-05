class WinterResources extends React.Component {
  render() {
    // Content for cards
    const contentSnowEmergencyParking = [
      {
        heading: "Parking Lot Name",
        content: this.props.snow_parking_lots_name
      },
      {
        heading: "Parking Lot Address",
        content: this.props.snow_parking_lots_address
      },
      {
        heading: "Parking Lot Fee",
        content: this.props.snow_parking_lots_fee
      },
      {
        heading: "Parking Lot Comments",
        content: this.props.snow_parking_lots_comments
      }
    ]
    const contentSnowRoutes = [
      {
        heading: "Closest Route",
        content: this.props.snow_routes
      },
      {
        content: (
          <div className="no-heading">
            There may be other snow routes in your area, <a href={"/departments/311/snow-emergency-parking"} className="mnl-link"> check here for all snow emergency parking restrictions</a>.
          </div>
        )
      }
    ];
    const secDesc = "Find you where you can / can’t park and how to prepare for snow emergencies.";
    const cardsWinter = (
      <div>
        <div className="sh">
          <h2 className="sh-title">Winter Resources</h2>
        </div>
        <div className="supporting-text">
          <p>We will ticket and tow your car if you park on a posted snow emergency artery during a declared snow emergency. If you can't find a spot, some lots and garages offer discounted parking to vehicles with Boston resident parking stickers.</p>
        </div>
        <div className="g">
          {/* Emergency Parking */}
          <MnlCard
            title={"Snow Emergency Parking"}
            image_header={
              configProps.pathImage+"parking.svg"
            }
            content_array={contentSnowEmergencyParking}
          />
          {/* Snow Routes */}
          <MnlCard
            title={"A Snow Route Near You"}
            image_header={
              configProps.pathImage+"plan.svg"
            }
            content_array={contentSnowRoutes}
          />
        </div>
        <button className="t--upper t--sans"
          onClick={() => {
            this.props.displaySection(null);
          }}
        >
          Back to results
        </button>
      </div>
    );

    let displayWinter;
    if (this.props.section == "winter") {
      history.pushState(null, null, configProps.path+'?p3');
      displayWinter = cardsWinter;
    } else if (this.props.section == null) {
      displayWinter = (
        <a
          className="cd g--4 g--4--sl m-t500 cdp-l mnl-section"
          title={"Winter Resources"}
          style={{ textAlign: "left" }}
          onClick={() => {
            this.props.displaySection("winter");
          }}
        >
          <MnlSection
            title={"Winter Resources"}
            image_header={
              configProps.pathImage+"snow_1.svg"
            }
            desc={secDesc}
          />
        </a>
      );
    } else {
      displayWinter = null;
    }
    return displayWinter;
  }
}
