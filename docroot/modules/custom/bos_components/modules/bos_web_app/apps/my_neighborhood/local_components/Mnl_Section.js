class MnlSection extends React.Component {
  render() {
    let imageHeader;
    if (this.props.image_header) {
      imageHeader = (
        <div className="cd-ic bg--cb" style={{ padding: "20px 0 0" }}>
          <img src={this.props.image_header} style={{ maxHeight: "150px" }} />
        </div>
      );
    }

    let title;
    if (this.props.name) {
      name = (
        <div className="cd-st t--upper t--subtitle">{this.props.title}</div>
      );
    }

    let desc;
    if (this.props.desc) {
      desc = <div className="cdp-st">{this.props.desc}</div>;
    }

    return (
      <div className="pointer">
        <div className="d-b bg--cb cdp-a ta-c p-a200 t--upper t--sans t--w t--ob--h t--s100">
          {this.props.title}
        </div>
        {imageHeader}
        <div className="cd-c">
          {title}
          {desc}
        </div>
      </div>
    );
  }
}
