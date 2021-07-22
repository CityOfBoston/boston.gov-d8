const TediousConnection = require('tedious').Connection;
const colors = require('colors');

let callback;
let connection;

/**
 * Makes a connection to the server specified in config object.
 * @param {mssqlConfig} config Configuration object for tedious.Connection
 * @param {any} cb Callback
 */
exports.connect = (config, cb) => {
  callback = cb;
  connection = new TediousConnection(config);
  connection.on('infoMessage', info);
  connection.on('errorMessage', error);
  connection.on('end', end);
  connection.on('debug', debug);
  connection.connect(connected);
}

function connected(err) {
  if (err) {
    console.log(`\u2716 MSSQL Proxy Connection Error: ${err}`.red);
    callback("error", {"error": `MSSql proxy connection error: ${err}`});
  }
  else {
    console.log('\u2714'.green + ' MSSQL Proxy Connected');
    callback("connected", connection);
  }
}

function end() {
  console.log('MSSQL Connection Closed');
  callback("disconnected", {});
}

function error(msg) {
  console.log(`\u2716 MSSQL Proxy Error: ${msg.number}: ${msg.message}`.red);
}

function info(info) {
  // console.log(info.number + ' : ' + info.message);
}

function debug(message) {
  // console.log(message);
}

exports.connection = connection
