import '@babel/polyfill';
import '__mocks__/matchMedia';
import {
  minimalHomeDefinition,
  studioUnit,
  oneBedroomUnit,
  twoBedroomUnit,
  threeBedroomUnit,
  fourBedroomUnit,
  aboveFourBedroomUnit,
} from '__mocks__/homes';
import { getNoFiltersApplied } from '__mocks__/filters';
import mockApiResponse from '__mocks__/developmentsApiResponse.json';
import React from 'react';
import {
  render,
  cleanup,
  fireEvent,
  waitForElement,
  getAllByRole,
} from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import { getDevelopmentsApiEndpoint } from '@util/dev';
import { generateRandomNumberString } from '@util/strings';
import { MemoryRouter } from 'react-router-dom';
import { rest } from 'msw';
import { setupServer } from 'msw/node';
import { act } from 'react-dom/test-utils';
import { LocalStorageMock } from '@react-mock/localstorage';

import Search from './index';

// Mock Developments API response
const server = setupServer(
  rest.get(
    getDevelopmentsApiEndpoint(),
    ( request, response, context ) => response(
      context.json( mockApiResponse ),
    ),
  ),
);

describe( 'Search', () => {
  beforeAll( () => server.listen() );

  afterEach( () => {
    server.resetHandlers();
    cleanup();
  } );

  afterAll( () => server.close() );

  it( 'Renders', async () => {
    const { getByTestId } = render(
      <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
        <Search />
      </MemoryRouter>,
    );
    const FiltersPanel = getByTestId( 'ml-filters-panel' );
    const ResultsPanel = getByTestId( 'ml-results-panel' );

    await act( async () => {
      await waitForElement( () => getAllByRole( ResultsPanel, 'article' ) );
    } );

    expect( FiltersPanel ).toBeInTheDocument();
    expect( ResultsPanel ).toBeInTheDocument();
  } );

  describe( 'Filters', () => {
    let noFiltersApplied = getNoFiltersApplied();

    beforeEach( () => {
      noFiltersApplied = getNoFiltersApplied();
    } );

    describe( 'Rental Price', () => {
      const homeWithUnitWithinPriceRange = {
        ...minimalHomeDefinition,
        "id": generateRandomNumberString(),
        "title": "Affordable",
        "offer": "rent",
        "units": [
          threeBedroomUnit,
        ],
      };
      const homeWithUnitOutsidePriceRange = {
        ...minimalHomeDefinition,
        "id": generateRandomNumberString(),
        "title": "Unaffordable",
        "offer": "rent",
        "units": [
          aboveFourBedroomUnit,
        ],
      };

      it( 'Filters results to only homes with units in the specified price range', () => {
        const homesToFilter = [homeWithUnitWithinPriceRange, homeWithUnitOutsidePriceRange];
        const { queryByText, getByTestId } = render(
          <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
            <Search
              homes={ homesToFilter }
              filters={
                {
                  ...Search.defaultProps.filters,
                  "rentalPrice": {
                    "lowerBound": 1000,
                    "upperBound": 4000,
                  },
                }
              }
            />
          </MemoryRouter>,
        );

        const lowerBoundInput = getByTestId( 'rentalPriceLowerBound' );
        const upperBoundInput = getByTestId( 'rentalPriceUpperBound' );

        const fourThousandPerMonth = () => queryByText( homeWithUnitWithinPriceRange.title );
        const fiveThousandPerMonth = () => queryByText( homeWithUnitOutsidePriceRange.title );

        expect( fourThousandPerMonth() ).toBeInTheDocument();
        expect( fiveThousandPerMonth() ).not.toBeInTheDocument();

        act( () => {
          fireEvent.change( lowerBoundInput, { "target": { "value": "4000" } } );
          fireEvent.change( upperBoundInput, { "target": { "value": "5000" } } );
        } );

        expect( fourThousandPerMonth() ).not.toBeInTheDocument();
        expect( fiveThousandPerMonth() ).toBeInTheDocument();
      } );
    } );

    describe( 'Offer', () => {
      const homeToRent = {
        ...minimalHomeDefinition,
        "id": generateRandomNumberString(),
        "title": "Home 1",
        "offer": "rent",
      };
      const homeToBuy = {
        ...minimalHomeDefinition,
        "id": generateRandomNumberString(),
        "title": "Home 2",
        "offer": "sale",
      };

      it( 'Filters results to only homes for rent', () => {
        const homesToFilter = [homeToRent, homeToBuy];
        const { getByLabelText, queryByText } = render(
          <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
            <Search
              homes={ homesToFilter }
              filters={
                {
                  ...Search.defaultProps.filters,
                  "offer": {
                    "rent": false,
                    "sale": false,
                  },
                }
              }
            />
          </MemoryRouter>,
        );
        const forRentInput = getByLabelText( /For Rent \(.*\)/, { "selector": "input" } );

        act( () => {
          fireEvent.click( forRentInput );
        } );

        const homeToBeFilteredOut = queryByText( homeToBuy.title );

        expect( forRentInput ).toBeChecked();
        expect( homeToBeFilteredOut ).not.toBeInTheDocument();
      } );

      it( 'Filters results to only homes for sale', () => {
        const homesToFilter = [homeToRent, homeToBuy];
        const { getByLabelText, queryByText } = render(
          <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
            <Search
              homes={ homesToFilter }
              filters={
                {
                  ...Search.defaultProps.filters,
                  "offer": {
                    "rent": false,
                    "sale": false,
                  },
                }
              }
            />
          </MemoryRouter>,
        );
        const forSaleInput = getByLabelText( /For Sale \(.*\)/, { "selector": "input" } );
        act( () => {
          fireEvent.click( forSaleInput );
        } );
        const homeToBeFilteredOut = queryByText( homeToRent.title );

        expect( forSaleInput ).toBeChecked();
        expect( homeToBeFilteredOut ).not.toBeInTheDocument();
      } );
    } );

    describe( 'Location', () => {
      const homeWithinBoston = {
        ...minimalHomeDefinition,
        "id": generateRandomNumberString(),
        "title": "Home 1",
        "city": "Boston",
        "cardinalDirection": null,
      };
      const homeOutsideBoston = {
        ...minimalHomeDefinition,
        "id": generateRandomNumberString(),
        "title": "Home 2",
        "city": "Cambridge",
        "cardinalDirection": "west",
      };

      it( 'Filters results to only homes within Boston', () => {
        const homesToFilter = [homeWithinBoston, homeOutsideBoston];
        const { getByLabelText, queryByText } = render(
          <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
            <Search
              homes={ homesToFilter }
              filters={ noFiltersApplied }
            />
          </MemoryRouter>,
        );
        const bostonInput = getByLabelText( 'Boston', { "selector": "input" } );
        act( () => {
          fireEvent.click( bostonInput );
        } );
        const homeToBeFilteredOut = queryByText( homeOutsideBoston.title );

        expect( bostonInput ).toBeChecked();
        expect( homeToBeFilteredOut ).not.toBeInTheDocument();
      } );

      it( 'Filters results to only homes outside Boston', () => {
        const homesToFilter = [homeWithinBoston, homeOutsideBoston];
        const { getByLabelText, queryByText } = render(
          <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
            <Search
              homes={ homesToFilter }
              filters={ noFiltersApplied }
            />
          </MemoryRouter>,
        );
        const beyondBostonInput = getByLabelText( 'Beyond Boston', { "selector": "input" } );
        act( () => {
          fireEvent.click( beyondBostonInput );
        } );
        const homeToBeFilteredOut = queryByText( homeWithinBoston.title );

        expect( beyondBostonInput ).toBeChecked();
        expect( homeToBeFilteredOut ).not.toBeInTheDocument();
      } );

      it( 'Sets all neighborhood checkboxes appropriately when the “Boston” checkbox is toggled', async () => {
        const { getByLabelText } = render(
          <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
            <Search filters={ noFiltersApplied } />
          </MemoryRouter>,
        );
        const bostonInput = getByLabelText( 'Boston', { "selector": "input" } );
        let dotInput;
        let southieInput;

        await act( async () => {
          dotInput = await waitForElement( () => getByLabelText( /Dorchester \(.*\)/, { "selector": "input" } ) );
          southieInput = await waitForElement( () => getByLabelText( /South Boston \(.*\)/, { "selector": "input" } ) );
        } );

        act( () => {
          fireEvent.click( bostonInput );
        } );

        expect( bostonInput ).toBeChecked();
        expect( dotInput ).toBeChecked();
        expect( southieInput ).toBeChecked();

        act( () => {
          fireEvent.click( bostonInput );
        } );

        expect( bostonInput ).not.toBeChecked();
        expect( dotInput ).not.toBeChecked();
        expect( southieInput ).not.toBeChecked();
      } );

      it( 'Sets all cardinal direction checkboxes appropriately when the “Beyond Boston” checkbox is toggled', async () => {
        const { getByLabelText } = render(
          <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
            <Search filters={ noFiltersApplied } />
          </MemoryRouter>,
        );
        const beyondBostonInput = getByLabelText( 'Beyond Boston', { "selector": "input" } );
        let westInput;
        let southInput;

        await act( async () => {
          westInput = await waitForElement( () => getByLabelText( /West of Boston \(.*\)/, { "selector": "input" } ) );
          southInput = await waitForElement( () => getByLabelText( /South of Boston \(.*\)/, { "selector": "input" } ) );

          // For some reason this fireEvent has to go inside act() or there is a race condition and the test fails,
          // even though it works outside of act() in the previous test
          fireEvent.click( beyondBostonInput );
        } );

        expect( beyondBostonInput ).toBeChecked();
        expect( westInput ).toBeChecked();
        expect( southInput ).toBeChecked();

        act( () => {
          fireEvent.click( beyondBostonInput );
        } );

        expect( beyondBostonInput ).not.toBeChecked();
        expect( westInput ).not.toBeChecked();
        expect( southInput ).not.toBeChecked();
      } );
    } );

    // Unit filtering

    describe( 'Bedrooms', () => {
      it( 'Filters by unit size', () => {
        const homeWithUnitsForEveryBedroomSize = {
          ...minimalHomeDefinition,
          "id": generateRandomNumberString(),
          "units": [
            studioUnit,
            oneBedroomUnit,
            twoBedroomUnit,
            threeBedroomUnit,
            fourBedroomUnit,
            aboveFourBedroomUnit,
          ],
        };
        const homesToFilter = [homeWithUnitsForEveryBedroomSize];
        const {
          getByLabelText, getByTestId, queryByTestId,
        } = render(
          <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
            <Search
              homes={ homesToFilter }
              filters={ noFiltersApplied }
            />
          </MemoryRouter>,
        );
        const zeroBedroomInput = getByLabelText( '0' );
        const twoBedroomInput = getByLabelText( '2' );
        const aboveThreeBedroomInput = getByLabelText( '3+' );

        act( () => {
          fireEvent.click( zeroBedroomInput );
        } );

        getByTestId( 'studio' );
        expect( queryByTestId( '1br' ) ).not.toBeInTheDocument();
        expect( queryByTestId( '2br' ) ).not.toBeInTheDocument();
        expect( queryByTestId( '3br' ) ).not.toBeInTheDocument();
        expect( queryByTestId( '3+br' ) ).not.toBeInTheDocument();

        act( () => {
          fireEvent.click( zeroBedroomInput );
          fireEvent.click( twoBedroomInput );
          fireEvent.click( aboveThreeBedroomInput );
        } );

        expect( queryByTestId( 'studio' ) ).not.toBeInTheDocument();
        expect( queryByTestId( '1br' ) ).not.toBeInTheDocument();
        getByTestId( '2br' );
        expect( queryByTestId( '3br' ) ).not.toBeInTheDocument();
        getByTestId( '3br' );
        getByTestId( '3+br' );
      } );
    } );

    describe( 'AMI Qualification', () => {
      it( 'Filters by AMI Percentage', () => {
        const amiBetweenEightyAndOneHundred = {
          ...noFiltersApplied,
          "amiQualification": {
            "lowerBound": 80,
            "upperBound": 100,
          },
        };
        const homeWithAmiAboveUpperBound = {
          ...minimalHomeDefinition,
          "id": generateRandomNumberString(),
          "offer": "rent",
          "units": [{
            ...studioUnit,
            "id": generateRandomNumberString(),
            "amiQualification": 110,
          }],
        };
        const homeWithAmiWithinBounds = {
          ...minimalHomeDefinition,
          "id": generateRandomNumberString(),
          "offer": "rent",
          "units": [{
            ...studioUnit,
            "id": generateRandomNumberString(),
            "amiQualification": 90,
          }],
        };
        const homeWithAmiBelowLowerBound = {
          ...minimalHomeDefinition,
          "id": generateRandomNumberString(),
          "offer": "rent",
          "units": [{
            ...studioUnit,
            "id": generateRandomNumberString(),
            "amiQualification": 70,
          }],
        };
        const homesToFilter = [homeWithAmiAboveUpperBound, homeWithAmiWithinBounds, homeWithAmiBelowLowerBound];
        const {
          getByTestId, queryByTestId,
        } = render(
          <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
            <Search
              homes={ homesToFilter }
              filters={ amiBetweenEightyAndOneHundred }
            />
          </MemoryRouter>,
        );

        getByTestId( homeWithAmiWithinBounds.units[0].id );
        expect( queryByTestId( homeWithAmiAboveUpperBound.units[0].id ) ).not.toBeInTheDocument();
        expect( queryByTestId( homeWithAmiBelowLowerBound.units[0].id ) ).not.toBeInTheDocument();
      } );
    } );

    describe( 'Income Qualification', () => {
      it( 'Hides income-restricted homes with limits higher than one’s household income', () => {
        const homesToFilter = [
          {
            ...minimalHomeDefinition,
            "id": generateRandomNumberString(),
            "units": [
              {
                ...oneBedroomUnit,
                "incomeQualification": 65000,
              },
              {
                ...twoBedroomUnit,
                "incomeQualification": null,
              },
            ],
          },
        ];

        const { queryByTestId, getByLabelText } = render(
          <LocalStorageMock items={ { "householdIncome": "$5,000.00", "incomeRate": "monthly" } }>
            <MemoryRouter initialEntries={['/metrolist/search']} initialIndex={0}>
              <Search homes={ homesToFilter } />
            </MemoryRouter>
          </LocalStorageMock>,
        );
        const incomeRestrictionFilterToggle = getByLabelText( /Hide homes that require a household income over \$[0-9,]+\/(mo|yr)\./, { "selector": "input" } );

        act( () => {
          fireEvent.click( incomeRestrictionFilterToggle );
        } );

        expect( queryByTestId( '1br' ) ).not.toBeInTheDocument();
        expect( queryByTestId( '2br' ) ).toBeInTheDocument();

        act( () => {
          fireEvent.click( incomeRestrictionFilterToggle );
        } );

        expect( queryByTestId( '1br' ) ).toBeInTheDocument();
        expect( queryByTestId( '2br' ) ).toBeInTheDocument();
      } );
    } );
  } );
} );
