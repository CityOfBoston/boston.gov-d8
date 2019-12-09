class WinterResources extends React.Component {
  render() {
    // Content for card
    const contentSnowRoutes = [
      {
        content: (
          <div className="no-heading">
            Lorem Ipsum is simply dummy text of the printing and typesetting
            industry. Lorem Ipsum has been the industry's standard dummy text
            ever since the 1500s, when an unknown printer took a galley of type
            and scrambled it to make a type specimen book.
          </div>
        )
      },
      {
        heading: "Closest Route",
        content: this.props.snow_routes
      }
    ];
    const secDesc = "Find out where to park and prepare for snow emergencies.";
    const cardsWinter = (
      <div className="b-c">
        <div className="sh">
          <h2 className="sh-title">Winter Resources</h2>
        </div>
        <div className="supporting-text">
          <p>{secDesc}</p>
        </div>
        <div className="g">
          {/* Snow Routes */}
          <MnlCard
            title={"Snow Routes"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/plan.svg"
            }
            content_array={contentSnowRoutes}
          />
        </div>
        <button
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
      displayWinter = cardsWinter;
    } else if (this.props.section == null) {
      displayWinter = (
        <div
          className="cd g--4 g--4--sl m-t500  cdp-l"
          style={{ textAlign: "left" }}
          onClick={() => {
            this.props.displaySection("winter");
          }}
        >
          <MnlSection
            title={"Winter Resources"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/snow_1.svg"
            }
            desc={secDesc}
          />
        </div>
      );
    } else {
      displayWinter = null;
    }
    return displayWinter;
  }
}
