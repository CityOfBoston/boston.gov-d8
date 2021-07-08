/**
 * Functions which manipulate data within the users table.
 */
const { config } = require('process');
const sql_svs = require('./mssql.connect.service');
const sql_exec = require('./mssql.exec.service');
let sql_conn;

/**
 * This is the schema for the mssql connections string.  In body as JSON String.
 */
const connstr = {
  "server": "",     // Servername or IPAddress
  "port": 1433,     // [optional] Usually 1433
  "database": "",   // [optional] The name of the database
  "userName": "",   // The Username credential
  "password": ""    // Password Credential
}

/**
 * A structure which represents the body of a request.
 */
const bodySchema = {
  'connectionString': '',
  'sqlStatement': '',         // In format connstr
  'limit': 100,
  'page': 0
}

/**
 * This is the schema for config passed to the tedious connect command.
 */
const configSchema = {
  server: "",
  options: {
    "port": 1433,
    "database": "",
    "trustServerCertificate": true,
    "requestTimeout": 30 * 1000,
      "useColumnNames": true,
        "rowCollectionOnDone": true
  },
  authentication: {
    type: "default",
    options: {
        userName: "",
        password: "",
      }
  }
}

/**
 * Makes a connection to the SQL Server.
 *
 * @param {connstr} connstr
 */
const makeConnection = (connstr) => {
  config = new configSchema;
  config.options.server = connstr.server;
  config.authentication.options.userName = connstr.userName;
  config.authentication.options.password = connstr.password;

  if ('port' in connstr) {
    config.options.port = connstr.port;
  }
  if ('database' in connstr) {
    config.options.database = connstr.database;
  }
  else {
    delete config.options.database;
  }

  sql_svs.connect(config)
}


/**
 * Executes an SQL statement on the provided connections string.
 * @param  {bodySchema} body An object containing the connection string and select statement
 * @return {Object} An object with the user record.
 */
exports.exec = (body) => {

  return new Promise((resolve, reject) => {
// SMASH the payload into the necessary
//makeconnection here
    sql = body.statement;

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        resolve(rows[0]);
      }
    });

  });

};

/**
 * Runs a select query, or a stored procedure which returns rows and returns paged result.
 * @param  {bodySchema} params An object containing the connection string and select statement.
 * @return {Object} An object with the user record.
 */
 exports.select = (body) => {

  return new Promise((resolve, reject) => {

    sql = `SELECT ID, Username, '*****' as Password, IPAddresses, Enabled, Role
           FROM dbo.users
           WHERE ID = ${id};`

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        resolve(rows[0]);
      }
    });

  });

};

