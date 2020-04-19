class CityServices extends React.Component {

   render() {
    // Content for Trash and Recycling Card
    let contentRecollect = [];
    if(this.props.recollect_date !== null) {
      const dateProp= this.props.recollect_date;
      const dateArray = dateProp.split('-');
      const dateFormat = new Date(dateArray[1] + '/' + dateArray[2] + '/' + dateArray[0]);
      const dateDays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
      const dateMonths = ["January","February","March","April","May","June","July","August","September","October","November","December"];
      let recollectFlags = this.props.recollect_services; 
        Object.keys(recollectFlags).map(key => 
            contentRecollect.push({
              heading: "Your next "+recollectFlags[key].name+" day is:",
              content: dateDays[dateFormat.getDay()] +', '+ dateMonths[dateFormat.getMonth()] + ' ' + dateFormat.getDate()
              //content: dateFormatted.getDay() +', '+ dateFormatted.getMonth() +', '+ dateFormatted.getDate()
            })
        )
      contentRecollect.push({
        heading: "NOTE:",
        content: "The trash and recycling schedule might change during holidays and weather events."
      });
    } else {
      contentRecollect.push({
        content: (
          <div>We're having trouble finding Trash Collection information for that address. This may be because your trash is picked up by a commercial vendor. If that’s not right or you have additional questions, please reach out to our Public Works Department at publicworks@boston.gov or 617-635-4900.</div>
          )
      });
    }

    contentRecollect.push({
      content: (
        <div>
          Learn more about <a href={"/trash-and-recycling"}>trash and recycling</a>.
        </div>
      )
    })
    
    // Content for Street Sweeping
    const contentStreetSweeping = [
      {
        content: (
           <div>
             The City cleans streets throughout the year, learn about our <a href={"/departments/public-works/street-sweeping-city"}>street sweeping program</a> and find the <a href={"https://www.cityofboston.gov/publicworks/sweeping/"} target="_blank" rel="noreferrer">street sweeping schedule</a> for your street.
          </div>
        )
      }
    ];

    // Content for Helpful Links
    const contentLinks = [
      {
        content: (
          <div>
            <div className="no-heading">
              <a href={"/trash-and-recycling#trash-day-app"}>Your trash day information</a>
            </div>
            <div className="no-heading">
              <a href={"/departments/assessing"}>Assessing Online</a>
            </div>
            <div className="no-heading">
               <a href={"/departments/public-works/street-sweeping-city"}>Street sweeping schedule</a>
            </div>
          </div>
        )
      }
    ];
    const secDesc = "Trash / recycling pick up and street sweeping.";
    const cardsCityServices = (
      <div>
        <div className="sh">
          <h2 className="sh-title">City Services</h2>
        </div>
        <div className="supporting-text">
          <p>{secDesc}</p>
        </div>
        <div className="g">
          {/* Trash and recycling info */}
          <MnlCard
            title={"Trash and Recycling"}
            image_header={
              configProps.pathImage+"trash_truck.svg"
            }
            content_array={contentRecollect}
          />
        {/* Street Sweeping */}
          <MnlCard
            title={"Street Sweeping"}
            image_header={
              configProps.pathImage+"street_sweeper.svg"
            }
            content_array={contentStreetSweeping}
          />
          {/* Helpful Link info */}
          {/*<MnlCard
            title={"Helpful Links"}
            image_header={
              configProps.pathImage+"info.svg"
            }
            content_array={contentLinks}
          />*/}
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
    let displayCityServices;
    if (this.props.section == "city-services") {
      history.pushState(null, null, configProps.path+'?p3');
      displayCityServices = cardsCityServices;
    } else if (this.props.section == null) {
      displayCityServices = (
        <div
          className="cd g--4 g--4--sl m-t500  cdp-l"
          style={{ textAlign: "left" }}
          onClick={() => {
            this.props.displaySection("city-services");
          }}
        >
          <MnlSection
            title={"City Services"}
            image_header={
              configProps.pathImage+"street_sweeper.svg"
            }
            desc={secDesc}
          />
        </div>
      );
    } else {
      displayCityServices = null;
    }
    return displayCityServices;
  }
}
