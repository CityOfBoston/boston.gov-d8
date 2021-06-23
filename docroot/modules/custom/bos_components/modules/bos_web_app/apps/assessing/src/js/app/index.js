// Import not needed because React, ReactDOM, and local/global compontents are loaded by *.libraries.yml
class MNL extends React.Component {
  constructor (props) {
    super(props);
    
    this.state = {
      error: null,
      isLoading: false,
      section: null,
      sam_id: null,
      itemsLookup: [],
      itemsDisplay: null,
      currentKeywords: null,
      submittedAddress: null,
      submittedKeywords: null,
      searchColor: null,
      searchByFilter: 0,
      searchFilters: [
        {
          value: 'address',
          label: 'Address',
          instructions: "Enter your address by street #, street name, suffix (Street, Ave, etc) ex. 1 City Hall Sq",
          placeholder: "Search by address",
        },
        {
          value: 'owner',
          label: "Property Owner",
          instructions: "Enter the property owner's name (last, first) ex. Last name, First Name",
          placeholder: "Search by Last name, First name",
        },
        {
          value: 'id',
          label: 'Parcel ID',
          instructions: "Enter the Property ID number ex. 0504203000",
          placeholder: "Search by Property ID",
        },
      ],
      queryStr: {
        base: `https://data.boston.gov/api/3/action/datastore_search_sql`,
        sql: {
          pre: `?sql=SELECT%20*%20from%20%228de4e3a0-c1d2-47cb-8202-98b9cbe3bd04%22%20WHERE%20%22`,
          filters: [
            {
              // pre: `ST_NUM%22%20LIKE%20%27100%27%20AND%20%22ST_NAME%22%20LIKE%20%27HOWARD%27%20AND%20%22ST_NAME_SUF%22%20LIKE%20%27AV%27`,
              pre: `ST_NUM%22%20LIKE%20%27100%27%20AND%20%22ST_NAME%22%20LIKE%20%27HOWARD%27%20AND%20%22ST_NAME_SUF%22%20LIKE%20%27AV%27`,
              post: ``,
              parseObj: {
                bridge: `%20AND%20`,
                st_num: `ST_NUM%22%20LIKE%20%27__value__%27`,
                st_name: `%22ST_NAME%22%20LIKE%20%27__value__%27`,
                st_name_suffix: `%22ST_NAME_SUF%22%20LIKE%20%27__value__%27`,
              },
            },
            {
              pre: `OWNER%22%20LIKE%20%27`,
              post: `%%27`
            },
            {
              pre: `PID%22%20LIKE%20%27`,
              post: `%27`,
            },
          ],
        },
      },
      st_suffix: [
        { abbr: 'AL', label: 'Alley' },
        { abbr: 'AV', label: 'Avenue' },
        { abbr: 'AV', label: 'Ave' },
        { abbr: 'AVE', label: 'Avenue' },
        { abbr: 'BL', label: 'BLDV' },
        { abbr: 'CI', label: 'Circle' },
        { abbr: 'CT', label: 'Court' },
        { abbr: 'DM', label: 'Dam' },
        { abbr: 'DR', label: 'Drive' },
        { abbr: 'HW', label: 'Highway' },
        { abbr: 'PK', label: 'Park' },
        { abbr: 'PW', label: 'Parkway' },
        { abbr: 'PL', label: 'Place' },
        { abbr: 'PZ', label: 'Plaza' },
        { abbr: 'RD', label: 'Road' },
        { abbr: 'RO', label: 'Row' },
        { abbr: 'SQ', label: 'Square' },
        { abbr: 'ST', label: 'Street' },
        { abbr: 'TE', label: 'Terrace' },
        { abbr: 'WY', label: 'Way' },
      ],
    };
  }

  componentDidMount () {
    let inputHeight = jQuery("#web-app input").height();
    let inputWidth = jQuery("#web-app input").width() - 75;
    jQuery(".resize").css('height', inputHeight + 'px');
    jQuery(".resize").css('width', inputWidth + 'px');
    jQuery("#web-app input").css('height', inputHeight + 'px');

    let that = this;
    window.addEventListener('popstate', function () {
      that.setDefaults();
    }, false);
  }

  setDefaults = () => {
    { (!configProps.frame_google() ? history.pushState(null, null, configProps.globals.path) : null) }
  }

  scaleInputText = () => {
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
    this.setState({
      currentKeywords: event.target.value,
      submittedKeywords: null,
      searchColor: null
    });

    // console.log('handleKeywordChange: ', event.target.value, ' | ', this.state.currentKeywords);

    if (inputChars >= 5 || event.keyCode === 13) {
      this.setState({
        isLoading: true,
        submittedKeywords: true,
        submittedAddress: null
      });
    }
  }

  handleKeywordSubmit = event => {
    event.preventDefault();
    this.setState({
      isLoading: true,
      submittedKeywords: true,
      submittedAddress: null,
    });
    this.lookupAddress();
  };

