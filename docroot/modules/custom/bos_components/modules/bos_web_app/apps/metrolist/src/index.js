// @babel/polyfill equivalent
// import "core-js/stable";
// import "regenerator-runtime/runtime";
import '@babel/polyfill';

import React from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import ScrollToTop from '@components/ScrollToTop';

import App from './components/App';
import './index.scss';

import '@globals/util.scss';

ReactDOM.render(
  <Router>
    <ScrollToTop />
    <App />
  </Router>,
  document.getElementById( 'web-app' ),
);
