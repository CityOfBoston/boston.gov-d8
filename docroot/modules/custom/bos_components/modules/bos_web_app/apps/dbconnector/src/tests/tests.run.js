const http = require('http');
const querystring = require('querystring');
const sql_exec = require('../common/services/tedious.exec.service');
const fs = require('fs');
const colors = require('colors');

let config = require('./tests.config').config;
let tests = require('./tests.config').tests;
const { Console } = require('console');

let testOrd = 0;

exports.run = () => {

  credNum = 0;

  console.log("== CREATE INITIAL TEST CONDITIONS========");
  // Delete DB contents and insert test data
  prepareDB()
    .then((result) => {
      console.log(`DB: Is now reset & prepared for testing.`);
      console.log("\n== RUN TESTS ========================");
      // Run the first test, this function cycles through all tests sequentially.
      doTest(testOrd);
    })
    .catch((reason) => {
      console.log("DB: Reset Failed");
    });
};

function doTest (testOrd) {

  if (testOrd < tests.length) {

    let data = "";
    let req_args = "";
    let options = {};

    // Fetch the requested test.
    let test = tests[testOrd];

    if ('debug' in test) {
      config.debug = test.debug;
    }

    if (test.enabled) {

      console.log("\nTEST: ".yellow + test.description);

      options = {
        hostname: config.hostname,
        port: config.port,
        path: test.path,
        method: test.method.type,
        headers: {}
      }
      // Add Authorization Header unless using /auth endpoint.
      if (options.path != "/auth") {
        options.headers["Authorization"] = `Bearer ${config.creds[test.use_creds].token}`;
      }
      if (options.path == "/auth/refresh") {
        test.method.payload = {
          "id" : config.creds[test.use_creds].userid,
          "refresh_token": config.creds[test.use_creds].refreshToken,
        };
      }

      if (test.method.type == "GET") {
        // Need to define any payload as a querystring.
        if ('querystring' in test.method) {
          req_args = querystring.stringify(test.method.querystring);
          options.path = `${test.path}?${req_args}`
        }
      }

      else {
        // Need a payload in the body
        if ('payload' in test.method) {
          data = JSON.stringify(test.method.payload);
          options.headers["Content-Length"] = data.length;
        }

        // Request payload is always json.
        options.headers["Content-Type"] = "application/json";
      }

      const execTest = async () => {
        await requestEndpoint(options, data)

          .then((result) => {
            // Validate the response.
            // console.log(`THEN: ${JSON.stringify(result)}`);
            if (validate(result, test)) {
              console.log("\u2714 [SUCCESS]".green);
              if (options.path == "/auth") {
                saveAuth(result);
              }
              testOrd++;
              doTest(testOrd);
            }
            else {
              console.log(`Narrative: ${test.expected_response.narrative}\n`)
              console.log("\nTESTS FAILED".black.bgRed + "\n");
              process.exit();
            }
          })

          .catch((reason) => {
            console.log(JSON.stringify(reason));
            msg = JSON.stringify(reason);
            if ('data' in reason && 'error' in reason.data) {
              msg = reason.data.error;
              if (Array.isArray(reason.data.error)) {
                msg = reason.data.error.join(", ");
              }
            }

            // Validate the error message - some tests are expected to fail.
            if (validate(reason, test)) {
              // console.log(`NOTE: Expected failure: \"${msg}\"`.grey);
              console.log("\u2714 [SUCCESS]".green);
              testOrd++;
              doTest(testOrd);
            }
            else {
              console.log(`TEST RESULT: ${msg}`.grey);
              console.log(`Narrative: ${test.expected_response.narrative}\n`)
              console.log("\nTESTS FAILED".black.bgRed + "\n");
              // console.log("\n");
              process.exit();
            }
          });
      };

      const validate = (result, test) => {

        if (parseInt(test.expected_response.code) >= 400) {
          msg = JSON.stringify(result);
          if ('data' in result && 'error' in result.data) {
            msg = result.data.error;
            if (Array.isArray(result.data.error)) {
              msg = result.data.error.join(", ");
            }
          }
          console.log(`NOTE: Test failed as expected: \"${msg}\"`.grey);
        }

        if (test.expected_response.code != result.status) {
          // We did not get the status expected.
          console.log(`\u2716 [FAIL] Expected HTTP_STATUS ${test.expected_response.code} but got ${result.status}`.red);
          return false;
        }

        if (test.expected_response.json_data) {
          // Expecting some json data back from endpoint
          if ('data' in result) {

            if (typeof result.data !== "object") {
              // No data returned from endpoint.
              console.log(`\u2716 [FAIL] Unexpected response format from endpoint`.red);
              return false;
            }

          }
          else {
            // No data returned from endpoint.
            console.log(`\u2716 [FAIL] Expected some data from endpoint - but got none.`.red);
            return false;
          }

        }

        if ('exact' in test.expected_response) {
          // This is a dated column and will only cause issues...
          ord = 0;
          result.data.forEach(row => {
            if ('LastUse' in row) {
              delete result.data[ord].LastUse;
            }
            if ('CreatedDate' in row) {
              delete result.data[ord].CreatedDate;
            }
            ord++;
          });


          if (JSON.stringify(test.expected_response.exact) != JSON.stringify(result.data)) {
            // Response does not match.
            console.log(`\u2716 [FAIL] response is not exact match expected`.red);
            console.log(`\nExpected:\n${JSON.stringify(test.expected_response.exact)}`);
            console.log(`\nReceived:\n${JSON.stringify(result.data)}\n`);
            return false;
          }
        }

        return true;

      };

      const saveAuth = (result) => {
        var guserord = 0;
        config.creds.find(userord => {
          if (userord.userid == result.data.userid) {
            return true;
          }
          guserord++;
          return false;
        });
        config.creds[guserord].token = result.data.accessToken;
        config.creds[guserord].refreshToken = result.data.refreshToken;
      };

      execTest();

    }
    else {
      testOrd++;
      doTest(testOrd);
    }

  }
  else {
    console.log("\nALL TESTS PASSED".black.bgGreen + "\n");
    // console.log("\n");
    process.exit();
  }
};

