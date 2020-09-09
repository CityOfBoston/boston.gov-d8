import '__mocks__/matchMedia';
import { getNoFiltersApplied } from '__mocks__/filters';
// import { minimalHomeDefinition } from '__mocks__/homes';

import React from 'react';
import {
  render,
  // prettyDOM,
  // fireEvent,
  // createEvent,
} from '@testing-library/react';
// import { act } from 'react-dom/test-utils';
import FiltersPanel from './index';

it( 'renders', () => {
  const { getByLabelText } = render(
    <FiltersPanel
      filters={ getNoFiltersApplied() }
      handleFilterChange={ () => {} }
      updateDrawerHeight={ () => {} }
      listingCounts={ {
        "offer": {
          "rent": 0,
          "sale": 0,
        },
        "location": {
          "city": {
            "boston": 0,
            "beyondBoston": 0,
          },
          "neighborhood": {},
          "cardinalDirection": {
            "west": 0,
            "north": 0,
            "south": 0,
          },
        },
        "rentalPrice": {
          "lowerBound": 0,
          "upperBound": 0,
        },
      } }
      drawerRef={ { "current": null } }
    />,
  );
  const filtersPanelHeading = getByLabelText( 'Filter Listings ⌄' );
  // Not possible to test for preventDefault being called?
  // const mockDoubleClick = {
  //   ...( new MouseEvent( 'click' ) ),
  //   "detail": 2,
  //   "preventDefault": jest.fn(),
  // };

  // fireEvent( mockDoubleClick, filtersPanelHeading );

  // expect( mockDoubleClick.preventDefault ).toHaveBeenCalledTimes( 1 );
} );
