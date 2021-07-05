const VerifyUserMiddleware = require('./verify.user.middleware');
const AuthorizationController = require('./authorization.controller');
const AuthValidationMiddleware = require('./auth.validation.middeware');
const PermissionMiddleware = require('../authorization/auth.permission.middleware');
exports.routesConfig = function (app) {

  app.post('/auth', [
    VerifyUserMiddleware.hasAuthValidFields,
    VerifyUserMiddleware.isPasswordAndUserMatch,
    PermissionMiddleware.isIPAddressAllowed,
    VerifyUserMiddleware.isUserEnabled,
    AuthorizationController.login
  ]);

  app.post('/auth/refresh', [
    AuthValidationMiddleware.validJWTNeeded,
    PermissionMiddleware.isIPAddressAllowed,
    AuthValidationMiddleware.verifyRefreshBodyField,
    AuthValidationMiddleware.validRefreshNeeded,
    AuthorizationController.login
  ]);
};
