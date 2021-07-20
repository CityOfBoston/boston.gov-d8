const UserModel = require('../users/users.model');
const crypto = require('crypto');
const Output = require('../../common/json.responses');

exports.hasAuthValidFields = (req, res, next) => {
  let errors = [];

  if (req.body) {
    if (!req.body.username) {
      errors.push('Missing username/email field');
    }
    if (!req.body.password) {
      errors.push('Missing password field');
    }

    if (errors.length) {
      return Output.json_response(res, 400, {error: errors.join(", ")});
    }
    else {
      return next();
    }
  } else {
    return Output.json_response(res, 400, {error: 'Missing username/email and password fields'});
  }
};

/**
 * This function calls the database and compares supplied username and password with DB values.
 * @param {*} req
 * @param {*} res
 * @param {*} next
 */
exports.isPasswordAndUserMatch = (req, res, next) => {
  UserModel.findByUsername(req.body.username)
    .then((user) => {
      // console.log(user);
      if (!user || user == [] || !user[0]){
        return Output.json_response(res, 400, {error: "Invalid username or password"});
      }
      else{
        let passwordFields = user[0].Password.split('$');
        let salt = passwordFields[0];
        let hash = crypto.createHmac('sha512', salt).update(req.body.password).digest("base64");
        if (hash === passwordFields[1]) {
          req.body = {
            userid: user[0].ID,
            username: user[0].Username,
            role: user[0].Role,
            enabled: user[0].Enabled,
            ipaddresses: user[0].IPAddresses.split(";") || "",
            session: user[0].Session
          };
          return next();
        }
        else {
          return Output.json_response(res, 400, {error: 'Invalid username or password'});
        }
      }
    })
    .catch((reason) => {
      return Output.json_response(res, 400, {error: reason});
    });
};

exports.isUserEnabled = (req, res, next) => {
  if (req.body.enabled) {
    return next()
  }
  else {
    return Output.json_response(res, 401, {error: "User Disabled"});
  }
};
