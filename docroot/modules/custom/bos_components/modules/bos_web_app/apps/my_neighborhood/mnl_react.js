// Import not needed because React, ReactDOM, and local/global compontents are loaded by *.libraries.yml
class MNL extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      error: null,
      isLoading: false,
      isLoadingRecollect: null,
      season: configProps.season,
      earlyVoting: configProps.earlyVoting,
      section: null,
      sam_id: null,
      itemsLookup: [],
      itemsDisplay: null,
      itemsRecollect: [],
      currentKeywords: null,
      submittedAddress: null,
      submittedKeywords: null,
      searchColor: null
    };
  }

  componentDidMount(){
    let inputHeight = jQuery("#web-app input").height();
    let inputWidth = jQuery("#web-app input").width() - 75;
    jQuery(".resize").css('height', inputHeight + 'px');
    jQuery(".resize").css('width', inputWidth + 'px');
    jQuery("#web-app input").css('height', inputHeight + 'px');
    
    let that = this;
    window.addEventListener('popstate', function (event) {
      if (that.state.submittedAddress !== null && that.state.section !== null) {
        that.displaySection(null);
      } 
      else if (that.state.submittedAddress !== null && that.state.section == null ) {
        that.setDefaults();
      }
    }, false);

    // Check for local storage SAM data;
    this.setCheckLocalStorage();
  }

  setDefaults = () => {
      this.setState({
        error: null,
        isLoading: false,
        isLoadingRecollect: null,
        itemsLookup: [],
        itemsDisplay: null,
        itemsRecollect: [],
        currentKeywords: "",
        submittedAddress: null,
        submittedKeywords: null,
      });
      history.pushState(null, null, configProps.path)
  }

  setCheckLocalStorage = (sam_id, sam_address, section) => {
    if(localStorage.getItem("sam_data")){
      let localSAM = JSON.parse(localStorage.getItem("sam_data"));
      this.displayAddress(localSAM[0].sam_id,localSAM[0].sam_address,localSAM[0].section);
      if(localSAM[0].section !== null){
        this.setState({section:localSAM[0].section});
      }
    }
    else {
      let samId = (sam_id ? sam_id : null);
      let samAddress = (sam_address ? sam_address : null);
      let cardSection = (section ? section : null);
      let mnl = [{
        "sam_id": samId,
        "sam_address": samAddress,
        "section": cardSection
      }];
      localStorage.setItem("sam_data", JSON.stringify(mnl));
    }
  }

  scaleInputText = op => {
      jQuery(".resize").textfill({
      maxFontPixels: 75,
      success: function() {
        let fontReSize = jQuery(".resize span").css('fontSize');
        jQuery("#web-app input").css('font-size', fontReSize);
      }
    }) 
  }

  handleKeywordChange = event => {
    event.preventDefault();
    let inputChars = event.target.value.length;
    let typingTimer;                
    let doneTypingInterval = 3000;  
    this.setState({
      currentKeywords: event.target.value,
      submittedKeywords: null,
      searchColor: null
    });

    if (inputChars >= 5 || event.keyCode === 13) {
      this.setState({
        isLoading: true,
        submittedKeywords: true,
        submittedAddress: null
      });
      clearTimeout(typingTimer);
      typingTimer = setTimeout(this.lookupAddress(), doneTypingInterval);
    }
    this.scaleInputText();
  };

  handleKeywordSubmit = event => {
    event.preventDefault();
    this.setState({
      isLoading: true,
      submittedKeywords: true,
      submittedAddress: null
    });
    this.lookupAddress();
    this.scaleInputText();
  };

  lookupRecollect = event => {
    let address = {"address":this.state.submittedAddress};
    fetch(
      "rest/recollect",
    {
      method: "POST",
      body: JSON.stringify(address),
    })
      .then(res => res.json())
      .then(
        result => {
          if (result.response){
            this.setState({
              isLoadingRecollect: false,
              itemsRecollect: result.response.next_event,
            });
          } else {
            this.setState({
              itemsRecollect: null
            });
          }
        },
        // Note: it's important to handle errors here
        // instead of a catch() block so that we don't swallow
        // exceptions from actual bugs in components.
        error => {
          this.setState({
            itemsRecollect: "error",
            error
          });
        }
      );

  };

  lookupAddress = event => {
    let addressQuery = this.state.currentKeywords;
    let paramsQuery = {
      condition: "[field_sam_address][operator]=CONTAINS",
      value: "[field_sam_address][value]=" + addressQuery,
      fields: "[node--neighborhood_lookup]=field_sam_address,field_sam_id",
      limit: "5"
    };
    fetch(
      "/jsonapi/node/neighborhood_lookup?filter" +
        paramsQuery.condition +
        "&filter" +
        paramsQuery.value +
        "&fields" +
        paramsQuery.fields +
        "&page[limit]=" + paramsQuery.limit
    )
      .then(res => res.json())
      .then(
        result => {
          if (result.data.length > 0)
            this.setState({
              isLoading: false,
              itemsLookup: result.data
            });
          else
            this.setState({
              isLoading: false,
              itemsLookup: []
            });
        },
        // Note: it's important to handle errors here
        // instead of a catch() block so that we don't swallow
        // exceptions from actual bugs in components.
        error => {
          this.setState({
            isLoading: false,
            error
          });
        }
      );
  };

  displayAddress = (sam_id, sam_address, section) => {
    //let localSection = (section) ? section : null;
    this.setState({
      isLoading: true,
      isLoadingRecollect: true,
      searchColor: "#288BE4",
      section: null,
      sam_id: sam_id,
      currentKeywords: sam_address
    });
    let paramsSamGet = {
      value: "[field_sam_id][value]=" + sam_id,
      fields:
        "[node--neighborhood_lookup]=field_sam_id,field_sam_neighborhood_data,field_sam_address"
    };
    let jsonData = "none";
    fetch(
      "/jsonapi/node/neighborhood_lookup?filter" +
        paramsSamGet.value +
        "&fields" +
        paramsSamGet.fields
    )
      .then(res => res.json())
      .then(
        result => {
          if (result.data[0]){
            jsonData = JSON.parse(result.data[0].attributes.field_sam_neighborhood_data);
            let newState = { ...this.state.itemsDisplay };
            newState.data = jsonData;
            this.setState({
              isLoading: false,
              submittedAddress: result.data[0].attributes.field_sam_address,
              submittedKeywords: false,
              itemsLookup: [],
              itemsDisplay: newState.data
            });
            this.lookupRecollect();
            this.scaleInputText();
            localStorage.removeItem("sam_data");
            this.setCheckLocalStorage(this.state.sam_id,this.state.submittedAddress, section);
          } else {
            this.setState({
              itemsDisplay: null,
              isLoading: false
            });
          }
        },
        // Note: it's important to handle errors here
        // instead of a catch() block so that we don't swallow
        // exceptions from actual bugs in components.
        error => {
          this.setState({
            isLoading: false,
            itemsDisplay: null,
            error
          });
        }
      );
  };

  displaySection = display => {
    this.setState({
      section: display
    });
    localStorage.removeItem("sam_data");
    this.setCheckLocalStorage(this.state.sam_id, this.state.submittedAddress, display);
  };

  render() {
    // Set and retreieve lookup items
    let itemsLookupArray = this.state.itemsLookup;
    let itemsLookupMarkup = [];
    let resultItem;
    if (this.state.submittedKeywords) {
      if (itemsLookupArray.length > 0) {
        for (const [index, value] of itemsLookupArray.entries()) {
          resultItem = (
            <button
              className="cd dl-i"
              tabIndex='0'
              style={{ cursor: "pointer" }}
              onClick={this.displayAddress.bind(
                this,
                itemsLookupArray[index].attributes.field_sam_id,
                itemsLookupArray[index].attributes.field_sam_address,
                null
              )}
              key={index}
            >
              <li className="css-1tksw0t">
                <div
                  className="addr addr--s"
                  style={{
                    whiteSpace: "pre-line",
                    display: "inline-block",
                    verticalAlign: "middle",
                    lineHeight: "1.4"
                  }}
                >
                  {itemsLookupArray[index].attributes.field_sam_address}
                </div>
                <div style={{ clear: "both" }} />
              </li>
            </button>
          );
          itemsLookupMarkup.push(resultItem);
        }
      } else {
        itemsLookupMarkup = <div className="supporting-text">No address was found by that name.</div>;
      }
    }
    let recollectDate = (this.state.isLoadingRecollect ? null : this.state.itemsRecollect.day);
    let recollectServices = (this.state.isLoadingRecollect ? null : this.state.itemsRecollect.flags);
    let mnlDisplay = this.state.submittedAddress ? (
      <div>
      {history.pushState({id: 'sections'}, '', configProps.path+'?p2')}
      <div className="g">
        <CityServices
          recollect_date={recollectDate}
          recollect_services={recollectServices}
          section={this.state.section}
          displaySection={this.displaySection}
        />
        
       <CitySpaces
          library_branch={this.state.itemsDisplay.public_libraries_branch}
          library_address={this.state.itemsDisplay.public_libraries_address}
          library_zipcode={this.state.itemsDisplay.public_libraries_zipcode}
          comm_center={this.state.itemsDisplay.community_centers_name}
          comm_address={this.state.itemsDisplay.community_centers_address}
          comm_hours={this.state.itemsDisplay.community_centers_school_year_hours}
          comm_summer_hours={this.state.itemsDisplay.community_centers_summer_hours}
          park_name={this.state.itemsDisplay.parks_name}
          park_district={this.state.itemsDisplay.parks_district}
          park_ownership={this.state.itemsDisplay.parks_ownership}
          park_type={this.state.itemsDisplay.parks_type}
          hist_name={this.state.itemsDisplay.historic_districts_name}
          hist_place_name={this.state.itemsDisplay.historic_districts_place_name}
          hist_status={this.state.itemsDisplay.historic_districts_status}
          hist_year={this.state.itemsDisplay.historic_districts_year}
          hist_use_type={this.state.itemsDisplay.historic_districts_use_type}
          zoning_district={this.state.itemsDisplay.zoning_districts_district}
          section={this.state.section}
          displaySection={this.displaySection}
        />

        <Representation
          councilor={this.state.itemsDisplay.city_council_councilor}
          district={this.state.itemsDisplay.city_council_district}
          councilor_image={this.state.itemsDisplay.city_council_image}
          councilor_webpage={this.state.itemsDisplay.city_council_webpage}
          liason_name={this.state.itemsDisplay.ons_liaison_name}
          liason_image={this.state.itemsDisplay.ons_liaison_pic_url}
          liason_webpage={this.state.itemsDisplay.ons_liaison_webpage}
          liason_neighborhood={this.state.itemsDisplay.ons_liaison_neighborhood}
          voting_location={this.state.itemsDisplay.polling_locations_location2}
          voting_address={this.state.itemsDisplay.polling_locations_location3}
          early_voting_active={this.state.earlyVoting}
          early_voting_dates={this.state.itemsDisplay.early_voting_dates}
          early_voting_times={this.state.itemsDisplay.early_voting_times}
          early_voting_address={this.state.itemsDisplay.early_voting_address}
          early_voting_location={this.state.itemsDisplay.early_voting_location}
          early_voting_neighborhood={this.state.itemsDisplay.early_voting_neighborhood}
          early_voting_notes={this.state.itemsDisplay.early_voting_notes}
          ward={this.state.itemsDisplay.ward_name}
          precinct={this.state.itemsDisplay.precincts_name}
          section={this.state.section}
          displaySection={this.displaySection}
        />
 
        {this.state.season == "summer" || this.state.season == null ? (
          <SummerResources
            tot_name={this.state.itemsDisplay.tot_sprays_name}
            tot_address={this.state.itemsDisplay.tot_sprays_address}
            bcyf_pool_center_name={this.state.itemsDisplay.bcyf_pool_centers_name}
            bcyf_pool_center_address={this.state.itemsDisplay.bcyf_pool_centers_address}
            bcyf_pool_center_hours={this.state.itemsDisplay.bcyf_pool_centers_school_year_hours}
            bcyf_pool_center_hours_summer={this.state.itemsDisplay.bcyf_pool_centers_summer_hours}
            section={this.state.section}
            displaySection={this.displaySection}
          />
        ) : null}

        {this.state.season == "winter" || this.state.season == null ? (
          <WinterResources
            snow_routes={this.state.itemsDisplay.snow_routes_name}
            snow_routes_respsonsibility={this.state.itemsDisplay.snow_routes_responsibility}
            snow_parking_lots_name={this.state.itemsDisplay.snow_parking_lots_name}
            snow_parking_lots_address={this.state.itemsDisplay.snow_parking_lots_address}
            snow_parking_lots_fee={this.state.itemsDisplay.snow_parking_lots_fee}
            snow_parking_lots_comments={this.state.itemsDisplay.snow_parking_lots_comments}
            section={this.state.section}
            displaySection={this.displaySection}
          />
        ) : null}

        <Newsletter 
          section={this.state.section}
        />
        <Bos311 
          section={this.state.section}
        />
      </div>
      </div>
    ) : (
      ""
    );
    return (
      <div className="paragraphs-items paragraphs-items-field-components paragraphs-items-full paragraphs-items-field-components-full mnl">
        <div>
          <Search
            handleKeywordChange={this.handleKeywordChange}
            handleKeywordSubmit={this.handleKeywordSubmit}
            placeholder="Enter your address"
            searchClass="sf-i-f"
            styleInline={{ color: this.state.searchColor }}
            currentKeywords={this.state.currentKeywords}
          />
        </div>
        <div style={{ paddingTop: "50px" }}>
          {this.state.isLoading ? (
            <div className="supporting-text">Loading ... </div>
          ) : (
            <div>
              <ul className="dl">{itemsLookupMarkup}</ul>
              <div>{mnlDisplay}</div>
            </div>
          )}
        </div>
        <div>&nbsp;</div>
      
      </div>
    );
  }
}

ReactDOM.render(<MNL />,
  document.getElementById("web-app")
);



