class CitySpaces extends React.Component {
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
            More information on <a href={""}>libraries</a>
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
        heading: "Hours",
        content: this.props.comm_hours
      },
      {
        heading: "Summer Hours",
        content: this.props.comm_summer_hours
      },
      {
        content: (
          <div>
            More information on <a href={""}>community centers</a>
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
        heading: "Ownership",
        content: this.props.park_ownership
      },
      {
        heading: "Type",
        content: this.props.park_type
      },
      {
        content: (
          <div>
            More information on <a href={""}>parks</a>
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
            title={"Library Branch"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/open_book.svg"
            }
            content_array={contentLibArray}
          />

          {/* Community Center */}
          <MnlCard
            title={"Community Center"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/family_house.svg"
            }
            content_array={contentCommCenters}
          />

          {/* Park Info */}
          <MnlCard
            title={"Park"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/trees.svg"
            }
            content_array={contentPark}
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
