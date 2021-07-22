const jwtSecret = require('../../common/env.config').jwt_secret,
  jwt = require('jsonwebtoken');
let jwtExpiration = require('../../common/env.config').jwt_expiration_in_seconds;
const crypto = require('crypto');
const Output = require('../../common/json.responses');

const updateExpiration = (body) => {
  // Update thedefault expiration with the users ttl(if any).
  if ('ttl' in body && body.ttl.toString() != "") {
    jwtExpiration = body.ttl;
  }
};

/**
 * Adds time to now and returns that time in seconds since epoch.
 *
 * @param {string} timestring
 * @returns {number} (int) timestring offset from current time in seconds since epoch
 */
const setExpiryTime = (timestring) => {
  let exp = 0;

  regex = /h(our)?(s)?/i;
  let ts = timestring.replace(regex, '*');
  if (ts.includes("*")) {
    ts.split("*").forEach(val => {
      if (!isNaN(val) && val != "") {
        exp = exp + (parseInt(val) * 60 * 60 * 1000);
      }
    });
  }

  regex = /m(in)?(ute)?(s)?/i;
  ts = ts.replace(regex, '%');
  if (ts.includes("%")) {
    ts.split("%").forEach(val => {
      if (!isNaN(val) && val != "") {
        exp = exp + (parseInt(val) * 60 * 1000);
      }
    });
  }

  regex = /s(ec)?(ond)?(s)?/i;
  ts = ts.replace(regex, '#');
  if (ts.includes("#")) {
    ts.split("#").forEach(val => {
      if (!isNaN(val) && val != "") {
        exp = exp + (parseInt(val) * 1000);
      }
    });
  }

  var a = new Date();
  return parseInt((a.getTime() + exp) / 1000);
}

exports.login = (req, res) => {
  try {
    let refreshId = req.body.userid + jwtSecret;
    let salt = crypto.randomBytes(16).toString('base64');
    let hash = crypto.createHmac('sha512', salt).update(refreshId).digest("base64");
    let token;
    req.body.refreshKey = salt;
    if ('exp' in req.body) {
      // This is login using a refresh Token.
      // console.log(setExpiryTime(jwtExpiration));
      req.body.exp = setExpiryTime(jwtExpiration);
      token = jwt.sign(req.body, jwtSecret);
    }
    else {
      // This is a login using the supplied username/password
      updateExpiration(req.body);
      // console.log("Expires: " + req.body.userid + "|" + jwtExpiration);
      token = jwt.sign(req.body, jwtSecret, { expiresIn: jwtExpiration });
    }
    let b = Buffer.from(hash);
    let refresh_token = b.toString('base64');

    return Output.json_response(res, 200, {userid: req.body.userid, authToken: token, refreshToken: refresh_token});
  }
  catch (err) {
    // console.log(err);
    return Output.json_response(res, 400, {error: err});
  }
};

exports.refresh_token = (req, res) => {
  try {
    req.body = req.jwt;
    console.log("JWT: ", req.jwt);

    // console.log("Expires: " + req.body.userid + "|" + jwtExpiration);
    // var a = new Date(req.body.exp);
    // req.body.exp =new Date(a.getTime + (10 * 1000));

    updateExpiration(req.body);

    let token = jwt.sign(req.body, jwtSecret, { expiresIn: jwtExpiration });
    console.log("JWT2: ", req.jwt);

    // let refreshId = req.body.userid + jwtSecret;
    // let hash = crypto.createHmac('sha512', req.body.refreshKey).update(refreshId).digest("base64");
    // let b = Buffer.from(hash);
    // let refresh_token = b.toString('base64');
    let refresh_token = "a";

    return Output.json_response(res, 200, { userid: req.body.userid, authToken: token, refreshToken: refresh_token});
  }
  catch (err) {
    console.log(err)
    return Output.json_response(res, 400, {error: err});
  }
};

// curl -H "Content-Type: application/json" -d '{"username":"david.upton@boston.gov", "password": "123"}' http://localhost:3600/auth
