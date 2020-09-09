import React from 'react';
import { render } from '@testing-library/react';
import { studioUnit } from '__mocks__/homes';
import Unit from './index';

it( 'renders', () => {
  render(
    <table>
      <tbody>
        <Unit unit={ studioUnit } />
      </tbody>
    </table>,
  );
} );
