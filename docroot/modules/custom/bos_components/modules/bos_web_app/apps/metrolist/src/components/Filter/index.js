import React from 'react';
import PropTypes from 'prop-types';

import Checkbox from '@components/Checkbox';
import Scale from '@components/Scale';
import Range from '@components/Range';

import './Filter.scss';

function renderFormControl( props ) {
  // console.log( 'props', props );
  switch ( props.type ) { // eslint-disable-line react/prop-types
    case 'scale':
      return <Scale { ...props } />;
    case 'checkbox':
      return <Checkbox { ...props } />;
    case 'checkbox-button':
      return <Checkbox button { ...props } />;
    case 'range':
      return <Range { ...props } />;
    default:
      return <Checkbox subcategoriesOnly { ...props } />;
  }
}

function Filter( props ) {
  return renderFormControl( props );
}

Filter.propTypes = {
  "className": PropTypes.string,
  "columnWidth": PropTypes.oneOfType( [PropTypes.string, PropTypes.bool] ),
  "type": PropTypes.oneOf( ['checkbox', 'checkbox-button', 'scale', 'range'] ),
  "criterion": PropTypes.string,
  "value": PropTypes.string,
  "values": PropTypes.string,
};

Filter.Label = ( props ) => <>{ props.children }</>;
Filter.Label.displayName = "FilterLabel";
Filter.Label.propTypes = { "children": PropTypes.node };

export default Filter;
