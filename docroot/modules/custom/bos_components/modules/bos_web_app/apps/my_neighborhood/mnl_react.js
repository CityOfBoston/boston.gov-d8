// Import not needed because React, ReactDOM, and local/global compontents are loaded by *.libraries.yml

class MNLItems extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      error: null,
      isLoading: false,
      season: configProps.season,
      earlyVoting: configProps.earlyVoting,
      section: null,
      sam_id: null,
      itemsLookup: [],
      itemsDisplay: null,
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
    //Skip and test
    //this.displayAddress('30712','22 Cheryl Parkway West Roxbury, 02136')
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
    this.setState({
      currentKeywords: event.target.value,
      submittedKeywords: null,
      searchColor: null
    });

    if (event.keyCode === 13) {
      this.setState({
        isLoading: true,
        submittedKeywords: true,
        submittedAddress: null
      });
      this.lookupAddress();
    }
    //let operation = event.keyCode;
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

  lookupAddress = event => {
    let addressQuery = this.state.currentKeywords;
    let paramsQuery = {
      condition: "[field_sam_address][operator]=CONTAINS",
      value: "[field_sam_address][value]=" + addressQuery,
      fields: "[node--neighborhood_lookup]=field_sam_address,field_sam_id"
    };
    fetch(
      "jsonapi/node/neighborhood_lookup?filter" +
        paramsQuery.condition +
        "&filter" +
        paramsQuery.value +
        "&fields" +
        paramsQuery.fields
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

  displayAddress = (sam_id, sam_address) => {
    this.setState({
      isLoading: true,
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
      "jsonapi/node/neighborhood_lookup?filter" +
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
            this.scaleInputText();
          } else {
            this.setState({
              itemsDisplay: null
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
  };


  render() {
    // Set and retreieve display items
    /*const regexTest = new RegExp("(<([^>]+)>)");
    let mnl_items_pre = this.state.itemsDisplay || "";
    let mnl_items = mnl_items_pre.replace(regexTest, "");
    let objCont = {};
    let mnl_items_string = String(mnl_items).split(",");
    const objData = mnl_items_string;
    objData.forEach(function(item) {
      let aItems = item.split(/:\s/);
      let keyItem = aItems[0].replace(/['"]+/g, "").trim();
      let valueItem = String(aItems[1])
        .replace(/['"]+/g, "")
        .trim();
      objCont[keyItem] = valueItem.replace(/(<([^>]+)>)/gi, "");
    });*/

    // Set and retreieve lookup items
    let itemsLookupArray = this.state.itemsLookup;
    let itemsLookupMarkup = [];
    let resultItem;
    if (this.state.submittedKeywords) {
      if (itemsLookupArray.length > 0) {
        for (const [index, value] of itemsLookupArray.entries()) {
          resultItem = (
            <a
              className="cd dl-i"
              style={{ cursor: "pointer" }}
              onClick={this.displayAddress.bind(
                this,
                itemsLookupArray[index].attributes.field_sam_id,
                itemsLookupArray[index].attributes.field_sam_address
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
            </a>
          );
          itemsLookupMarkup.push(resultItem);
        }
      } else {
        itemsLookupMarkup = "No address was found by that name.";
      }
    }
    let mnlDisplay = this.state.submittedAddress ? (
      <div className="g">
       <CitySpaces
          library_branch={this.state.itemsDisplay.library_branch}
          library_address={this.state.itemsDisplay.library_address}
          comm_center={this.state.itemsDisplay.bcyf_center}
          comm_address={this.state.itemsDisplay.bcyf_address}
          comm_hours={this.state.itemsDisplay.bcyf_school_year_hours}
          comm_summer_hours={this.state.itemsDisplay.bcyf_summer_hours}
          park_name={this.state.itemsDisplay.park_name}
          park_district={this.state.itemsDisplay.park_district}
          park_ownership={this.state.itemsDisplay.park_ownership}
          park_type={this.state.itemsDisplay.park_type}
          hist_name={this.state.itemsDisplay.hist_name}
          hist_place_name={this.state.itemsDisplay.hist_place_name}
          hist_status={this.state.itemsDisplay.hist_status}
          hist_year={this.state.itemsDisplay.hist_year}
          hist_use_type={this.state.itemsDisplay.hist_use_type}
          section={this.state.section}
          displaySection={this.displaySection}
        />

        <Representation
          councilor={this.state.itemsDisplay.councilor}
          district={this.state.itemsDisplay.district}
          councilor_image={this.state.itemsDisplay.councilor_image}
          councilor_webpage={this.state.itemsDisplay.councilor_webpage}
          liason_name={this.state.itemsDisplay.liason_name}
          liason_image={this.state.itemsDisplay.liason_pic_url}
          voting_location={this.state.itemsDisplay.vote_location2}
          voting_address={this.state.itemsDisplay.vote_location3}
          early_voting_active={this.state.earlyVoting}
          early_voting_dates={this.state.itemsDisplay.early_voting_dates}
          early_voting_times={this.state.itemsDisplay.early_voting_times}
          early_voting_address={this.state.itemsDisplay.early_voting_address}
          early_voting_location={this.state.itemsDisplay.early_voting_location}
          early_voting_neighborhood={this.state.itemsDisplay.early_voting_neighborhood}
          early_voting_notes={this.state.itemsDisplay.early_voting_notes}
          ward={this.state.itemsDisplay.ward}
          precinct={this.state.itemsDisplay.precinct}
          section={this.state.section}
          displaySection={this.displaySection}
        />
 
        {this.state.season == "summer" || this.state.season == null ? (
          <SummerResources
            tot_name={this.state.itemsDisplay.tot_park_name}
            tot_address={this.state.itemsDisplay.tot_address_text}
            bcyf_pool_center_name={this.state.itemsDisplay.bcyf_pool_center_name}
            bcyf_pool_center_address={this.state.itemsDisplay.bcyf_pool_center_address}
            section={this.state.section}
            displaySection={this.displaySection}
          />
        ) : null}

        {this.state.season == "winter" || this.state.season == null ? (
          <WinterResources
            snow_routes={this.state.itemsDisplay.snow_routes_full_name}
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
      </div>
    ) : (
      ""
    );
    return (
      <div className="paragraphs-items paragraphs-items-field-components paragraphs-items-full paragraphs-items-field-components-full">
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
            <div>Loading ... </div>
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

const el = document.getElementById("web-app");
ReactDOM.render(<MNLItems />, el);
