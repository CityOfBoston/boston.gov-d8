class CitySpaces extends React.Component {
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
          content: (this.props.hist_year == 0 || this.props.hist_year == null ? "No Year Avaialble" : this.props.hist_year)
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
    const contentLibArray = [
      {
        heading: this.props.library_branch,
        content: this.props.library_address
      },
      {
        content: (
          <div>
            Learn more about the City's <a href={"https://www.bpl.org/"} target="_blank" rel="noreferrer">library system</a>
          </div>
        )
      }
    ];
    const contentCommCenters = [
      {
        heading: this.props.comm_center,
        content: this.props.comm_address
      },
      {
        content: (
          <div>
            Learn more about the City's <a href={"departments/boston-centers-youth-families"}>community centers</a>
          </div>
        )
      }
    ];
    const contentPark = [
      {
        heading: this.props.park_name,
        content: this.props.park_district
      },
      {
        heading: "Park Ownership",
        content: this.props.park_ownership
      },
      {
        heading: "Park Type",
        content: this.props.park_type
      },
      {
        content: (
          <div>
            Lern more about the City's <a href={"departments/parks-and-recreation"}>parks</a>
          </div>
        )
      }
    ];
    const secDesc =
      "Libraries, community centers, parks and playgrounds near you.";
    const cardsCitySpaces = (
      <div className="b-c">
        <div className="sh">
          <h2 className="sh-title">City Spaces</h2>
        </div>
        <div className="supporting-text">
          <p>{secDesc}</p>
        </div>
        <div className="g">
          {/* Library Branch */}
          <MnlCard
            title={"A Library Branch Near You"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/open_book.svg"
            }
            content_array={contentLibArray}
          />

          {/* Community Center */}
          <MnlCard
            title={"A Community Center Near You"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/family_house.svg"
            }
            content_array={contentCommCenters}
          />

          {/* Park Info */}
          <MnlCard
            title={"A Park Near You"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/trees.svg"
            }
            content_array={contentPark}
          />
          {/* Historical Info */}
          <MnlCard
            title={"Districts"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/location.svg"
            }
            content_array={this.checkHistInfo()}
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
    let displayCitySpaces;
    if (this.props.section == "city-spaces") {
      displayCitySpaces = cardsCitySpaces;
    } else if (this.props.section == null) {
      displayCitySpaces = (
        <div
          className="cd g--4 g--4--sl m-t500  cdp-l"
          style={{ textAlign: "left" }}
          onClick={() => {
            this.props.displaySection("city-spaces");
          }}
        >
          <MnlSection
            title={"City Spaces"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/playground.svg"
            }
            desc={secDesc}
          />
        </div>
      );
    } else {
      displayCitySpaces = null;
    }
    return displayCitySpaces;
  }
}
