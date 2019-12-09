class SummerResources extends React.Component {
  render() {
    // Content for card
    const contentTotSprays = [
      {
        heading: this.props.tot_name,
        content: this.props.tot_address
      },
      {
        content: (
          <div>
            Learn more about <a href={""}>tot-sprays</a>
          </div>
        )
      }
    ];
    const secDesc = "Cooling centers, pools and tot sprays near you.";
    const cardsSummer = (
      <div className="b-c">
        <div className="sh">
          <h2 className="sh-title">Summer Resources</h2>
        </div>
        <div className="supporting-text">
          <p>{secDesc}</p>
        </div>
        <div className="g">
          {/* Tot Sprays */}
          <MnlCard
            title={"Tot Spray"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/tot_spray.svg"
            }
            content_array={contentTotSprays}
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
    let displaySummer;
    if (this.props.section == "summer") {
      displaySummer = cardsSummer;
    } else if (this.props.section == null) {
      displaySummer = (
        <div
          className="cd g--4 g--4--sl m-t500  cdp-l"
          style={{ textAlign: "left" }}
          onClick={() => {
            this.props.displaySection("summer");
          }}
        >
          <MnlSection
            title={"Summer Resources"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/sun.svg"
            }
            desc={secDesc}
          />
        </div>
      );
    } else {
      displaySummer = null;
    }
    return displaySummer;
  }
}