function requestEndpoint (options, data) {
  if (config.debug) {
    console.log("==REQUEST================".gray);
    console.log(`data: ${JSON.stringify(data)}`.gray);
    console.log(`options: ${JSON.stringify(options)}`.gray);
    console.log("=========================".gray);
  }

  var body = '';

  return new Promise((resolve, reject) => {

    // Setup the Endpoint and handler.
    const req = http.request(options, res => {
      if (config.debug) {
        console.log("==RESPONSE=================".gray);
        console.log(`statusCode: ${res.statusCode}`.gray);
      }
      res.on('data', chunk => {
        body += chunk;
      });
      res.on('end', function () {
        if (body == "" || typeof body === "undefined") {
          // console.log("nothing");
          body = {"result": "No Data"};
        }
        else {
          console.log(`body: ${body}`)
          body = JSON.parse(`${body}`);
        }

        output = {
          data: body,
          status: res.statusCode
        }

        if (config.debug) {
          console.log(`data: ${JSON.stringify(output)}`.gray);
          console.log("===========================".gray);
        }

        if ('error' in body) {
          // console.log(`${JSON.stringify(output)}`);
          reject(output);
        }
        else {
          // console.log(`${JSON.stringify(output)}`);
          resolve(output);
        }
      });

    })

    req.on('error', error => {
      console.error("[Error] " + error);
      console.log("=========================");
      reject({'error': error});
    })

    // make the API Call.
    req.write(data);
    req.end();
  });
};

function prepareDB () {

  return new Promise((resolve, reject) => {
    console.log("DB: Initializing Connection")
    fs.readFile('/app/docroot/modules/custom/bos_components/modules/bos_web_app/apps/dbconnector/src/tests/dbpurge.sql', 'utf8' , (err, sql) => {

      if (err) {
        console.error(err);
        resolve(false);
      }

      setTimeout(function () {
        console.log("DB: Purging Tables")
        sql_exec.exec(sql, function (rows, err) {
          if (err) {
            console.log("[ERROR] " + err);
            resolve(false);
          }
          fs.readFile('/app/docroot/modules/custom/bos_components/modules/bos_web_app/apps/dbconnector/src/tests/dbcreate.sql', 'utf8' , (err, sql) => {
            if (err) {
              console.error(err);
              resolve(false);
            }

            console.log("DB: Creating tables")
            sql_exec.exec(sql, function (rows, err) {
              if (err) {
                console.log("[ERROR] " + err);
              }
              resolve(true);
            });

          });
        });
      }, 5000);

    });

  });
};
