

const ConnController = require('./connections.controller');
const ValidationMiddleware = require('../authorization/auth.validation.middeware');
const PermissionMiddleware = require('../authorization/auth.permission.middleware');
const ConnPermissionMiddleware = require('./connections.permission.middleware');
const config = require('../../common/env.config');

const ADMIN = config.permissionLevels.ADMIN_USER;
const SUPER = config.permissionLevels.SUPER_USER;
const NORMAL = config.permissionLevels.NORMAL_USER;
const OWNER = config.permissionLevels.OWNER;

exports.routesConfig = function (app) {
  // Inserts a new connection.
  app.post('/connection', [
    ValidationMiddleware.validJWTNeeded,
    ValidationMiddleware.isFlooding,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),
    ConnController.insert
  ]);
  // Lists all connections.
  app.get('/connections', [
    ValidationMiddleware.validJWTNeeded,
    ValidationMiddleware.isFlooding,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(ADMIN),
    ConnController.list
  ]);
  // Fetch a single connection from Token supplied.
  app.get('/connections/:token', [
    ValidationMiddleware.validJWTNeeded,
    ValidationMiddleware.isFlooding,
    PermissionMiddleware.isIPAddressAllowed,
    ConnPermissionMiddleware.canThisUserUseThisConnection,
    ConnController.get
  ]);
  // Update a single connection.
  app.patch('/connections/:token', [
    ValidationMiddleware.validJWTNeeded,
    ValidationMiddleware.isFlooding,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(SUPER),
    ConnController.update
  ]);
  // Disable a single connection.
  app.delete('/connections/:token', [
    ValidationMiddleware.validJWTNeeded,
    ValidationMiddleware.isFlooding,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(SUPER),
    ConnController.disable
  ]);

/**
 * MAPPING CONNECTIONS <=> USERS
 */

  // List all connections available to a user
  app.get('/users/:userId/connections', [
    ValidationMiddleware.validJWTNeeded,
    ValidationMiddleware.isFlooding,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.onlySameUserOrAdminCanDoThisAction,
    ConnController.getUserConnections
  ]);
  // List users who are allowed to use a connToken.
  app.get('/connection/:token/users', [
    ValidationMiddleware.validJWTNeeded,
    ValidationMiddleware.isFlooding,
    PermissionMiddleware.isIPAddressAllowed,
    ConnPermissionMiddleware.canThisUserUseThisConnection,
    ConnController.getConnectionUsers
  ]);
  // Inserts a new connection->user mapping.
  app.post('/connection/:token/user/:userid', [
    ValidationMiddleware.validJWTNeeded,
    ValidationMiddleware.isFlooding,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(SUPER),
    ConnController.insertMapping
  ]);
  // Deletes a connection->user mapping.
  app.delete('/connection/:token/user/:userid', [
    ValidationMiddleware.validJWTNeeded,
    ValidationMiddleware.isFlooding,
    PermissionMiddleware.isIPAddressAllowed,
    PermissionMiddleware.minimumPermissionLevelRequired(SUPER),
    ConnController.deleteMapping
  ]);

};
