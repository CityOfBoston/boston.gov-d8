const ProxyController = require('./proxy.controller');
const ValidationMiddleware = require('../authorization/auth.validation.middeware');
const PermissionMiddleware = require('../authorization/auth.permission.middleware');
const config = require('../../common/env.config');

const ADMIN = config.permissionLevels.ADMIN_USER;
const SUPER = config.permissionLevels.SUPER_USER;
const NORMAL = config.permissionLevels.NORMAL_USER;
const OWNER = config.permissionLevels.OWNER;

exports.routesConfig = function (app) {
  /* Payload in the format
  *  {
  *     'statement': 'statement',
  *     'connectionString': ''
  *     'args': [],
  *   }
  */
  app.post('/:driver/query', [
    ValidationMiddleware.validJWTNeeded,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),
    ProxyController.query
  ]);

  /* Payload in the format
  *  {
  *     'limit': 0,     // <- Rows per page
  *     'page': 0,      // <- Return page #
  *     'select statement': 'select statement',
  *     'connectionString': ''
  *     'args': [],
  *   }
  * querystring ?limit=N&page=N
  */
  app.post('/:driver/select', [
    ValidationMiddleware.validJWTNeeded,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),
    ProxyController.select
  ]);

};
