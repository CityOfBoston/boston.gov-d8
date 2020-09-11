const fetch = require( 'node-fetch' );
const fs = require( 'fs' );

const serverEnvironmentId = 'dev';
let origin;

switch ( serverEnvironmentId ) {
  case 'www':
  case 'prod':
    origin = 'https://www.boston.gov';
    break;

  default:
    origin = `https://d8-${serverEnvironmentId}.boston.gov`;
}

console.log( `Fetching HUD AMI definitions from ${origin.replace( 'https://', '' )}…` );

const endpointPath = '/metrolist/api/v1/ami/hud/base?_format=json';
const endpoint = `${origin}${endpointPath}`;

fetch( endpoint )
  .then( ( response ) => response.json() )
  .then( ( amiDefinitions ) => {
    fs.writeFile(
      'src/components/AmiEstimator/ami-definitions.json',
      JSON.stringify( amiDefinitions ),
      'utf8',
      ( writeFileError ) => {
        if ( !writeFileError ) {
          console.log( 'Updated AMI defintions.' );
          process.exit( 0 );
        } else {
          console.error( writeFileError );
          process.exit( 1 );
        }
      },
    );
  } )
  .catch( ( error ) => {
    console.error( error );
  } );
