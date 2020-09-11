import { useLocation } from 'react-router-dom';
import { hasOwnProperty } from '@util/objects';

// https://stackoverflow.com/a/11764168/214325
export function paginate( homes, homesPerPage = 8 ) {
  const pages = [];
  let i = 0;
  const numberOfHomes = homes.length;

  while ( i < numberOfHomes ) {
    pages.push( homes.slice( i, i += homesPerPage ) );
  }

  return pages;
}

export function getQuery( location ) {
  return new URLSearchParams( location.search );
}

export function useQuery() {
  return new URLSearchParams( useLocation().search );
}

export function getPage( location ) {
  return parseInt( getQuery( location ).get( 'page' ), 10 );
}

export function filterHomes( {
  homesToFilter,
  filtersToApply,
  defaultFilters,
  matchOnNoneSelected = true,
} ) {
  const matchingHomes = homesToFilter
    .filter( ( home ) => {
      let matchesOffer = (
        (
          ( filtersToApply.offer.rent !== false )
          && ( home.offer === 'rent' )
        )
        || (
          ( filtersToApply.offer.sale !== false )
          && ( home.offer === 'sale' )
        )
      );

      let matchesBroadLocation = (
        (
          ( filtersToApply.location.city.boston !== false )
          && ( home.cardinalDirection === null )
        )
        || (
          ( filtersToApply.location.city.beyondBoston !== false )
          && ( home.cardinalDirection !== null )
        )
      );

      const neighborhoodIsPresentInFilters = (
        hasOwnProperty( filtersToApply.location.neighborhood, home.neighborhood )
        && ( filtersToApply.location.neighborhood[home.neighborhood] === true )
      );
      const cardinalDirectionIsPresentInFilters = (
        hasOwnProperty( filtersToApply.location.cardinalDirection, home.cardinalDirection )
        && ( filtersToApply.location.cardinalDirection[home.cardinalDirection] === true )
      );
      let matchesNarrowLocation = ( home.cardinalDirection === null ) ? neighborhoodIsPresentInFilters : cardinalDirectionIsPresentInFilters;

      const unitBedroomSizes = home.units.filter( ( unit ) => unit && unit.bedrooms ).map( ( unit ) => unit.bedrooms ).sort();
      let matchesBedrooms = unitBedroomSizes.length && (
        (
          ( filtersToApply.bedrooms['0'] === true )
          && ( unitBedroomSizes.indexOf( 0 ) !== -1 )
        )
        || (
          ( filtersToApply.bedrooms['1'] === true )
          && ( unitBedroomSizes.indexOf( 1 ) !== -1 )
        )
        || (
          ( filtersToApply.bedrooms['2'] === true )
          && ( unitBedroomSizes.indexOf( 2 ) !== -1 )
        )
        || (
          ( filtersToApply.bedrooms['3+'] === true )
          && ( unitBedroomSizes[unitBedroomSizes.length - 1] >= 3 )
        )
      );

      const dedupedAmi = new Set( home.units.filter( ( unit ) => unit && unit.amiQualification ).map( ( unit ) => unit.amiQualification ) );
      const unitAmiQualifications = Array.from( dedupedAmi );
      let matchesAmiQualification;

      if (
        ( home.incomeRestricted === false )
        || ( !unitAmiQualifications.length )
      ) {
        matchesAmiQualification = true;
      } else {
        for ( let index = 0; index < unitAmiQualifications.length; index++ ) {
          const amiQualification = ( unitAmiQualifications[index] || null );

          if ( amiQualification === null ) {
            matchesAmiQualification = true;
            break;
          }

          if ( filtersToApply.amiQualification.lowerBound <= filtersToApply.amiQualification.upperBound ) {
            matchesAmiQualification = (
              ( amiQualification >= filtersToApply.amiQualification.lowerBound )
              && ( amiQualification <= filtersToApply.amiQualification.upperBound )
            );
          // These values can be switched in the UI causing the names to no longer be semantic
          } else if ( filtersToApply.amiQualification.lowerBound > filtersToApply.amiQualification.upperBound ) {
            matchesAmiQualification = (
              ( amiQualification >= filtersToApply.amiQualification.upperBound )
              && ( amiQualification <= filtersToApply.amiQualification.lowerBound )
            );
          }

          if ( matchesAmiQualification ) {
            break;
          }
        }
      }

      if ( matchOnNoneSelected ) {
        if ( !filtersToApply.offer.rent && !filtersToApply.offer.sale ) {
          matchesOffer = true;
        }

        if ( !filtersToApply.location.city.boston && !filtersToApply.location.city.beyondBoston ) {
          matchesBroadLocation = true;
          matchesNarrowLocation = true;
        }

        if (
          !filtersToApply.bedrooms['0']
          && !filtersToApply.bedrooms['1']
          && !filtersToApply.bedrooms['2']
          && !filtersToApply.bedrooms['3+']
        ) {
          matchesBedrooms = true;
        }
      }

      return (
        matchesOffer
        && matchesBroadLocation
        && matchesNarrowLocation
        && matchesBedrooms
        && matchesAmiQualification
      );
    } )
    .map( ( home ) => {
      const newUnits = home.units
        .filter( ( unit ) => unit ) // Remove undefined, null, etc.
        .filter( ( unit ) => {
          let unitMatchesRentalPrice;

          if (
            filtersToApply.rentalPrice.upperBound
            && (
              ( home.offer === 'rent' )
              || ( home.type === 'apt' )
            )
          ) {
            let rentalPriceLowerBound;
            let rentalPriceUpperBound;

            if ( filtersToApply.rentalPrice.lowerBound > filtersToApply.rentalPrice.upperBound ) {
              rentalPriceLowerBound = filtersToApply.rentalPrice.upperBound;
              rentalPriceUpperBound = filtersToApply.rentalPrice.lowerBound;
            } else {
              rentalPriceLowerBound = filtersToApply.rentalPrice.lowerBound;
              rentalPriceUpperBound = filtersToApply.rentalPrice.upperBound;
            }

            unitMatchesRentalPrice = (
              ( unit.price >= rentalPriceLowerBound )
              && (
                ( unit.price <= rentalPriceUpperBound )
                // If the current upper bound is equal to the default upper bound
                // (which means it is all the way to the right on the slider),
                // change from “$XXX” to “$XXX+”—i.e. expand the scope of prices to include
                // values above the nominal maximum.
                || (
                  ( rentalPriceUpperBound === defaultFilters.rentalPrice.upperBound )
                  && ( unit.price >= rentalPriceUpperBound )
                )
              )
            );
          } else {
            unitMatchesRentalPrice = true;
          }

          let unitMatchesBedrooms = (
            (
              filtersToApply.bedrooms['0br']
              && ( unit.bedrooms === 0 )
            )
            || (
              filtersToApply.bedrooms['1br']
              && ( unit.bedrooms === 1 )
            )
            || (
              filtersToApply.bedrooms['2br']
              && ( unit.bedrooms === 2 )
            )
            || (
              filtersToApply.bedrooms['3+br']
              && ( unit.bedrooms >= 3 )
            )
          );

          if ( matchOnNoneSelected ) {
            if (
              !filtersToApply.bedrooms['0br']
              && !filtersToApply.bedrooms['1br']
              && !filtersToApply.bedrooms['2br']
              && !filtersToApply.bedrooms['3+br']
            ) {
              unitMatchesBedrooms = true;
            }
          }

          // TODO: Maybe exit early if we already know it is not a match?
          // if ( !unitMatchesBedrooms ) {
          //   return false;
          // }

          let unitMatchesAmiQualification;
          const unitAmiQualification = ( unit.amiQualification || null );

          if ( unitAmiQualification === null ) {
            unitMatchesAmiQualification = true;
          } else if ( filtersToApply.amiQualification.lowerBound <= filtersToApply.amiQualification.upperBound ) {
            unitMatchesAmiQualification = (
              ( unitAmiQualification >= filtersToApply.amiQualification.lowerBound )
              && ( unitAmiQualification <= filtersToApply.amiQualification.upperBound )
            );
          // These values can be switched in the UI causing the names to no longer be semantic
          } else if ( filtersToApply.amiQualification.lowerBound > filtersToApply.amiQualification.upperBound ) {
            unitMatchesAmiQualification = (
              ( unitAmiQualification >= filtersToApply.amiQualification.upperBound )
              && ( unitAmiQualification <= filtersToApply.amiQualification.lowerBound )
            );
          }

          let unitMatchesIncomeQualification;
          const unitIncomeQualification = ( unit.incomeQualification || null );

          if ( ( unitIncomeQualification === null ) || !filtersToApply.incomeQualification.upperBound ) {
            unitMatchesIncomeQualification = true;
          } else {
            unitMatchesIncomeQualification = ( unitIncomeQualification <= filtersToApply.incomeQualification.upperBound );
          }

          return ( unitMatchesRentalPrice && unitMatchesBedrooms && unitMatchesAmiQualification && unitMatchesIncomeQualification );
        } );

      return {
        ...home,
        "units": newUnits,
      };
    } )
    .filter( ( home ) => !!home.units.length );

  return matchingHomes;
}

