const UsersController = require('./users.controller');
const ValidationMiddleware = require('../authorization/auth.validation.middeware');
const PermissionMiddleware = require('../authorization/auth.permission.middleware');
const config = require('../../common/env.config');

const ADMIN = config.permissionLevels.ADMIN_USER;
const SUPER = config.permissionLevels.SUPER_USER;
const NORMAL = config.permissionLevels.NORMAL_USER;
const OWNER = config.permissionLevels.OWNER;

exports.routesConfig = function (app) {
  // Inserts a new user.
  app.post('/users', [
    ValidationMiddleware.validJWTNeeded,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),
    UsersController.insert
  ]);
  // Lists all users.
  app.get('/users', [
    ValidationMiddleware.validJWTNeeded,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),
    UsersController.list
  ]);
  // Fetch a single user from userID supplied.
  app.get('/users/:userId', [
    ValidationMiddleware.validJWTNeeded,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.onlySameUserOrAdminCanDoThisAction,
    UsersController.get
  ]);
  // Update a single user.
  app.patch('/users/:userId', [
    ValidationMiddleware.validJWTNeeded,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.onlySameUserOrAdminCanDoThisAction,
    UsersController.update
  ]);
  // Disable a single user.
  app.delete('/users/:userId', [
    ValidationMiddleware.validJWTNeeded,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),
    UsersController.disable
  ]);
};
