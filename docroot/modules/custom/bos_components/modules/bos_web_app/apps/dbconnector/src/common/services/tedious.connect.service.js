const Connection = require('tedious').Connection;
const config = require('../env.config').apiConfig;

let state = "disconnected"

const connection = new Connection(config);

connection.connect(connected);
connection.on('infoMessage', info);
connection.on('errorMessage', error);
connection.on('end', end);
connection.on('debug', debug);

function connected(err) {
  if (err) {
    state = 'error';
    console.log("Connection failed: " + err.message);
    console.log("App terminated due to System DB connectivity errors");
    process.exit(0);
  }

  console.log('System DB Connected');

  process.stdin.resume();

  process.stdin.on('data', function(chunk) {
    exec(chunk);
  });

  process.stdin.on('end', function() {
    state = "connected";
    info({ number: 1, message: 'System DB Connected' });
    // process.exit(0);
  });

  process.stderr.on('error', function () {
    error({number: 0, message: "STDIN Error"})
  })
}

function end() {
  info({number: 0, message: 'System DB Connection closed'});
  process.exit(0);
}

function info(info) {
  // console.log(info.number + ' : ' + info.message);
}

function error(err) {
  // console.log('ERROR: ' + err.number + ' : ' + err.message);
}

function debug(message) {
  // console.log('debug: ' + message);
}

exports.connection = connection;
exports.state = state;
