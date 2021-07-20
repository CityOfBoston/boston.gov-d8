/**
 * Functions which check the authenticated users role (stored in req.jwt or req.body)
 *
 * These functions are intended to be used in an express.js app (i.e. rest API endpoint).
 *
 * Sucessful permission (role) verification results in the express next() directive being called.
 * Failures return an error state in the response (res) object which (should) cause the endpoint
 * functions to terminate and return an error HTTP_State back to the caller.
 *
 * The token is validated at auth.validation.middleware.js:validJWTNeeded() and
 * the stored user info is loaded the req.jwt.
 */
const jwt = require('jsonwebtoken'),
  secret = require('../../common/env.config')['jwt_secret'],
  flood_time = require('../../common/env.config')['flood_time'],
  flood_level = require('../../common/env.config')['flood_level'];
const UserModel = require('../users/users.model');
const Output = require('../../common/json.responses');

// Roles are defined in env.config.js.
const config = require('../../common/env.config');
const ADMIN = config.permissionLevels.ADMIN_USER;
const OWNER = config.permissionLevels.OWNER;

/**
 * Checks if the current user has a role greater than or equal to the required_role.
 * @param  {Number} required_role as defined in env.config.js.
 * @return {any} calls next function, or returns error message in response object.
 */
exports.minimumPermissionLevelRequired = (required_role) => {
  return (req, res, next) => {
    let user_role = parseInt(req.jwt.role);
    required_role = parseInt(required_role);
    if (user_role == OWNER || user_role >= required_role) {
      // console.log("Min Role Verified")
      return next();
    } else {
      // console.log("Role Failed")
      return Output.json_response(res, 403)
    }
  };
};

/**
 * Checks if the current user has the role provided in the array of acceptable roles.
 * @param  {Array} allowed_roles An array of roles. The authenticated user must have one of these roles.
 * @return {any} calls next function, or returns error message in response object.
 */
exports.PermissionLevelRequired = (allowed_roles) => {
  return (req, res, next) => {
    let user_role = parseInt(req.jwt.role);

    // Validate the allowed_roles. It must be an array or an integer.
    if (typeof allowed_roles === "number") {
      allowed_roles = [allowed_roles];
    }
    else if (!Array.isArray(allowed_roles)) {
      // console.log("Unexpected role argument format")
      return Output.json_response(res, 400, {"error": "Internal: Bad Role provided"});
    }

    // Add (sum) the roles so we can use a bitwise comparison.
    let sum_roles = 0
    allowed_roles.forEach(role => {
      sum_roles += parseInt(role);
    });

    if (sum_roles == 0) {
      // console.log("No Roles Allowed")
      return Output.json_response(res, 403);
    }
    else if (user_role == OWNER || user_role & sum_roles) {
      return next();
    }
    else {
      // console.log("Role Failed")
      return Output.json_response(res, 403);
    }
  };
};

/**
 * Checks if the current user is the user_id passed in req.params.userId, or is an Admin/Owner.
 * @param  {Request} req The current session request object
 * @param  {Response} req The current session response object
 * @param  {Function} next
 * @return {any} calls next function, or returns error message in response object.
 */
exports.onlySameUserOrAdminCanDoThisAction = (req, res, next) => {
  let user_role = parseInt(req.jwt.role);
  let userId = parseInt(req.jwt.userid);
  // console.log(userId + " | " + req.params.userId);
  if (req.params && req.params.userId && userId === parseInt(req.params.userId)) {
    return next();
  } else {
    if (user_role == ADMIN || user_role == OWNER) {
      return next();
    } else {
      // console.log("User is not the specified user nor has Admins Role")
      return Output.json_response(res, 403);
    }
  }

};

/**
 * Prevents current user from performing an action if they are the user passed in req.params.userId.
 * @param  {Request} req The current session request object
 * @param  {Response} req The current session response object
 * @param  {Function} next
 * @return {any} calls next function, or returns error message in response object.
 */
 exports.sameUserCantDoThisAction = (req, res, next) => {
  let userId = req.jwt.userid;

  if (req.params.userId !== userId) {
    return next();
  }
  else {
    // console.log("User is prevented from this action")
    return Output.json_response(res, 403);
  }

};

/**
 * Checks if this token is being used from one of the users allowed IPAddresses.
 * @param  {Request} req The current session request object
 * @param  {Response} req The current session response object
 * @param  {Function} next
 * @return {any} calls next function, or returns error message in response object.
 */
exports.isIPAddressAllowed = (req, res, next) => {

  let allowed_ips = "";

  if (req.jwt && 'ipaddresses' in req.jwt) {
    // This is being called after auth.validation.middeware.js;validJWTNeeded().
    allowed_ips = req.jwt.ipaddresses;
  }
  else if (req.body && 'ipaddresses' in req.body) {
    // This is being called after verify.user.middleware:isPasswordAndUserMatch().
    allowed_ips = req.body.ipaddresses;
  }
  else {
    // Have not run a process which determines the ipaddresses allowed.
    return Output.json_response(res, 500, {error: 'JWT not initialized'});
  }

  if (allowed_ips != "") {

    allowed_ips = allowed_ips.join(";").replace(/localhost/gi, "127.0.0.1").split(";");
    let caller_ip = req.ip.replace(/localhost/gi, "127.0.0.1");

    if (allowed_ips.includes(caller_ip)) {
      // console.log(`IPAddress ${caller_ip} OK`)
      return next()
    }
    // console.log(`IPAddress ${caller_ip} Failed`)
    return Output.json_response(res, 403);
  }
  else {
    // No IPAddress filtering.
    // console.log(`IPAddress ${req.ip} OK - no filter`)
    return next();
  }
};

/**
 * Records a request for this user, and checks for Flood/DDOS style abuse.
 * @param {*} req
 * @param {*} res
 * @param {*} next
 */
exports.monitorForFlood = (req, res, next) => {
  try {
    // Increment and manage 30 second "blocks" for this user.
    let block = Math.round((new Date().getTime / 1000) / flood_time);

    if (block in req.session.flood[req.body.userid]) {
      // Block already exists, so increment its count
      req.session.flood[req.body.userid][block]++
      // Check if "old blocks" exist, and if so delete them...
      if (Object.keys(req.session.flood[req.body.userid]).length > 1) {
        // Re-initialize user with the current block and counter - removes any old blocks.
        req.session.flood[req.body.userid] = {block: req.session.flood[req.body.userid][block]};
      }
      // Now check we aren't being abused.
      if (req.session.flood[req.body.userid][block] > flood_level) {
        // Oh dear, ...
        console.log("User abusing API");
        // Disable the User record in the DB.
        UserModel.disableById(req.jwt.userId)
          .then((result) => {
            // Kill the JWT Token.
            delete req.jwt;
            // Die quietly.
            return Output.json_response(res, 401);
          });
      }
    }
    else {
      // The current time block does not exist.
      // Create the block, set count to 1.
      // This will remove any old blocks.
      req.session.flood[req.body.userid] = {block: 1};
    }
  }
  catch (err) {
    // console.log(err)
    return Output.json_response(res, 400, {error: err});
  }
  finally {
    next();
  }
}
