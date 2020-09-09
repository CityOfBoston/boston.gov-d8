import React from 'react';
import PropTypes from 'prop-types';
import { numericString } from 'airbnb-prop-types';

import './Icon.scss';

function Icon( props ) {
  const attributes = {
    "className": `ml-icon ml-icon--${props.icon}`,
    ...props,
  };

  return (
    <picture>
      <source data-testid="ml-icon__svg" type="image/svg+xml" srcSet={ `/images/${props.icon}.sv\g` } />
      <img
        { ...attributes }
        src={ `/images/${props.icon}.png` }
        srcSet={ `/images/${props.icon}.png 1x, /images/${props.icon}@2x.png 2x, /images/${props.icon}@3x.png 3x` }
        alt={ props.alt }
      />
    </picture>
  );
}

Icon.propTypes = {
  "icon": PropTypes.string.isRequired,
  "width": numericString(),
  "height": numericString(),
  "alt": PropTypes.string.isRequired,
};

export default Icon;
