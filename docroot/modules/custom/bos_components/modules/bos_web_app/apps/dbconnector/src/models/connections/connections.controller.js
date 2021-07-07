const ConnModel = require('./connections.model');

exports.insert = (req, res) => {
  connData = req.body;
  if ('userid' in req.body) {
    connData.createdBy = req.body.userid;
  }
  else if ('userid' in req.jwt) {
    connData.createdBy = req.jwt.userid;
  }
  if (! 'description' in connData ||  connData.description == "") {
    connData.description = `Created by ${req.jwt.username}`
  }
  ConnModel.create(connData)
    .then((result) => {
      res.status(201).send({connToken: result});
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
  ConnModel.list(limit, page)
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
  ConnModel.findByToken(req.params.token)
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

exports.update = (req, res) => {
   ConnModel.update(req.params.token, req.body)
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
  ConnModel.disableByToken(req.params.token)
    .then((result)=>{
      res.status(204).send({});
    })
    .catch((reason) => {
      error = {
        "error": reason
      }
      res.status(400).send(JSON.stringify(error));
    });

};

/**
 * MAPPING CONNECTIONS <=> USERS
 */

exports.getUserConnections = (req, res) => {
  if (isNaN(req.params.userId)) {
    ConnModel.findConnectionsByUsername(req.params.userId)
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
    ConnModel.findConnectionsByUserId(req.params.userId)
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

exports.getConnectionUsers = (req, res) => {
  ConnModel.findUsersByToken(req.params.token)
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

exports.insertMapping = (req, res) => {
  ConnModel.createMapping(req.params.token, req.params.userid)
    .then((result) => {
      res.status(201).send();
    })
    .catch((reason) => {
      error = {
        "error": reason
      }
      res.status(400).send(JSON.stringify(error));
    });
};

exports.deleteMapping = (req, res) => {
  ConnModel.deleteMapping(req.params.token, req.params.userid)
    .then((result)=>{
      res.status(204).send({});
    })
    .catch((reason) => {
      error = {
        "error": reason
      }
      res.status(400).send(JSON.stringify(error));
    });
};
