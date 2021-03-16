// Import not needed because React, ReactDOM, and local/global compontents are loaded by *.libraries.yml
class MNL extends React.Component {
  constructor (props) {
    super(props);
    this.state = {
      error: null,
      isLoading: false,
      isLoadingRecollect: null,
      section: null,
      sam_id: null,
      itemsLookup: [],
      itemsDisplay: null,
      itemsRecollect: [],
      currentKeywords: null,
      submittedAddress: null,
      submittedKeywords: null,
      searchColor: null,
      searchType: 'owner',
    };
  }

  componentDidMount () {
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
      else if (that.state.submittedAddress !== null && that.state.section == null) {
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
    { (!configProps.frame_google() ? history.pushState(null, null, configProps.globals.path) : null) }
  }

  setCheckLocalStorage = (sam_id, sam_address, section) => {
    console.log('setCheckLocalStorage: ', localStorage.getItem("sam_data"));
    if (!configProps.frame_google()) {
      if (localStorage.getItem("sam_data")) {
        let localSAM = JSON.parse(localStorage.getItem("sam_data"));
        this.displayAddress(localSAM[0].sam_id, localSAM[0].sam_address, localSAM[0].section);

        if (localSAM[0].section !== null) {
          this.setState({ section: localSAM[0].section });
        }
      } else {
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
  }

  scaleInputText = op => {
    jQuery(".resize").textfill({
      minFontPixels: 20,
      maxFontPixels: 75,
      success: function () {
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
    let address = { "address": this.state.submittedAddress };
    fetch(
      "rest/recollect",
      {
        method: "POST",
        body: JSON.stringify(address),
      }
    )
      .then(res => res.json())
      .then(
        result => {
          if (result.response && result.response.events.length > 0) {
            this.setState({
              isLoadingRecollect: false,
              itemsRecollect: result.response.events,
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

  ownerStr = str => {

  };

  lookupAddress = event => {
    let addressQuery = encodeURI(this.state.currentKeywords.toUpperCase());
    fetch(
      `https://data.boston.gov/api/3/action/datastore_search_sql?sql=SELECT%20*%20from%20%228de4e3a0-c1d2-47cb-8202-98b9cbe3bd04%22%20WHERE%20%22OWNER%22%20LIKE%20%27${addressQuery}%%27`,
      {
        method: 'GET',
        redirect: 'follow',
      },

    )
      .then(res => res.json())
      .then(
        result => {
          console.log(`result: `, result);
          if (result.result.records.length > 0)
            this.setState({
              isLoading: false,
              itemsLookup: result.result.records
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
    this.setState({
      isLoading: true,
      isLoadingRecollect: true,
      searchColor: "#288BE4",
      section: null,
      sam_id: sam_id,
      currentKeywords: sam_address
    });
    
    let jsonData = "none";
    fetch(
      'https://data.boston.gov/api/3/action/datastore_search_sql?sql=SELECT%20*%20from%20"8de4e3a0-c1d2-47cb-8202-98b9cbe3bd04"%20WHERE%20"ST_NUM"%20LIKE%20%27100%27%20AND%20"ST_NAME"%20LIKE%20%27HOWARD%27',
      {
        method: 'GET',
        redirect: 'follow',
      },
    )
      .then(res => res.json())
      .then(
        result => {
          if (result.data[0]) {
            jsonData = JSON.parse(result.records);
            let newState = { ...this.state.itemsDisplay };
            newState.data = jsonData;
            this.setState({
              isLoading: false,
              // submittedAddress: result.data[0].attributes.field_sam_address,
              submittedAddress: `${jsonData.MAIL_ADDRESS}}`,
              submittedKeywords: false,
              itemsLookup: [],
              itemsDisplay: newState.data
            });
            this.lookupRecollect();
            this.scaleInputText();
            localStorage.removeItem("sam_data");
            this.setCheckLocalStorage(this.state.sam_id, this.state.submittedAddress, section);
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

  displaySection = (display, event) => {
    if (event && event.keyCode === 13 || event == null) {
      this.setState({
        section: display
      });
      localStorage.removeItem("sam_data");
      this.setCheckLocalStorage(this.state.sam_id, this.state.submittedAddress, display);
    }
  };

  render () {
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
                itemsLookupArray[index].PID,
                itemsLookupArray[index].MAIL_ADDRESS,
                null
              )}
              key={index}
            >
              <li className="css-1tksw0t">
                <div
                  className="mnl-address addr addr--s"
                  style={{
                    whiteSpace: "pre-line",
                    display: "block",
                    verticalAlign: "middle",
                    lineHeight: "1.4"
                  }}
                >
                  {itemsLookupArray[index].OWNER}
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

    // let recollectEvents = (this.state.isLoadingRecollect ? null : this.state.itemsRecollect);
    // let configSection = configProps.sections;
    let mnlDisplay = this.state.submittedAddress ? (
      <div>
        {(!configProps.frame_google() ? history.pushState({ id: 'sections' }, '', configProps.globals.path + '?p2') : null)}
        <div className="g">
          Text ...
        </div>
      </div>
    ) : (
      ""
    );
    return (
      <div className="paragraphs-items paragraphs-items-field-components paragraphs-items-full paragraphs-items-field-components-full mnl">
        <div className="search-filters">
          <label className="filters-label">Search By:</label>

          <label className="ra" for="radio[0]">
            <input
              id="radio[0]"
              type="radio"
              name="search_filter"
              value="Public Notices"
              className="ra-f"
            />
            <span className="ra-l">Owner</span>
          </label>

          <label className="ra" for="radio[1]">
            <input
              id="radio[1]"
              type="radio"
              name="search_filter"
              value={1}
              className="ra-f"
            />
            <span className="ra-l">Address</span>
          </label>

          <label className="ra" for="radio[2]">
            <input
              id="radio[2]"
              type="radio"
              name="search_filter"
              value={2}
              className="ra-f"
            />
            <span className="ra-l">ID</span>
          </label>
        </div>
        
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
        <div style={{ paddingTop: "30px" }}>
          {this.state.isLoading ? (
            <div className="supporting-text">Loading ... </div>
          ) : (
            <div>
              <ul className="dl">{itemsLookupMarkup}</ul>
              <div>{mnlDisplay}</div>
            </div>
          )}
        </div>
      </div>
    );
  }
}

ReactDOM.render(<MNL />,
  document.getElementById("web-app")
);
