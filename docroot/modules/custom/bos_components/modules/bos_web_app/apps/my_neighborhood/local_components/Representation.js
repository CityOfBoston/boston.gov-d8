class Representation extends React.Component {
  render() {
    // Content for card
    let contentRepArray;
    if (this.props.councilor !== null) {
      contentRepArray = [
          {
            heading: this.props.councilor,
            content: "City Councilor, District " + this.props.district
          },
          {
            content: 
              <div>
                Learn more about <a href={"/departments/city-council"} className="mnl-link">Boston's City Council</a>.
              </div> 
          }
      ];
    } else {
      contentRepArray = [
        {
          content: <div>We're having trouble finding City Council information for that address. Please let us know at feedback@boston.gov and check our <a href={"/departments/city-council"} title={"City Councilor"} className="mnl-link">City Council page</a> page for more information.</div>
        }
      ];
    }
    let contentLiasonArray;
    if (this.props.liason_name !== null) {
      contentLiasonArray = [
        {
          heading: this.props.liason_name,
          content: "Contact: " + this.props.liason_neighborhood
        },
        {
          content: (
            <div>
              Learn more about <a href={"/departments/neighborhood-services"} className="mnl-link">Neighborhood Services</a>.
            </div>
          )
        }
      ];
    } else {
      contentLiasonArray = [
        {
          content: <div>We're having trouble finding Neighborhood Contact information for that address. Please let us know at feedback@boston.gov and check our <a href={"/departments/neighborhood-services"} title={"Neighborhood Services"} className="mnl-link">Neighborhood Services page</a> page for more information.</div>
        }
      ];
    }
    let contentPollingArray;
    if (this.props.ward == null || this.props.precinct == null) {
      contentPollingArray = [
        {
          content: <div>We're having trouble finding your voting information for that address. Please let us know at feedback@boston.gov and check our <a href={"/voting-boston"} title={"Voting in Boston"} className="mnl-link">Voting in Boston</a> page for more information.</div>
        }
      ];
    } else {
      contentPollingArray = [
        /*{
          heading: this.props.voting_location,
          content: this.props.voting_address
        },*/
        {
          heading: "Ward",
          content: this.props.ward
        },
        {
          heading: "Precinct",
          content: this.props.precinct
        },
        {
          content: <div> Find your <a href={"http://www.sec.state.ma.us/wheredoivotema//bal/myelectioninfo.aspx"} target="_blank" rel="noreferrer" className="mnl-link">polling location</a>.</div>
        }
      ];
    }
    let contentEarlyVotingArray;
    if (this.props.early_voting_dates !== null) {
        contentEarlyVotingArray = [
        {
          heading: "Location",
          content: <div>
                      <div>{this.props.early_voting_location}</div>
                      <div>{this.props.early_voting_address}</div>
                    </div>
        },
        {
          heading: "Date/Time",
          content: <div>
                      <div>Starts {this.props.early_voting_dates}</div>
                      <div>{this.props.early_voting_times}</div>
                  </div>
        },
        {
          heading: "Neighborhood",
          content: this.props.early_voting_neighborhood
        }]
        if (this.props.early_voting_notes !== null) {
          contentEarlyVotingArray.push({
            heading: "Notes",
            content: this.props.early_voting_notes
          })
        }
    } else {
        contentEarlyVotingArray = [
        {
          heading: "",
          content: <div>No early voting data available.</div>
        }]
    };
    contentEarlyVotingArray.push({
          content: (
            <div>
              Learn more about <a href={"/departments/elections/vote-early-boston"} className="mnl-link">early voting in Boston</a>.
            </div>
          )
    })
    const secDesc =
      "Ward and precinct numbers, and early voting and polling locations.";
    const cardsRepresentation = (
      <div>
        <div className="sh">
          <h2 className="sh-title">Representation</h2>
        </div>
        <div className="supporting-text">
          <p>{secDesc}</p>
        </div>
        <div className="g">
          {/* Polling Info */}
          <MnlCard
            title={"Your Polling Information"}
            image_header={
              configProps.pathImage+"voting_ballot.svg"
            }
            content_array={contentPollingArray}
          />
          {/* City Councilor */}
          <MnlCard
            title={"Your City Councilor"}
            image={
              this.props.councilor_image !== null && this.props.councilor
                ? this.props.councilor_image
                : "https://patterns.boston.gov/images/global/icons/experiential/meet-archaeologist.svg"
            }
            image_href={
              this.props.councilor !== null
                ? this.props.councilor_webpage
                : "departments/city-council"
            }
            content_array={contentRepArray}
          />

          {/* Neighborhood Liason */}
          <MnlCard
            title={"Your Neighborhood Contact"}
            image={
              this.props.liason_image !== null && this.props.liason_name
                ? this.props.liason_image
                : "https://patterns.boston.gov/images/global/icons/experiential/meet-archaeologist.svg"
            }
            image_href={
              this.props.liason_name !== null
                ? this.props.liason_webpage
                : "/departments/neighborhood-services"
            }
            content_array={contentLiasonArray}
          />

          {/* Early Voting Info */}
          {this.props.early_voting_active == true ? (
            <MnlCard
              title={"An Early Voting Location Near You"}
              image_header={
                configProps.pathImage+"voting_ballot.svg"
              }
              content_array={contentEarlyVotingArray}
            />
          ): null}

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
    let displayReps;
    if (this.props.section == "reps") {
      history.pushState(null, null, configProps.path+'?p3');
      displayReps = cardsRepresentation;
    } else if (this.props.section == null) {
      displayReps = (
        <a
          className="cd g--4 g--4--sl m-t500 cdp-l mnl-section"
          title={"Representation"}
          style={{ textAlign: "left" }}
          onClick={() => {
            this.props.displaySection("reps");
          }}
          onKeyUp={() => {
            this.props.displaySection("reps",event);
          }}
        >
          <MnlSection
            title={"Representation"}
            image_header={
              configProps.pathImage+"conversation.svg"
            }
            desc={secDesc}
          />
        </a>
      );
    } else {
      displayReps = null;
    }
    return displayReps;
  }
}