export function getNewFilters( event, filters ) {
  const $input = event.target;
  let newValue;
  const newFilters = { ...filters };
  let valueAsKey = false;
  let isNumeric = false;
  let specialCase = false;
  let parent;
  let parentCriterion;

  switch ( $input.type ) {
    case 'checkbox':
      newValue = $input.checked;
      valueAsKey = true;
      break;

    default:
      newValue = $input.value;
  }

  if ( hasOwnProperty( event, 'metrolist' ) ) {
    if ( hasOwnProperty( event.metrolist, 'parentCriterion' ) ) {
      parentCriterion = event.metrolist.parentCriterion;

      switch ( parentCriterion ) { // eslint-disable-line default-case
        case 'amiQualification':
        case 'rentalPrice':
          isNumeric = true;
          break;
      }

      if ( isNumeric ) {
        newValue = Number.parseInt( newValue, 10 );
      }

      if ( parentCriterion !== $input.name ) {
        if ( valueAsKey ) {
          specialCase = true;
          parent = newFilters[parentCriterion][$input.name];
          parent[$input.value] = newValue;
        } else {
          specialCase = true;
          parent = newFilters[parentCriterion];
          parent[$input.name] = newValue;
        }
      }
    }
  }

  if ( !specialCase ) {
    // console.log( '!specialCase' );
    parent = newFilters[$input.name];
    parent[$input.value] = newValue;
  }

  switch ( $input.name ) {
    case 'neighborhood':
      if ( newValue && !filters.location.city.boston ) {
        newFilters.location.city.boston = newValue;
      }
      break;

    case 'cardinalDirection':
      if ( newValue && !filters.location.city.beyondBoston ) {
        newFilters.location.city.beyondBoston = newValue;
      }
      break;

    case 'bedrooms':
      newFilters.bedrooms[$input.value] = newValue;
      break;

    default:
  }

  // Selecting Boston or Beyond Boston checks/unchecks all subcategories
  switch ( $input.value ) {
    case 'boston':
      Object.keys( filters.location.neighborhood ).forEach( ( neighborhood ) => {
        newFilters.location.neighborhood[neighborhood] = newValue;
      } );
      break;

    case 'beyondBoston':
      Object.keys( filters.location.cardinalDirection ).forEach( ( cardinalDirection ) => {
        newFilters.location.cardinalDirection[cardinalDirection] = newValue;
      } );
      break;

    default:
  }

  return newFilters;
}
