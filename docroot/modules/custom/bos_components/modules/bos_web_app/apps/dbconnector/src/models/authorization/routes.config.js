const AuthorizationController = require('./authorization.controller');
const AuthValidationMiddleware = require('./auth.validation.middeware');
const VerifyUserMiddleware = require('./verify.user.middleware');
const PermissionMiddleware = require('./auth.permission.middleware');

exports.routesConfig = function (app) {

  app.post('/auth', [
    VerifyUserMiddleware.hasAuthValidFields,
    VerifyUserMiddleware.isPasswordAndUserMatch,
    PermissionMiddleware.isIPAddressAllowed,
    VerifyUserMiddleware.isUserEnabled,
    AuthValidationMiddleware.isFlooding,
    AuthorizationController.login
  ]);

  app.post('/auth/refresh', [
    AuthValidationMiddleware.validJWTNeeded,
    AuthValidationMiddleware.verifyRefreshBodyField,
    AuthValidationMiddleware.validRefreshNeeded,
    PermissionMiddleware.isIPAddressAllowed,
    AuthValidationMiddleware.isFlooding,
    AuthorizationController.login
  ]);
};
