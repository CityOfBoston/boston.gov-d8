const jwtSecret = require('../../common/env.config').jwt_secret,
  jwtExpiration = require('../../common/env.config').jwt_expiration_in_seconds,
  jwt = require('jsonwebtoken');
const crypto = require('crypto');
const Output = require('../../common/json.responses');

exports.login = (req, res) => {
  try {
    let refreshId = req.body.userid + jwtSecret;
    let salt = crypto.randomBytes(16).toString('base64');
    let hash = crypto.createHmac('sha512', salt).update(refreshId).digest("base64");
    // console.log(JSON.stringify(req.body));
    let token;
    req.body.refreshKey = salt;
    if ('exp' in req.body) {
      token = jwt.sign(req.body, jwtSecret);
    }
    else {
      token = jwt.sign(req.body, jwtSecret, { expiresIn: jwtExpiration });
    }
    let b = Buffer.from(hash);
    let refresh_token = b.toString('base64');

    return Output.json_response(res, 201, {userid: req.body.userid, accessToken: token, refreshToken: refresh_token});
  }
  catch (err) {
    // console.log(err);
    return Output.json_response(res, 400, {error: err});
  }
};

exports.refresh_token = (req, res) => {
  try {
    req.body = req.jwt;
    let token = jwt.sign(req.body, jwtSecret);
    return Output.json_response(res, 201, {id: token});
  }
  catch (err) {
    return Output.json_response(res, 400, {error: err});
  }
};

// curl -H "Content-Type: application/json" -d '{"username":"david.upton@boston.gov", "password": "123"}' http://localhost:3600/auth
