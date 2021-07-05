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
    console.log("No Refresh_Token was supplied in body")
    return res.status(400).send({error: 'need to pass refresh_token field'});
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
  } else {
    return res.status(400).send({error: 'Invalid refresh token'});
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
        return res.status(401).send();
      }
      else {
        req.jwt = jwt.verify(authorization[1], secret);
        console.log("Token Valid")
        return next();
      }

    } catch (err) {
      if (err.toString().toLowerCase().includes("expired")) {
        return res.status(403).send({"error": `Token Expired (${jwtExpiration})`});
      }
      if (err.toString().toLowerCase().includes("invalid")) {
        return res.status(403).send({"error": "Bad Token"});
      }
      console.log(`err: ${err}`)
      return res.status(403).send();
    }
  } else {
    return res.status(401).send();
  }
};
