// Import not needed because React, ReactDOM, and local/global compontents are loaded by *.libraries.yml

class MNLItems extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      error: null,
      isLoading: false,
      season: null,
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
  };

  handleKeywordSubmit = event => {
    event.preventDefault();
    this.setState({
      isLoading: true,
      submittedKeywords: true,
      submittedAddress: null
    });
    this.lookupAddress();
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
    fetch(
      "jsonapi/node/neighborhood_lookup?filter" +
        paramsSamGet.value +
        "&fields" +
        paramsSamGet.fields
    )
      .then(res => res.json())
      .then(
        result => {
          //console.log(result)
          //let results = ;
          if (result.data[0])
            this.setState({
              isLoading: false,
              submittedAddress: result.data[0].attributes.field_sam_address,
              submittedKeywords: false,
              itemsLookup: [],
              itemsDisplay:
                result.data[0].attributes.field_sam_neighborhood_data
            });
          else
            this.setState({
              itemsDisplay: null
            });
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

  componentDidMount() {
    // Test address option
    //this.displayAddress('30712','Cheryl Ln')
  }

  render() {
    // Set and retreieve display items
    const regexTest = new RegExp("(<([^>]+)>)");
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
    });

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
        <Representation
          councilor={objCont.councilor}
          district={objCont.district}
          councilor_image={objCont.councilor_image}
          councilor_webpage={objCont.councilor_webpage}
          liason_name={objCont.liason_name}
          liason_image={objCont.liason_pic_url}
          voting_location={objCont.vote_location2}
          voting_address={objCont.vote_location3}
          early_voting_dates={objCont.early_voting_dates}
          early_voting_times={objCont.early_voting_times}
          early_voting_address={objCont.early_voting_address}
          early_voting_location={objCont.early_voting_location}
          early_voting_neighborhood={objCont.early_voting_neighborhood}
          early_voting_notes={objCont.early_voting_notes}
          ward={objCont.ward}
          precinct={objCont.precinct}
          section={this.state.section}
          displaySection={this.displaySection}
        />

        <CitySpaces
          library_branch={objCont.library_branch}
          library_address={objCont.library_address}
          comm_center={objCont.bcyf_center}
          comm_address={objCont.bcyf_address}
          comm_hours={objCont.bcyf_school_year_hours}
          comm_summer_hours={objCont.bcyf_summer_hours}
          park_name={objCont.park_name}
          park_district={objCont.park_district}
          park_ownership={objCont.park_ownership}
          park_type={objCont.park_type}
          section={this.state.section}
          displaySection={this.displaySection}
        />

        <PropertyInformation
          hist_name={objCont.hist_name}
          hist_place_name={objCont.hist_place_name}
          hist_status={objCont.hist_status}
          hist_year={objCont.hist_year}
          hist_use_type={objCont.hist_use_type}
          section={this.state.section}
          displaySection={this.displaySection}
        />

        {this.state.season == "winter" || this.state.season == null ? (
          <WinterResources
            snow_routes={objCont.snow_routes_full_name}
            snow_routes_respsonsibility={objCont.snow_routes_responsibility}
            section={this.state.section}
            displaySection={this.displaySection}
          />
        ) : null}

        {this.state.season == "summer" || this.state.season == null ? (
          <SummerResources
            tot_name={objCont.tot_park_name}
            tot_address={objCont.tot_address_text}
            section={this.state.section}
            displaySection={this.displaySection}
          />
        ) : null}
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
