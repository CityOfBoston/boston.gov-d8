
const listen_port = require('../common/env.config.js').port;

const express = require('express');

const app = express();
const bodyParser = require('body-parser');

// Include the models routing files.
const HealthRouter = require('../models/health/routes.config');
const AuthorizationRouter = require('../models/authorization/routes.config');
const UsersRouter = require('../models/users/routes.config');
const ConnectionsRouter = require('../models/connections/routes.config');
const ProxyRouter = require('../models/proxies/routes.config');

app.use(function (req, res, next) {
  res.set('Cache-Control', 'no-store');
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Credentials', 'true');
  res.header('Access-Control-Allow-Methods', 'GET,HEAD,PUT,PATCH,POST,DELETE');
  res.header('Access-Control-Expose-Headers', 'Content-Length');
  res.header('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, X-Requested-With, Range');
  if (req.method === 'OPTIONS') {
    return res.sendStatus(200);
  } else {
    return next();
  }
});

app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());

app.set('trust proxy', true)

// Create the endpoints from the models routing files.
HealthRouter.routesConfig(app);
AuthorizationRouter.routesConfig(app);
UsersRouter.routesConfig(app);
ConnectionsRouter.routesConfig(app);
ProxyRouter.routesConfig(app);

// Start the express server service.
try {
  app.listen(listen_port, function () {
    console.log('app listening at port %s', listen_port);
  });
}
catch(err) {
  console.log("app starting error: " + err);
}
