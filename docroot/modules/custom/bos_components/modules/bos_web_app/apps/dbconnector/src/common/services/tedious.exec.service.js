const sql_conn = require('../../common/services/tedious.connect.service').connection;

const Request = require('tedious').Request;
let rows = [];
let statements = 0;
let callback;
let errors = false;

exports.exec = (sql, callbacker) => {
  callback = callbacker;
  errors = false;
  statements = 0;
  rows = []

  sql = sql.toString();

  // console.log(sql);

  const request = new Request(sql, statementComplete);
  request.on('doneInProc', requestDone);
  request.on('error', tediousReturn);
  request.on('requestCompleted', tediousReturn);

  sql_conn.execSql(request);

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
  if (errors) {

  }
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
    // console.log('Processed ' + statements + " statements");
  }
}

exports.statementsExecuted = statements;
