class Representation extends React.Component {
  render() {
    // Content for card
    const contentRepArray = [
      {
        heading: this.props.councilor,
        content: "City Councilor, District " + this.props.district
      },
      {
        content: (
          <div>
            Learn more about{" "}
            <a href={this.props.councilor_webpage}>{this.props.councilor}</a>
          </div>
        )
      }
    ];
    const contentVotingArray = [
      {
        heading: this.props.voting_location,
        content: this.props.voting_address
      },
      {
        heading: "Ward",
        content: this.props.ward
      },
      {
        heading: "Precinct",
        content: this.props.precinct
      }
    ];
    const contentLiasonArray = [
      {
        heading: this.props.liason_name,
        content: <div>&nbsp;</div>
      },
      {
        content: (
          <div>
            Learn more about <a href={""}>{this.props.liason_name}</a>
          </div>
        )
      }
    ];
    const contentEarlyVotingArray = [
      {
        heading: (
          <div>
            <div>Starts {this.props.early_voting_dates}</div>
            <div>{this.props.early_voting_times}</div>
          </div>
        ),
        content: <div>&nbsp;</div>
      },
      {
        heading: this.props.early_voting_location,
        content: this.props.early_voting_address
      },
      {
        heading: "Neighborhood",
        content: this.props.early_voting_neighborhood
      },
      {
        heading: "Notes",
        content: this.props.early_voting_notes
      },
      {
        content: (
          <div>
            Learn more about <a href={""}>voting</a>
          </div>
        )
      }
    ];
    const contentNewsletter = [
      {
        content: (
          <div>
            Sign up for your neighborhood{" "}
            <a
              href={
                "https://newsletters.boston.gov/subscribe?category=My%20Neighborhood"
              }
            >
              email newsletter
            </a>
          </div>
        )
      }
    ];
    const secDesc =
      "Ward and precinct numbers, and early voting and polling locations.";
    const cardsRepresentation = (
      <div className="b-c">
        <div className="sh">
          <h2 className="sh-title">Representation</h2>
        </div>
        <div className="supporting-text">
          <p>{secDesc}</p>
        </div>
        <div className="g">
          {/* City Councilor */}
          <MnlCard
            title={"Your City Councilor"}
            image={
              this.props.councilor_image !== null
                ? this.props.councilor_image
                : "https://patterns.boston.gov/images/global/icons/experiential/meet-archaeologist.svg"
            }
            content_array={contentRepArray}
          />

          {/* Voting Info */}
          <MnlCard
            title={"Your Voting Location"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/voting_ballot.svg"
            }
            content_array={contentVotingArray}
          />

          {/* Neighborhood Liason */}
          <MnlCard
            title={"Your Neighborhood Liason"}
            image={this.props.liason_image}
            content_array={contentLiasonArray}
          />

          {/* Early Voting Info */}
          <MnlCard
            title={"Early Voting Location"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/voting_ballot.svg"
            }
            content_array={contentEarlyVotingArray}
          />

          {/* Newsletter Sign Up */}
          <MnlCard
            title={"Newsletter Sign Up"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/email_notification.svg"
            }
            content_array={contentNewsletter}
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
    let displayReps;
    if (this.props.section == "reps") {
      displayReps = cardsRepresentation;
    } else if (this.props.section == null) {
      displayReps = (
        <div
          className="cd g--4 g--4--sl m-t500  cdp-l"
          style={{ textAlign: "left" }}
          onClick={() => {
            this.props.displaySection("reps");
          }}
        >
          <MnlSection
            title={"Representation"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/conversation.svg"
            }
            desc={secDesc}
          />
        </div>
      );
    } else {
      displayReps = null;
    }
    return displayReps;
  }
}
