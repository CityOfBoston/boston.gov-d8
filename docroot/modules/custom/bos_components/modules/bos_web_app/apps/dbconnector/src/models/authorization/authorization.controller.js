const jwtSecret = require('../../common/env.config').jwt_secret,
  jwtExpiration = require('../../common/env.config').jwt_expiration_in_seconds,
  flood_time = require('../../common/env.config').flood_time,
  jwt = require('jsonwebtoken');
const crypto = require('crypto');

exports.login = (req, res) => {
  try {
    let refreshId = req.body.userid + jwtSecret;
    let salt = crypto.randomBytes(16).toString('base64');
    let hash = crypto.createHmac('sha512', salt).update(refreshId).digest("base64");
    req.body.refreshKey = salt;
    let token = jwt.sign(req.body, jwtSecret, { expiresIn: jwtExpiration });
    let b = Buffer.from(hash);
    let refresh_token = b.toString('base64');

    // Create a session cookie with a 30 second "block" for this user.
    // let block = Math.round((new Date().getTime / 1000) / flood_time);
    // console.log(JSON.stringify(session));
    // if (!"flood" in req.session) {
    //   req.session['flood'] = {};
    // }
    // // Initializes for this user and removes any old blocks.
    // req.session.flood = [req.body.userid] = {block : 0};

    res.status(201).send({userid: req.body.userid, accessToken: token, refreshToken: refresh_token});
  } catch (err) {
    console.log(err);
    res.status(500).send({error: err});
  }
};

exports.refresh_token = (req, res) => {
  try {
    req.body = req.jwt;
    let token = jwt.sign(req.body, jwtSecret);
    res.status(201).send({id: token});
  } catch (err) {
    res.status(500).send({error: err});
  }
};

// curl -H "Content-Type: application/json" -d '{"username":"david.upton@boston.gov", "password": "123"}' http://localhost:3600/auth
