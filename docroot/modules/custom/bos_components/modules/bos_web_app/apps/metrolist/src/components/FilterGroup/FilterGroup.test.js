import '__mocks__/matchMedia';
import React from 'react';
import { render } from '@testing-library/react';
import Filter from '@components/Filter';
import FilterGroup from './index';

it( 'renders', () => {
  render(
    <FilterGroup>
      <Filter />
    </FilterGroup>,
  );
} );
