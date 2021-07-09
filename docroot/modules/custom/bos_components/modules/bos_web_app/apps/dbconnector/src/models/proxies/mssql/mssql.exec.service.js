const Request = require('tedious').Request;
const mssql = require('./mssql.connect.service');
let rows = [];
let statements = 0;
let callback;
let errors = false;

exports.exec = (config, sql, cb) => {

  callback = cb;
  errors = false;
  statements = 0;
  rows = []
  console.log(JSON.stringify(config));
  sql = sql.toString();
  sql = `use ${config.options.database};\n ${sql};`;
  console.log(sql);

  delete config.options.schema;

  mssql.connect(config, function (conn) {

    const request = new Request(sql, statementComplete);
    request.on('doneInProc', requestDone);
    request.on('error', tediousReturn);
    request.on('requestCompleted', tediousReturn);

    conn.execSql(request);
  });
}

function requestDone(rowCount, more, reqRows) {
  statements += 1;
  if (rowCount > 0) {
    let lrows = [];
    reqRows.forEach((row) => {
      lrow = row
      for (const col in lrow) {
        // delete lrow[col].metadata;
        lrow[col] = lrow[col].value;
      }
      lrows.push(lrow);
    });
    rows.push(lrows);
  }
}

function tediousReturn(err) {
  if (errors) {}
  else if (err && typeof err !== "undefined") {
    callback([], 'Statement ' + (statements + 1) + ' failed: ' + err);
    errors = true
  }
  else {
    callback(rows);
  }
}

function statementComplete(err, rowCount) {
  if (errors) {}
  else if (err && typeof err !== "undefined") {
    callback([], 'Statement ' + (statements + 1) + ' failed: ' + err);
    errors = true
  }
  else {
    console.log('Processed ' + statements + " statements");
  }
}

exports.statementsExecuted = statements;
