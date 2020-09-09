import React from 'react';
import PropTypes from 'prop-types';

import Home from '@components/Home';
import Stack from '@components/Stack';
import Inset from '@components/Inset';

import { homeObject } from '@util/validation';

import './ResultsPanel.scss';

function ResultsPanel( props ) {
  const {
    homes, className, columnWidth, filters,
  } = props;
  const attributes = { ...props };

  if ( homes && ( homes.length > 0 ) ) {
    delete attributes.homes;
  }

  if ( columnWidth ) {
    delete attributes.columnWidth;
    attributes['data-column-width'] = columnWidth;
  }

  if ( filters ) {
    delete attributes.filters;
  }

  return (
    <article
      data-testid="ml-results-panel"
      className={ `ml-results-panel${className ? ` ${className}` : ''}` }
      { ...attributes }
    >
      <h3 className="sr-only">Results</h3>
      <Inset until="large">
        <Stack space="panel">
        {
          homes && ( homes.length )
            ? homes.map( ( home ) => <Home key={ home.id } home={ home } filters={ filters } /> )
            : <p>No homes match the selected filters.</p>
        }
        </Stack>
      </Inset>
    </article>
  );
}

ResultsPanel.propTypes = {
  "homes": PropTypes.arrayOf( homeObject ),
  "columnWidth": PropTypes.string,
  "className": PropTypes.string,
  "filters": PropTypes.object,
};

export default ResultsPanel;