  lookupAddress = () => {
    let addressSt = this.state.currentKeywords;
    let qryParams = this.state.queryStr;
    let addressQuery = addressSt ? encodeURI(this.state.currentKeywords.toUpperCase()) : '';
    let qryUrl = `${qryParams.base}${qryParams.sql.pre}${qryParams.sql.filters[this.state.searchByFilter].pre}${addressQuery}${qryParams.sql.filters[this.state.searchByFilter].post}`;
    const currSearchFilterObj = this.state.searchFilters[this.state.searchByFilter];

    if (currSearchFilterObj.value === 'address') {
      let addressStr = decodeURIComponent(addressQuery);
      let addressArr = addressStr.split(' ');
      let getMatchingSuffixObj = this.state.st_suffix.find(obj => {
        const lastObj = addressArr[addressArr.length-1];
        return obj.abbr.toLowerCase() === lastObj.toLocaleLowerCase() || obj.label.toLowerCase() === lastObj.toLocaleLowerCase()
      });

      if (
        addressArr.length > 2 &&
        parseInt(addressArr[0]) !== 'NaN' &&
        typeof getMatchingSuffixObj === 'object'
      ) {
        const sqlParams = qryParams.sql.filters[this.state.searchByFilter];
        const st_num = sqlParams.parseObj.st_num.replace('__value__', addressArr[0]);
        const st_name_suffix = sqlParams.parseObj.st_name_suffix.replace('__value__', getMatchingSuffixObj.abbr);
        const stName = addressArr.length === 3 ? encodeURIComponent(addressArr[1]) : encodeURIComponent(addressArr.slice(1, addressArr.length - 1).join(' '));
        const st_name = sqlParams.parseObj.st_name.replace('__value__', stName);
        const qry = `${st_num}${sqlParams.parseObj.bridge}${st_name}${sqlParams.parseObj.bridge}${st_name_suffix}`;
        
        qryUrl = `${qryParams.base}${qryParams.sql.pre}${qry}`;
        // qryUrl = `${qryParams.base}${qryParams.sql.pre}${qryParams.sql.filters[this.state.searchByFilter].pre}`;
      }
    }
    
    fetch(
      qryUrl,
      {
        method: 'GET',
        redirect: 'follow',
      },
    )
      .then(res => res.json())
      .then(
        result => {
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

  searchFilterHandler = ev => {
    this.setState({
      searchByFilter: ev.currentTarget.value
    });
    
    // console.log(`searchFilterHandler > ${this.state.searchFilters[ev.currentTarget.value].label}`);
  };

  render () {
    // Set and retreieve lookup items
    let itemsLookupArray = this.state.itemsLookup.slice(0, 9);
    let itemsLookupMarkup = [];
    let resultItem;
    if (this.state.submittedKeywords) {
      if (itemsLookupArray.length > 0) {
        for (const [index, value] of itemsLookupArray.entries()) {
          // console.log('render > value: ', value);
          resultItem = (
            <a
              className="cd dl-i search-result"
              tabIndex='0'
              style={{ cursor: "pointer" }}
              key={index}
              href={`assessing-online/${itemsLookupArray[index].PID}`}
            >
              <li className="address-item rows">
                <div className="prop-value column-value mobile">
                  ${itemsLookupArray[index].AV_TOTAL}
                </div>
                <div className="prop-value column-property">
                  {itemsLookupArray[index].MAIL_ADDRESS}
                </div>
                <div className="prop-value column-owner">
                  {itemsLookupArray[index].OWNER}
                </div>
                <div className="prop-value column-parcel">
                  {itemsLookupArray[index].PID}
                </div>
                <div className="prop-value column-value desktop">
                  ${itemsLookupArray[index].AV_TOTAL}
                </div>
              </li>
            </a>
          );
          itemsLookupMarkup.push(resultItem);
        }
      } else {
        itemsLookupMarkup = <div className="supporting-text">No address was found by that name.</div>;
      }
    }

    const renderListHeaders = () => {
      let retElem = '';
      if (itemsLookupMarkup.length > 0) {
        retElem = (
          <li className="header">
            <div className="header-label">Property</div>
            <div className="header-label">Owner</div>
            <div className="header-label">Parcel ID</div>
            <div className="header-label">Value</div>
          </li>
        );
      }
      return retElem;
    };

    return (
      <div className="paragraphs-items paragraphs-items-field-components paragraphs-items-full paragraphs-items-field-components-full mnl">
        <SearchFilters
          searchByFilter={this.state.searchByFilter}
          searchFilters={this.state.searchFilters}
          onChange={this.searchFilterHandler}
        />

        <div className="filterBy-desc">
          {this.state.searchFilters[this.state.searchByFilter].instructions}
        </div>
        
        <div>
          <Search
            handleKeywordChange={this.handleKeywordChange}
            handleKeywordSubmit={this.handleKeywordSubmit}
            placeholder={this.state.searchFilters[this.state.searchByFilter].placeholder}
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
              <ul className="dl">
                {renderListHeaders()}
                {itemsLookupMarkup}
              </ul>
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
