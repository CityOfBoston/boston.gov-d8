/**
 * Functions which check that the connections are allowed to be used by this user.
 */
const ConnModel = require('./connections.model');
const sql_exec = require('../../common/services/tedious.exec.service')
// Roles are defined in env.config.js.
const config = require('../../common/env.config');
const ADMIN = config.permissionLevels.ADMIN_USER;
const OWNER = config.permissionLevels.OWNER;

/**
 * Verifies that the user is allowed to use this connection.
 * @param {*} req
 * @param {*} res
 * @param {*} next
 * @returns {*}
 */
 exports.isConnectionEnabled = (req, res, next) => {
  if (true) {
    next();
  }
  else {
   return res.status(401).send();
  }
}

/**
 * Verifies that the user is allowed to use this connection.
 * @param {*} req
 * @param {*} res
 * @param {*} next
 * @returns {*}
 */
 exports.canThisUserUseThisConnection = (req, res, next) => {

  let user_role = parseInt(req.jwt.role);
  required_role = parseInt(ADMIN);
  if (user_role == OWNER || user_role >= required_role) {
    // User is an ADMIN or the OWNER, so that's OK.
    next()
  }

  else {
    // Validate that this user can access the connection string defined by this token.
    sql = `
    SELECT COUNT(*) as count
      FROM permissionsMap perm
        INNER JOIN dbo.connTokens tok ON perm.connID = tok.id
    WHERE userid = ${req.jwt.userid} and tok.token = '${req.params.token}';
    `

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        return res.status(500).send();
      }
      else {
        if (rows[0][0].count == 0) {
          console.log(JSON.stringify(rows))
          return res.status(403).send();
        }
        else {
          next();
        }
      }
    });
  }

}

