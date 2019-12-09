class MnlCard extends React.Component {
  render() {
    let textAlign = "left";
    let imageHeader;
    if (this.props.image_header) {
      imageHeader = (
        <div className="cd-ic bg--cb" style={{ padding: "20px 0 0" }}>
          <img src={this.props.image_header} style={{ maxHeight: "150px" }} />
        </div>
      );
    }

    let image;
    if (this.props.image) {
      image = <img src={this.props.image} className="cdp-i p-a100" />;
      textAlign = "center";
    }

    // content array
    let itemsContent = [];
    for (const [index, value] of this.props.content_array.entries()) {
      let heading;
      let content;
      if (value.heading) {
        heading = (
          <div className="cd-st t--upper t--subtitle">{value.heading}</div>
        );
      }
      if (value.content) {
        content = <div className="cdp-st t--g300">{value.content}</div>;
      }
      itemsContent.push(
        <div>
          {heading}
          {content}
        </div>
      );
    }
    return (
      <div
        className="cd g--4 g--4--sl m-t500  cdp-l"
        style={{ textAlign: textAlign }}
      >
        <div className="d-b bg--cb cdp-a ta-c p-a200 t--upper t--sans t--w t--ob--h t--s100">
          {this.props.title}
        </div>
        {imageHeader}
        <div className="cd-c">
          {image}
          {itemsContent}
        </div>
      </div>
    );
  }
}
