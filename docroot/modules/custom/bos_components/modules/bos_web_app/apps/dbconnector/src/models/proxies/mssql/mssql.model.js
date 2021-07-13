/**
 * Functions which manipulate data within the users table.
 */
const { type } = require('os');
const { config } = require('process');
const mssqlexec = require('./mssql.exec.service');

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
 * This is the schema for the mssql connections string.  In body as JSON String.
 */
const connstr = {
  "server": "",     // Servername or IPAddress
  "port": 1433,     // [optional] Usually 1433
  "database": "",   // [optional] The name of the database
  "userName": "",   // The Username credential
  "password": ""    // Password Credential
}
// Export this so that the connectionstring schema is available externally.
exports.connstr = connstr;

/**
 * This is the schema for config passed to the tedious connect command.
 */
let tediousConfigSchema = {
  server: "",
  options: {
    "port": 1433,
    "database": "",
    "schema": "dbo",
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
 * Applies arguments to the sql string.
 * @param {String} sql
 * @param {Object} args
 * @returns
 */
const unpackSQL = (sql, args) => {
  // substitute arguments into sql.
  let element = "";
  for (const arg in args) {
    if (Object.hasOwnProperty.call(args, arg)) {
      element = args[arg];
      sql = sql.replace(`{${arg}}`, element);
    }
  }
  return sql;
}

/**
 * Convert the connection string passed in body into an object required by the
 * mssql.connect.service.
 *
 * @param {connstr} connstr
 * @returns {tediousConfigSchema} populated config object.
 */
const makeTediousConfig = (connstr) => {

  let config;
  config = tediousConfigSchema;
  let csobj = JSON.parse(connstr);

  config.server = csobj.host;
  config.authentication.options.userName = csobj.user;
  config.authentication.options.password = csobj.password;


  if ('port' in csobj) {
    config.options.port = parseInt(csobj.port);
  }
  if ('schema' in csobj) {
    config.options.schema = parseInt(csobj.schema);
  }
  if ('database' in csobj) {
    config.options.database = csobj.database;
  }
  else {
    delete config.options.database;
  }

  return config;
}

/**
 * Executes an SQL statement on the provided connections string.
 * @param  {bodySchema} body An object containing the connection string and select statement
 * @return {Object} An object with the user record.
 */
exports.exec = (body) => {

  return new Promise((resolve, reject) => {
    sql = unpackSQL(body.statement, body.args);
    let config = makeTediousConfig(body.connectionString);
    console.log(sql);
    mssqlexec.exec(config, sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        if(rows.length == 1) {
          resolve(rows[0]);
        }
        else {
          resolve(rows);
        }
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
    let sql = body.statement;
    if (sql.endsWith(";")) {
      // remove trailing ;'s
      sql = sql.split(";")[0]
    }
    sql = unpackSQL(sql, body.args);

    if (! sql.toLowerCase().includes("offset")) {
      sql = `${sql}\n OFFSET {offset} ROWS FETCH NEXT {limit} ROWS ONLY;`;
      paging = {
        "limit": parseInt(body.limit),
        "offset": (parseInt(body.page) * parseInt(body.limit))
      }

      sql = unpackSQL(sql, paging);
    }

    let config = makeTediousConfig(body.connectionString);

    mssqlexec.exec(config, sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        resolve(rows[0]);
      }
    });

  });

};
