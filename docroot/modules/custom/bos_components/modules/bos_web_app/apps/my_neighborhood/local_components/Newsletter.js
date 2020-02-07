class Newsletter extends React.Component {
  
  render() {
    // Content for card
    let displayNL;
    if (this.props.section == null) { 
      const secDesc = "Sign up for your neighborhood email newsletter.";
      displayNL = (
        <a
        className="cd g--4 g--4--sl m-t500  cdp-l"
        style={{ textAlign: "left" }}
        href={"https://newsletters.boston.gov/subscribe?category=My%20Neighborhood"}
        >
          <MnlSection
            title={"Newsletter Sign Up"}
            image_header={
              "https://patterns.boston.gov/assets/icons/experiential_icons/email_notification.svg"
            }
            desc={secDesc}
      
          />
        </a>
      );
    } else {
      displayNL = null;
    }
    return displayNL;
  }
}
