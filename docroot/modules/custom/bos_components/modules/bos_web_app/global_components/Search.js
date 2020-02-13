

class Search extends React.Component {
  render() {
      return (
        <div className="sf">
          <div className="sf-i mnl-search">
            <input
                type="text"
                value={this.props.currentKeywords}
                style={this.props.styleInline}
                aria-label={this.props.placeholder}
                placeholder={this.props.placeholder}
                className={this.props.searchClass + ' search'}
                onChange={this.props.handleKeywordChange}
                onKeyUp={this.props.handleKeywordChange}
            />
        
            <button
              type="button"
              className="sf-i-b"
              onClick={this.props.handleKeywordSubmit}
            >
              Search
            </button>
          </div>
          <div className="sf-i resize">
            <span style ={this.props.styleInline} className={this.props.searchClass}>{this.props.currentKeywords}</span>
          </div>
        </div>
      )
  }
}


