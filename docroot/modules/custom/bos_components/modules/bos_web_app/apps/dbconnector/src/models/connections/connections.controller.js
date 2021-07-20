const ConnModel = require('./connections.model');
const Output = require('../../common/json.responses');

exports.insert = (req, res) => {
  connData = req.body;
  if ('userid' in req.body) {
    connData.createdBy = req.body.userid;
  }
  else if ('userid' in req.jwt) {
    connData.createdBy = req.jwt.userid;
  }
  if (! 'description' in connData ||  connData.description == "") {
    connData.description = `Created by ${req.jwt.username}`;
  }
  ConnModel.create(connData)
    .then((result) => {
      return Output.json_response(res, 201, {connToken: result});
    })
    .catch((reason) => {
      return Output.json_response(res, 400, {error: reason});
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
      return Output.json_response(res, 200, {error: result});
    })
    .catch((reason) => {
      return Output.json_response(res, 400, {error: reason});
    });
};

exports.get = (req, res) => {
  ConnModel.findByToken(req.params.token)
    .then((result) => {
      return Output.json_response(res, 200, result);
    })
    .catch((reason) => {
      return Output.json_response(res, 400, {error: reason});
    });
};

exports.update = (req, res) => {
   ConnModel.update(req.params.token, req.body)
     .then((result) => {
      return Output.json_response(res, 204);
     })
     .catch((reason) => {
      return Output.json_response(res, 400, {error: reason});
     });

};

exports.disable = (req, res) => {
  ConnModel.disableByToken(req.params.token)
    .then((result)=>{
      return Output.json_response(res, 204, result);
    })
    .catch((reason) => {
      return Output.json_response(res, 400, {error: reason});
    });

};

/**
 * MAPPING CONNECTIONS <=> USERS
 */

exports.getUserConnections = (req, res) => {
  if (isNaN(req.params.userId)) {
    ConnModel.findConnectionsByUsername(req.params.userId)
      .then((result) => {
        return Output.json_response(res, 200, result);
      })
      .catch((reason) => {
        return Output.json_response(res, 400, {error: reason});
      });
  }
  else {
    ConnModel.findConnectionsByUserId(req.params.userId)
      .then((result) => {
        return Output.json_response(res, 200, result);
      })
      .catch((reason) => {
        return Output.json_response(res, 400, {error: reason});
      });
  }
};

exports.getConnectionUsers = (req, res) => {
  ConnModel.findUsersByToken(req.params.token)
    .then((result) => {
      return Output.json_response(res, 200, result);
    })
    .catch((reason) => {
      return Output.json_response(res, 400, {error: reason});
    });

};

exports.insertMapping = (req, res) => {
  ConnModel.createMapping(req.params.token, req.params.userid)
    .then((result) => {
      return Output.json_response(res, 201, result);
    })
    .catch((reason) => {
      return Output.json_response(res, 400, {error: reason});
    });
};

exports.deleteMapping = (req, res) => {
  ConnModel.deleteMapping(req.params.token, req.params.userid)
    .then((result)=>{
      return Output.json_response(res, 204);
    })
    .catch((reason) => {
      return Output.json_response(res, 400, {error: reason});
    });
};
