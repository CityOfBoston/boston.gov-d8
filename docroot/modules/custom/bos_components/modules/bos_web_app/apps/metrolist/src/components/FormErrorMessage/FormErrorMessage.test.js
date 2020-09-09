import React from 'react';
import { render } from '@testing-library/react';
import FormErrorMessage from './index';
// import { generateRandomNumberString } from '@util/strings';

it( 'renders', () => {
  render( <FormErrorMessage id="form-error-message" /> );
} );
