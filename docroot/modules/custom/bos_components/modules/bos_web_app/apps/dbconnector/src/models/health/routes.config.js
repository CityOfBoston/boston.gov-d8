const HealthController = require('./health.controller');

exports.routesConfig = function (app) {
  // Lists all users.
  app.get('/admin/ok', [
    HealthController.ping
  ]);
};
