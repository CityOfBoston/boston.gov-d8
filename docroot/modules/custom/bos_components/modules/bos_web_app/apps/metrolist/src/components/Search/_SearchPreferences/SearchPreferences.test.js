import '__mocks__/matchMedia';
import React from 'react';
import { render } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

import SearchPreferences from './index';

describe( 'SearchPreferences', () => {
  it( 'renders', () => {
    render( <SearchPreferences setFilters={ () => {} } /> );
    // throw new Error( 'Test missing' );
  } );
} );
