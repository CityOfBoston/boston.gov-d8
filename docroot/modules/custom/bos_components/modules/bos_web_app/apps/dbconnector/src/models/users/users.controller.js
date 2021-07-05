const UserModel = require('./users.model');
const crypto = require('crypto');

exports.insert = (req, res) => {
  let salt = crypto.randomBytes(16).toString('base64');
  let hash = crypto.createHmac('sha512', salt).update(req.body.password).digest("base64");
  req.body.password = salt + "$" + hash;
  UserModel.create(req.body)
    .then((result) => {
      res.status(201).send({id: result});
    })
    .catch((reason) => {
      error = {
        "error": reason
      }
      res.status(400).send(JSON.stringify(error));
    });
};

exports.list = (req, res) => {
  let limit = 10;
  let page = 0;
  if (req.query) {
    if (req.query.page) {
      req.query.page = parseInt(req.query.page);
      page = Number.isInteger(req.query.page) ? req.query.page : 0;
    }
    if (req.query.limit) {
      req.query.limit = parseInt(req.query.limit);
      limit = Number.isInteger(req.query.limit) ? req.query.limit : 10;
    }
  }
  UserModel.list(limit, page)
    .then((result) => {
      res.status(200).send(result);
    })
    .catch((reason) => {
      error = {
        "error": reason
      }
      res.status(400).send(JSON.stringify(error));
    });
};

exports.get = (req, res) => {
  if (isNaN(req.params.userId)) {
    UserModel.findByUsername(req.params.userId)
      .then((result) => {
        res.status(200).send(result);
      })
      .catch((reason) => {
        error = {
          "error": reason
        }
        res.status(400).send(JSON.stringify(error));
      });
  }
  else {
    UserModel.findByUserId(req.params.userId)
      .then((result) => {
        res.status(200).send(result);
      })
      .catch((reason) => {
        error = {
          "error": reason
        }
        res.status(400).send(JSON.stringify(error));
      });
  }
};

exports.update = (req, res) => {
  if (req.body.password) {
    let salt = crypto.randomBytes(16).toString('base64');
    let hash = crypto.createHmac('sha512', salt).update(req.body.password).digest("base64");
    req.body.password = salt + "$" + hash;
  }
   UserModel.update(req.params.userId, req.body)
     .then((result) => {
        res.status(204).send({});
     })
     .catch((reason) => {
       error = {
         "error": reason
       }
       res.status(400).send(JSON.stringify(error));
     });

};

exports.disable = (req, res) => {
  UserModel.disableById(req.params.userId)
    .then((result)=>{
      res.status(204).send({});
    });
};

// wget -O test http://localhost:3600/test?body="123" && cat test
// curl -H "Content-Type: application/json" -d '{"username":"123", "password":"123"}' http://localhost:3600/users
// curl -H "Authorization: Bearer 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOjIsInVzZXJuYW1lIjoiZGF2aWQudXB0b25AYm9zdG9uLmdvdiIsInBlcm1pc3Npb25MZXZlbCI6MSwiZW5hYmxlZCI6dHJ1ZSwiSVBBZGRyZXNzIjpbIiJdLCJyZWZyZXNoS2V5IjoiUWZXQm9QYVpVNlk0WS9KM2JTa2NzUT09IiwiaWF0IjoxNjI1MDc4MzAyfQ.7uRhoMNk4DrjIAfU3N1Ffoylh6zb6obWRVUTB0HeJhw'" 'http://localhost:3600/users?page=1&limit=3'
// curl http://localhost:3600/users/4
// curl http://localhost:3600/users/david
// curl -H "Content-Type: application/json" -X PATCH -d '{"username":"david.upton@boston.gov"}' 'http://localhost:3600/users/3'
// curl -X DELETE 'http://localhost:3600/users/3'


// -H "Authorization: Bearer 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOjIsInVzZXJuYW1lIjoiZGF2aWQudXB0b25AYm9zdG9uLmdvdiIsInBlcm1pc3Npb25MZXZlbCI6MSwiZW5hYmxlZCI6dHJ1ZSwiSVBBZGRyZXNzIjpbIiJdLCJyZWZyZXNoS2V5IjoiUWZXQm9QYVpVNlk0WS9KM2JTa2NzUT09IiwiaWF0IjoxNjI1MDc4MzAyfQ.7uRhoMNk4DrjIAfU3N1Ffoylh6zb6obWRVUTB0HeJhw'"
