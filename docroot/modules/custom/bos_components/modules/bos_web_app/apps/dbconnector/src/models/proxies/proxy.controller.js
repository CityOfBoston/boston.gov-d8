
exports.query = (req, res) => {
  const DriverModel = require(`./${req.params.driver}/${req.params.driver}.model`);
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
};

exports.select = (req, res) => {
const DriverModel = require(`./${req.params.driver}/${req.params.driver}.model`);
  let limit = 100;
  let page = 0;
  if (req.body) {
    if (req.body.page) {
      req.body.page = parseInt(req.body.page);
      req.body.page = Number.isInteger(req.body.page) ? req.body.page : 0;
    }
    if (req.body.limit) {
      req.body.limit = parseInt(req.body.limit);
      req.body.limit = Number.isInteger(req.body.limit) ? req.body.limit : 100;
    }
  }

  DriverModel.select(req.body)
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

// curl -H "Content-Type: application/json" -X PATCH -d '{"username":"david.upton@boston.gov"}' 'http://localhost:3600/users/3'

// -H "Authorization: Bearer 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOjIsInVzZXJuYW1lIjoiZGF2aWQudXB0b25AYm9zdG9uLmdvdiIsInBlcm1pc3Npb25MZXZlbCI6MSwiZW5hYmxlZCI6dHJ1ZSwiSVBBZGRyZXNzIjpbIiJdLCJyZWZyZXNoS2V5IjoiUWZXQm9QYVpVNlk0WS9KM2JTa2NzUT09IiwiaWF0IjoxNjI1MDc4MzAyfQ.7uRhoMNk4DrjIAfU3N1Ffoylh6zb6obWRVUTB0HeJhw'"
