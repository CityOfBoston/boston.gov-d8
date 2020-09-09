import React from 'react';
import PropTypes from 'prop-types';
import {
  Switch, Route,
  useLocation,
} from 'react-router-dom';


import Search from '@components/Search';
import AmiEstimator from '@components/AmiEstimator';

import {
  resolveLocationConsideringGoogleTranslate,
  // switchBackToMetrolistBaseIfNeeded,
} from '@util/translation';

import './Routes.scss';

function Routes() {
  return (
    <Switch location={ resolveLocationConsideringGoogleTranslate( useLocation() ) }>
      <Route path="/metrolist/search">
        <Search />
      </Route>
      <Route path="/metrolist/ami-estimator">
        <AmiEstimator />
      </Route>
      <Route exact path="/metrolist/">
        <article>
          <div className="hro hro--t">
            <div className="hro-c">
              <div className="hro-i hro-i--l">Welcome to the new</div>
              <h2 className="hro-t hro-t--l">Homepage</h2>
            </div>
          </div>
          <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. In voluptatibus nisi minima obcaecati, at facilis, et quos maiores ad provident qui. Quos libero culpa ad. Alias corporis ipsum sequi commodi?</p>
        </article>
      </Route>
    </Switch>
  );
}

Routes.displayName = 'Routes';

Routes.propTypes = {
  "children": PropTypes.node,
  "className": PropTypes.string,
};

export default Routes;
