const TediousConnection = require('tedious').Connection;
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
  connection.on('infoMessage', infoError);
  connection.on('errorMessage', infoError);
  connection.on('end', end);
  connection.on('debug', debug);
  connection.connect(connected);
}

function connected(err) {
  if (err) {
    console.log(`MSSql proxy connection error: ${err}`);
    callback("error", {"error": `MSSql proxy connection error: ${err}`});
  }
  else {
    console.log('MSSQL Proxy Connected');
    callback("connected", connection);
  }
}

function end() {
  console.log('Connection closed');
  callback("disconnected", {});
}

function infoError(info) {
  console.log(info.number + ' : ' + info.message);
}

function debug(message) {
  // console.log(message);
}

exports.connection = connection
