class PropertyInformation extends React.Component {
  checkHistInfo = event => {
    let contentDistricts = null;
    if (this.props.hist_name !== null) {
      contentDistricts = [
        {
          heading: "Historic Name",
          content: this.props.hist_name
        },
        {
          heading: "District",
          content: this.props.hist_place_name
        },
        {
          heading: "Year",
          content: this.props.hist_year
        }
      ];
    } else {
      contentDistricts = [
        {
          heading: "Historic District",
          content: <div>This address is not in a historic district.</div>
        }
      ];
    }
    contentDistricts.push({
      heading: "Zoning District",
      content: "No data available"
    });
    return contentDistricts;
  };
  render() {
    // Content for card
    const secDesc = "Zoning and historic districts, property owner and type.";
    const cardsProperty = (
      <div className="b-c">
        <div className="sh">
          <h2 className="sh-title">Property Information</h2>
        </div>
        <div className="supporting-text">
          <p>{secDesc}</p>
        </div>
        <div className="g">
          {/* Districts */}
          <MnlCard
            title={"Districts"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/location.svg"
            }
            content_array={this.checkHistInfo()}
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
    let displayProperty;
    if (this.props.section == "property") {
      displayProperty = cardsProperty;
    } else if (this.props.section == null) {
      displayProperty = (
        <div
          className="cd g--4 g--4--sl m-t500  cdp-l"
          style={{ textAlign: "left" }}
          onClick={() => {
            this.props.displaySection("property");
          }}
        >
          <MnlSection
            title={"Proeprty Information"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/buildings.svg"
            }
            desc={secDesc}
          />
        </div>
      );
    } else {
      displayProperty = null;
    }
    return displayProperty;
  }
}
