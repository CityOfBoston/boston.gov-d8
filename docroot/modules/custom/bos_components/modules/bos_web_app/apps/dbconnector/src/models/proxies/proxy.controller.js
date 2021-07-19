const ConnModel = require('../connections/connections.model');

exports.query = (req, res) => {
  const DriverModel = require(`./${req.params.driver}/${req.params.driver}.model`);

 fetchConnectionstringFromToken(req.body.token)
  .then((result) => {
    if (!result.Enabled) {
      res.status(400).send({"error": "Connection disabled"});
    }
    else {
      req.body.connectionString = result.ConnectionString;
      DriverModel.exec(req.body)
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
  })
  .catch((reason) => {
     error = {
       "error": reason
     }
     res.status(400).send(JSON.stringify(error));
  });

};

exports.select = (req, res) => {

  const DriverModel = require(`./${req.params.driver}/${req.params.driver}.model`);

  let limit = 100;
  let page = 0;
  if (req.body) {
    if (req.body.page) {
      req.body.page = parseInt(req.body.page);
      req.body.page = Number.isInteger(req.body.page) ? req.body.page : page;
    }
    if (req.body.limit) {
      req.body.limit = parseInt(req.body.limit);
      req.body.limit = Number.isInteger(req.body.limit) ? req.body.limit : limit;
    }
  }

  fetchConnectionstringFromToken(req.body.token)
    .then((result) => {
      req.body.connectionString = result.ConnectionString;
      DriverModel.select(req.body)
        .then((result) => {
          res.status(200).send(result);
        })
        .catch((reason) => {
          console.log("ERROR: " + reason);
          error = {
            "error": reason
          }
          res.status(400).send(JSON.stringify(error));
        });
      })
    .catch((reason) => {
      error = {
        "error": reason
      }
      res.status(400).send(JSON.stringify(error));
    });
};

function fetchConnectionstringFromToken(token) {

  return new Promise((resolve, reject) => {
    ConnModel.findByToken(token)
      .then((result) => {
        if(result == []) {
          reject("Token not found");
        }
        resolve(result[0]);
      })
      .catch((reason) => {
        reject(reason);
      });
    });

}

// curl -H "Content-Type: application/json" -X PATCH -d '{"username":"david.upton@boston.gov"}' 'http://localhost:3600/users/3'

// -H "Authorization: Bearer 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOjIsInVzZXJuYW1lIjoiZGF2aWQudXB0b25AYm9zdG9uLmdvdiIsInBlcm1pc3Npb25MZXZlbCI6MSwiZW5hYmxlZCI6dHJ1ZSwiSVBBZGRyZXNzIjpbIiJdLCJyZWZyZXNoS2V5IjoiUWZXQm9QYVpVNlk0WS9KM2JTa2NzUT09IiwiaWF0IjoxNjI1MDc4MzAyfQ.7uRhoMNk4DrjIAfU3N1Ffoylh6zb6obWRVUTB0HeJhw'"
