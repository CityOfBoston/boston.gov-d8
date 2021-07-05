/**
 * Functions which check that the connections are allowed to be used by this user.
 */
const ConnModel = require('./connections.model');

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
  if (true) {
    next();
  }
  else {
   return res.status(401).send();
  }
}

