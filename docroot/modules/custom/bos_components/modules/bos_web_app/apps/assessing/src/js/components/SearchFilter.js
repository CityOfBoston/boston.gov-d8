class SearchFilters extends React.Component {
  constructor (props) {
    super(props);
  }

  render () {
    const RadioBtnElems = () => {
      const fn_onChange = this.props.onChange;
      
      return this.props.searchFilters.map((obj, index) => {
        let attributes = {
          id: `radio[${index}]`,
          type: 'radio',
          name: index,
          className: 'ra-f',
          labelText: obj.label,
          onChange: {fn_onChange},
        };

        if (this.props.searchByFilter === index)
          attributes['checked'] = 'checked';

        return <RadioBtn {...attributes} />;
      });
    }

    return(
      <div className="search-filters">
        <label className="filters-label">Search By:</label>

        {RadioBtnElems()}
      </div>
    );
  }
}