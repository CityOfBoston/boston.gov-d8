function RadioBtn(props) {
  let attributes = {
    id: `radio[${props.index}]`,
    type: 'radio',
    name: props.name,
    className: 'ra-f',
    onchange: props.onChange,
  };
  
  // console.log('props.onchange: type =', typeof props.onchange, ' : ', props.onchange);
  
  if (props.checked)
    attributes['checked'] = props.checked;

  return (
    <label
      className="ra" 
      for={`radio[${props.index}]`}
    >
      <input {...attributes}/>
      <span className="ra-l">{props.labelText}</span>
    </label>
  );
}

RadioBtn.defaultProps = {
  index: 0,
};

