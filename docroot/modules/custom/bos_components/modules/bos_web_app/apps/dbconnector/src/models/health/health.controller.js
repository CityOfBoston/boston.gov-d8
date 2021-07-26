// const Output = require('../../common/json.responses');
// const sql_conn = require('../../common/services/tedious.connect.service').connection;

exports.ping = (req, res) => {
  if (sql_conn) {
    return Output.json_response(res, 200, {});
  }
  else {
    return Output.json_response(res, 404);
  }
};
