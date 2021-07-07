const TediousConnection = require('tedious').Connection;

const mssqlConfig = {
  server: "172.18.0.2",
  options: {
    "port": 1433,
    "database": "dbconnector",
    "trustServerCertificate": true,
    "requestTimeout": 30 * 1000,
    "useColumnNames": true,
    "rowCollectionOnDone": true
  },
  authentication: {
    type: "default",
    options: {
      userName: "dbconnector",
      password: "dbc0nnector@COB",
    }
  }
};

const connection;

/**
 * Makes a connection to the server specified in config object.
 * @param {mssqlConfig} config Configuration obecjt for tedious.Connection
 */
exports.connect = (config) => {

  connection = new TediousConnection(config);

  connection.connect(connected);
  connection.on('infoMessage', infoError);
  connection.on('errorMessage', infoError);
  connection.on('end', end);
  connection.on('debug', debug);

}

function connected(err) {
  if (err) {
    console.log(err);
    process.exit(1);
  }

  // console.log('connected');

  process.stdin.resume();

  process.stdin.on('data', function(chunk) {
    exec(chunk);
  });

  process.stdin.on('end', function() {
    process.exit(0);
  });
}

function end() {
  console.log('Connection closed');
  process.exit(0);
}

function infoError(info) {
  console.log(info.number + ' : ' + info.message);
}

function debug(message) {
  // console.log(message);
}

exports.connection = connection;
