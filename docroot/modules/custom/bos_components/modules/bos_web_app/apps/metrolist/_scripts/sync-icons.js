const fetch = require( 'node-fetch' );
const fs = require( 'fs' );

const endpoint = 'https://patterns.boston.gov/assets/manifest/icons_manifest.json';

console.log( 'Fetching icons manifest from patterns.boston.gov…' );

fetch( endpoint )
  .then( ( response ) => response.json() )
  .then( ( json ) => {
    const iconsByName = {};

    json
      .filter( ( iconEntry ) => ( iconEntry.category !== 'accessboston' ) )
      .filter( ( iconEntry ) => ( iconEntry.category !== 'drupal_icons' ) )
      .forEach( ( iconEntry ) => {
        const iconId = iconEntry.title;
        delete iconEntry.filename;
        delete iconEntry.title;
        delete iconEntry.directory;
        delete iconEntry.ext;
        iconEntry.url = iconEntry.url.replace( 'https://assets.boston.gov', '' );
        iconEntry.category = iconEntry.category.replace( ' ', '_' );
        iconsByName[iconId] = iconEntry;
      } );

    fs.writeFile(
      'src/components/Icon/icons_manifest.json',
      JSON.stringify( iconsByName ),
      // JSON.stringift( iconsByName, null, 2 ),
      'utf8',
      ( writeFileError ) => {
        if ( !writeFileError ) {
          console.log( 'Updated icons manifest.' );
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
    process.exit( 1 );
  } );
