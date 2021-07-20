/**
 * Functions which validate aspects of the JWT Token
 *
 * These functions are intended to be used in an express.js app (i.e. rest API endpoint).
 *
  */
const jwt = require('jsonwebtoken'),
  secret = require('../../common/env.config').jwt_secret,
  jwtExpiration = require('../../common/env.config').jwt_expiration_in_seconds,
  crypto = require('crypto');
const flood_time = require('../../common/env.config').flood_time,
  flood_level = require('../../common/env.config').flood_level
  const Output = require('../../common/json.responses');


/**
 * Checks that a refresh token has been supplied in the request body.
 * @param  {Request} req The current session request object
 * @param  {Response} req The current session response object
 * @param  {Function} next
 * @return {any} calls next function, or returns 400 error message in response object.
 */
exports.verifyRefreshBodyField = (req, res, next) => {
  if (req.body && req.body.refresh_token) {
    return next();
  }
  else {
    return Output.json_response(res, 400, {error: 'Missing refresh token'});
  }
};

/**
 * Checks that the refresh token matches a Token in the JWT. Loads the token into req.body.
 * @param  {Request} req The current session request object
 * @param  {Response} req The current session response object
 * @param  {Function} next
 * @return {any} calls next function, or returns 400 error message in response object.
 */
exports.validRefreshNeeded = (req, res, next) => {
  let b = Buffer.from(req.body.refresh_token, 'base64');
  let refresh_token = b.toString();
  let hash = crypto.createHmac('sha512', req.jwt.refreshKey).update(req.jwt.userid + secret).digest("base64");
  if (hash === refresh_token) {
    req.body = req.jwt;
    return next();
  }
  else {
    return Output.json_response(res, 400, {error: 'Invalid refresh token'});
  }
};

/**
 * Checks that the supplied JWT Token is valid. Loads user details into req.jwt object.
 * @param  {Request} req The current session request object
 * @param  {Response} req The current session response object
 * @param  {Function} next
 * @return {any} calls next function, or returns 401/403 error message in response object.
 */
exports.validJWTNeeded = (req, res, next) => {
  if (req.headers['authorization']) {
    try {
      let authorization = req.headers['authorization'].split(' ');
      if (authorization[0] !== 'Bearer') {
        return Output.json_response(res, 401);
      }
      else {
        req.jwt = jwt.verify(authorization[1], secret);
        return next();
      }

    }
    catch (err) {
      if (err.toString().toLowerCase().includes("expired")) {
        return Output.json_response(res, 401, {error: 'Expired Token'});
      }
      if (err.toString().toLowerCase().includes("invalid")) {
        return Output.json_response(res, 403, {error: 'Bad Token'});
      }
      return Output.json_response(res, 400, {error: err});
    }
  }
  else {
    return Output.json_response(res, 401, {error: "Missing Authentication Token"});
  }
};

function readUserSession(userid) {

}

/**
 * Makes sure a flood block is created, and then increments it.
 * @param {*} req Request object
 * @param {number} block The time-block to increment.
 * @param {object} sess The jwt session object.
 */
function incrementFloodCounter(block, sess) {

  if (typeof sess.session === "undefined" || sess.session == null) {
    // console.log("recreate_1");
    sess.session = {'flood': {}};
  }
  else if (typeof sess.session.flood === "undefined") {
    // console.log("recreate_2");
    sess.session.flood = {};
  }

  let flood = sess.session.flood;

  if (block in flood) {
    // Increment this block for this user.
    // console.log("existing block");
    flood[block]++;
  }
  else {
    // Initializes new block for this user and remove any old blocks.
    // console.log("new block");
    flood[block] = 1;
  }
  // console.log(sess);
  return sess;

};

/**
 * Increments the flood tracker, and checks for flood attacks.
 *
 * @param {*} req
 * @param {*} res
 * @param {*} next
 * @returns
 */
exports.isFlooding = (req, res, next) => {

  // Establish which time block we are in.
  let block = Math.round((new Date().getTime() / 1000) / flood_time);

  //Manage the flood counter.
  if (typeof req.jwt === "undefined") {
    // During authentication, prior to login, user info is in the body.
    req.body = incrementFloodCounter(block, req.body);
    use_count = parseInt(req.body.session.flood[block]);
  }
  else {
    req.jwt = incrementFloodCounter(block, req.jwt);
    use_count = parseInt(req.jwt.session.flood[block]);
  }

  // Determine if user is over-using service.
  if (use_count <= flood_level) {
    next();
  }
  else {
    // Return a simple 200, do not alert the flooder to the issue.
    // console.log(`hits: ${use_count}`)
    // console.log(`level: ${flood_level}`)
    return Output.json_response(res, 200, {error: "No Data"});
  }

  if (typeof req.jwt !== "undefined") {
    // console.log("jwt: " + JSON.stringify(req.jwt));
  }

};
