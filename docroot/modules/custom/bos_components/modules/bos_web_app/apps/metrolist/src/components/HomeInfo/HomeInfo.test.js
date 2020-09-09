import React from 'react';
import { render } from '@testing-library/react';
import HomeInfo from './index';

it( 'renders', () => {
  render( <HomeInfo info={ {} } /> );
} );
