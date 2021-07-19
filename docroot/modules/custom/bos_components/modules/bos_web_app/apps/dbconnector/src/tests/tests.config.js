const config = require('../common/env.config');
module.exports = {
  config: {
    debug: false,
    headers: {
      "Content-Type": "application/json"
    },
    hostname:  "localhost",
    port: 3600,
    creds: [
      {
        username: "david.upton@boston.gov",
        password: "123",
        userid: 1,
        token: '',
        refreshToken: ''
      },
      {
        username: "havocint@gmail.com",
        password: "",
        userid: 2,
        token: '',
        refreshToken: ''
      },
      {
        username: "havocint@hotmail.com",
        password: "123",
        userid: 3,
        token: '',
        refreshToken: ''
      },
      {
        username: "david",
        password: "123",
        userid: 4,
        token: '',
        refreshToken: ''
      },
      {
        username: "someone@somewhere.com",
        password: "123",
        userid: 5,
        token: '',
        refreshToken: ''
      },
    ],
  },

  tests: [
    {
      description: "Login as OWNER user (UID=1 / creds=0)",
      enabled: true,
      debug: false,
      path: "/auth",
      method: {
        type: "POST",
        payload: {
          "username": "david.upton@boston.gov",
          "password": "123"
        }
      },
      expected_response: {
        narrative: "Login OK.",
        code: 201,
        json_data: true,
      }
    },
    {
      description: "List Users",
      enabled: true,
      debug: true,
      path: "/users",
      use_creds: 0,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Returns All users in the users table",
        code: 200,
        json_data: true,
      }
    },
    {
      description: "List Users, paged",
      enabled: true,
      debug: false,
      path: "/users",
      use_creds: 0,
      method: {
        type: "GET",
        querystring: {
          "limit": 2,
          "page":1
        }
      },
      expected_response: {
        narrative: "2 user records starting with the 3rd record. ",
        code: 200,
        json_data: true,
        exact: [{"ID":3,"Username":"havocint@hotmail.com","Password":"*****","IPAddresses":"","Enabled":true,"Role":4},{"ID":4,"Username":"david","Password":"*****","IPAddresses":"10.10.10.10","Enabled":true,"Role":1}]
      }
    },
    {
      description: "List single user",
      enabled: true,
      debug: false,
      path: "/users/1",
      use_creds: 0,
      method: {
        type: "GET",
        querystring: {}
      },
      expected_response: {
        narrative: "Look for an exact match",
        code: 200,
        json_data: true,
        exact: [{ "ID": 1, "Username": "david.upton@boston.gov", "Password": "*****", "IPAddresses": "", "Enabled": true, "Role": 4096 }]
      }
    },
    {
      description: "List single user (from wrong ip)",
      enabled: true,
      debug: false,
      path: "/auth",
      method: {
        type: "POST",
        payload: {
          "username": "david",
          "password": "123"
        }
      },
      expected_response: {
        narrative: "Look for an exact match",
        code: 400,
        json_data: true,
        exact: { "error": "Unathorized IPAddress" }
      }
    },
    {
      description: "Add a single user",
      enabled: true,
      debug: false,
      path: "/users",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          "username": "someone@somewhere.com",
          "password": "123",
          "role": config.permissionLevels.NORMAL_USER
        }
      },
      expected_response: {
        narrative: "Adds a new user to the user table. ",
        code: 201,
        json_data: true,
      }
    },
    {
      description: "Try to add a duplicate single user",
      enabled: true,
      debug: false,
      path: "/users",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          "username": "someone@somewhere.com",
          "password": "123"
        }
      },
      expected_response: {
        narrative: "Adds a duplicate user to the user table. Should fail.",
        code: 400,
        json_data: true,
        exact: {"error": "Statement 3 failed: RequestError: Cannot insert duplicate key row in object 'dbo.users' with unique index 'UK_Username'. The duplicate key value is (someone@somewhere.com)."}
      }
    },
    {
      description: "Try to fetch a user that is not yourself when not an admin",
      enabled: true,
      debug: false,
      path: "/users/1",
      use_creds: 4,
      method: {
        type: "GET",
        querystring: {}
      },
      expected_response: {
        narrative: "Should get a 403 - forbidden",
        code: 403,
        json_data: false
      }
    },
    {
      description: "Disable a user",
      enabled: true,
      debug: false,
      path: "/users/2",
      use_creds: 0,
      method: {
        type: "DELETE",
      },
      expected_response: {
        narrative: "Disables User id 2 (sets enabled = 0).",
        code: 204,
        json_data: true,
      }
    },
    {
      description: "Update a single user (and re-enable as admin_user)",
      enabled: true,
      debug: false,
      path: "/users/2",
      use_creds: 0,
      method: {
        type: "PATCH",
        payload: {
          "username": "havocint@gmail.com",
          "password": "new",
          "role": config.permissionLevels.ADMIN_USER,
          'enabled': 1
        }
      },
      expected_response: {
        narrative: "Updates the User id 2 with new password and role and re-enables.",
        code: 204,
        json_data: true,
      }
    },
    {
      description: "Login as updated and re-enabled user",
      enabled: true,
      debug: false,
      path: "/auth",
      method: {
        type: "POST",
        payload: {
          "username": "havocint@gmail.com",
          "password": "new"
        }
      },
      expected_response: {
        narrative: "Login OK.",
        code: 201,
        json_data: true,
      }
    },
    {
      description: "Login as the new user (normal permissions) ",
      enabled: true,
      debug: false,
      path: "/auth",
      method: {
        type: "POST",
        payload: {
          "username": "someone@somewhere.com",
          "password": "123"
        }
      },
      expected_response: {
        narrative: "Login OK.",
        code: 201,
        json_data: true,
      }
    },
    {
      description: "New user attempts to disable a user",
      enabled: true,
      debug: false,
      path: "/users/2",
      use_creds: 4,
      method: {
        type: "DELETE",
      },
      expected_response: {
        narrative: "Fails to disable User id 2 (not authorized).",
        code: 403,
        json_data: false,
      }
    },
    {
      description: "New user attempts to refresh token",
      enabled: true,
      debug: false,
      path: "/auth/refresh",
      use_creds: 4,
      method: {
        type: "POST",
      },
      expected_response: {
        narrative: "Refreshes the token.",
        code: 201,
        json_data: false,
      }
    },
    {
      description: "List Connection Strings",
      enabled: true,
      debug: false,
      path: "/connections",
      use_creds: 0,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Returns All connection strings",
        code: 200,
        json_data: true,
        exact: [{"ID":1,"Token":"806117D6-EE39-4664-B49E-4D069610E818","ConnectionString":"****","Description":"dummy entry","Username":"david.upton@boston.gov","Enabled":true},{"ID":2,"Token":"11666A1A-3E54-42C3-A523-9F38EEDD96F3","ConnectionString":"****","Description":"dummy entry","Username":"havocint@gmail.com","Enabled":true}]
      }
    },
    {
      description: "Fetch a single Connection String by token",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 0,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Returns a single connection string (token: 806117D6-EE39-4664-B49E-4D069610E818)",
        code: 200,
        json_data: true,
        exact: [{ "ID": 1, "Token": "806117D6-EE39-4664-B49E-4D069610E818", "ConnectionString": '{"host":"172.20.0.5", "port":"1433", "schema":"dbo", "database":"CMDB", "user":"admin", "password":"7sUSVGG%3g6a"}',"Description":"dummy entry","CreatedBy":1,"Enabled":true}]
      }
    },
    {
      description: "Insert a new connection string",
      enabled: true,
      debug: false,
      path: "/connection",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          connectionString: "mysql:123.123.123.123:3000/hellp@123",
          description: "A new test connection string",
          createdBy: 0
        }
      },
      expected_response: {
        narrative: "Creates a new connection string",
        code: 201,
        json_data: false,
      }
    },
    {
      description: "Disable a connection string",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 0,
      method: {
        type: "DELETE",
      },
      expected_response: {
        narrative: "Disable a single connection string (806117D6-EE39-4664-B49E-4D069610E818)",
        code: 204,
        json_data: false,
      }
    },
    {
      description: "Check Connection String was disabled",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 0,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "The connection string should have enabled=false (token: 806117D6-EE39-4664-B49E-4D069610E818)",
        code: 200,
        json_data: true,
        exact: [{
          "ID": 1, "Token": "806117D6-EE39-4664-B49E-4D069610E818", "ConnectionString":`{"host":"${config.apiConfig.server}", "port":"1433", "schema":"dbo", "database":"CMDB", "user":"admin", "password":"7sUSVGG%3g6a"}`, "Description":"dummy entry","CreatedBy":1,"Enabled":false}]
      }
    },
    {
      description: "Update a connection string",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 0,
      method: {
        type: "PATCH",
        payload: {
          description: "Updated Dummy",
          enabled: 1
        }
      },
      expected_response: {
        narrative: "Changes the values of some field in connection string.",
        code: 204,
        json_data: false,
      }
    },
    {
      description: "Check Connection String was updated",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 0,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "The connection string should have enabled=false (token: 806117D6-EE39-4664-B49E-4D069610E818)",
        code: 200,
        json_data: true,
        exact: [{ "ID": 1, "Token": "806117D6-EE39-4664-B49E-4D069610E818", "ConnectionString": `{"host":"${config.apiConfig.server}", "port":"1433", "schema":"dbo", "database":"CMDB", "user":"admin", "password":"7sUSVGG%3g6a"}`,"Description":"Updated Dummy","CreatedBy":1,"Enabled":true}]
      }
    },
    {
      description: "Try to get connectionstring you are not permissioned for.",
      enabled: true,
      debug: false,
      path: "/connections/11666a1a-3e54-42c3-a523-9f38eedd96f3",
      use_creds: 4,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Cannot get the connections string from tokens - should get a 403.",
        code: 403,
      }
    },
    {
      description: "List connection strings available to a user (by userid)",
      enabled: true,
      debug: false,
      path: "/users/1/connections",
      use_creds: 0,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Should get user + connections",
        code: 200,
        json_data: true,
        exact: [{ "Username": "david.upton@boston.gov", "userid": 1, "connid": 1, "Token": "806117D6-EE39-4664-B49E-4D069610E818", "ConnectionString": `{"host":"${config.apiConfig.server}", "port":"1433", "schema":"dbo", "database":"CMDB", "user":"admin", "password":"7sUSVGG%3g6a"}`,"Description":"Updated Dummy","Enabled":true,"Count":0}]
      }
    },
    {
      description: "List connection strings available to a user (by username)",
      enabled: true,
      debug: false,
      path: "/users/david.upton@boston.gov/connections",
      use_creds: 0,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Should get user + connections",
        code: 200,
        json_data: true,
        exact: [{ "Username": "david.upton@boston.gov", "userid": 1, "connid": 1, "Token": "806117D6-EE39-4664-B49E-4D069610E818", "ConnectionString": `{"host":"${config.apiConfig.server}", "port":"1433", "schema":"dbo", "database":"CMDB", "user":"admin", "password":"7sUSVGG%3g6a"}`,"Description":"Updated Dummy","Enabled":true,"Count":0}]
      }
    },
    {
      description: "List users who can use a connection string",
      enabled: true,
      debug: false,
      path: "/connection/806117D6-EE39-4664-B49E-4D069610E818/users",
      use_creds: 0,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "The connection string should have enabled=false (token: 806117D6-EE39-4664-B49E-4D069610E818)",
        code: 200,
        json_data: true,
        exact: [{"ID":1,"Username":"david.upton@boston.gov","Enabled":true,"Count":0},{"ID":3,"Username":"havocint@hotmail.com","Enabled":true,"Count":0},{"ID":5,"Username":"someone@somewhere.com","Enabled":true,"Count":0}]
      }
    },
    {
      description: "Add a new connections string permission ",
      enabled: true,
      debug: false,
      path: "/connection/806117D6-EE39-4664-B49E-4D069610E818/user/2",
      use_creds: 0,
      method: {
        type: "POST",
      },
      expected_response: {
        narrative: "A new connection string should exist",
        code: 201,
        json_data: false,
      }
    },
    {
      description: "Remove a connection string permission",
      enabled: true,
      debug: false,
      path: "/connection/806117D6-EE39-4664-B49E-4D069610E818/user/2",
      use_creds: 0,
      method: {
        type: "DELETE",
      },
      expected_response: {
        narrative: "The connection string should have enabled=false (token: 806117D6-EE39-4664-B49E-4D069610E818)",
        code: 204,
        json_data: false,
      }
    },
    {
      description: "Execute SQL with invalid token",
      enabled: true,
      debug: false,
      path: "/query/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "SELECT '{a}' AS val",
          'token': 'x06117D6-EE39-4664-B49E-4D069610E818',
          'args': { "a": "Dataset 1" },
        }
      },
      expected_response: {
        narrative: "Tries to connect with an invalid token (token not uuid)",
        code: 400,
        json_data: true,
        exact: { "error": "Token not found" }
      }
    },
    {
      description: "Execute SQL with invalid token - 2",
      enabled: true,
      debug: false,
      path: "/query/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "SELECT '{a}' AS val",
          'token': '006117D6-EE39-4664-B49E-4D069610E818',
          'args': { "a": "Dataset 1" },
        }
      },
      expected_response: {
        narrative: "Tries to connect with an invalid token (non-existant token)",
        code: 400,
        json_data: true,
        exact: { "error": "Token not found" }
      }
    },
    {
      description: "Execute SQL with disabled token",
      enabled: false,
      debug: false,
      path: "/query/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "SELECT '{a}' AS val",
          'token': 'x06117D6-EE39-4664-B49E-4D069610E818',
          'args': { "a": "Dataset 1" },
        }
      },
      expected_response: {
        narrative: "Tries to connect with a disabled token",
        code: 400,
        json_data: true,
        exact: { "error": "Token not found" }
      }
    },
    {
      description: "Execute some SQL",
      enabled: true,
      debug: false,
      path: "/query/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "SELECT '{a}' AS val",
          'token': '806117D6-EE39-4664-B49E-4D069610E818',
          'args': { "a": "Dataset1" },
        }
      },
      expected_response: {
        narrative: "Runs a simple single sql select.",
        code: 200,
        json_data: true,
        exact: [{ "val": "Dataset1" }]
      }
    },
    {
      description: "Execute some multi-row SQL",
      enabled: true,
      debug: false,
      path: "/query/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "SELECT '{a}' AS val; SELECT '{b}' AS val",
          'token': '806117D6-EE39-4664-B49E-4D069610E818',
          'args': { "a": "Dataset1", "b": "Dataset2" },
        }
      },
      expected_response: {
        narrative: "Runs sql",
        code: 200,
        json_data: true,
        exact: [[{ "val": "Dataset1" }], [{ "val": "Dataset2" }]]
      }
    },
    {
      description: "Execute an INSERT SQL",
      enabled: true,
      debug: false,
      path: "/query/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "INSERT INTO dbo.[CMDBVariables] ([ID], [CREATED_DATE], [LAST_MODIFIED_DATE], [CREATED_BY], [LAST_MODIFIED_BY], [key], [value]) VALUES ('123117D6-EE39-4664-B49E-4D069610E818', '2021-07-07T18:21:38.417Z', '2021-07-07T18:21:38.417Z', 'CON01579', 'CON01579', 'test1', 'test1value'),('123417D6-EE39-4664-B49E-4D069610E818', '2021-07-07T18:21:38.417Z', '2021-07-07T18:21:38.417Z', 'CON01579', 'CON01579', 'test2', 'test2value')",
          'token': '806117D6-EE39-4664-B49E-4D069610E818',
          'args': { "a": "dbo.[CMDBVariables]"},
        }
      },
      expected_response: {
        narrative: "Adds 2 records to CMDBVariables table.",
        code: 200,
        json_data: false
      }
    },
    {
      description: "Execute an COMBO SQL",
      enabled: true,
      debug: false,
      path: "/query/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "DELETE FROM dbo.[CMDBVariables] WHERE [ID] = '123117D6-EE39-4664-B49E-4D069610E818'; SELECT [ID] FROM CMDBVariables WHERE [ID] = '123417D6-EE39-4664-B49E-4D069610E818';DELETE FROM dbo.[CMDBVariables] WHERE [ID] = '123417D6-EE39-4664-B49E-4D069610E818';",
          'token': '806117D6-EE39-4664-B49E-4D069610E818',
          'args': { "a": "dbo.[CMDBVariables]" },
        }
      },
      expected_response: {
        narrative: "Adds 2 records to CMDBVariables table.",
        code: 200,
        json_data: true,
        exact: [[], [{ "ID": "123417D6-EE39-4664-B49E-4D069610E818" }], []]
      }
    },
    {
      description: "Execute a select SQL (fails)",
      enabled: true,
      debug: false,
      path: "/select/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "SELECT '{a}' AS val; SELECT '{b}' AS val",
          'token': '806117D6-EE39-4664-B49E-4D069610E818',
          'limit': 1,
          'page': 1,
          'args': { "a": "Dataset1", "b": "Dataset2" },
        }
      },
      expected_response: {
        narrative: "Passes too many sql statements into endpoint.",
        code: 400,
        json_data: false
      }
    },
    {
      description: "Execute a select SQL",
      enabled: true,
      debug: false,
      path: "/select/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "SELECT Username from users order by Username;",
          'token': '11666A1A-3E54-42C3-A523-9F38EEDD96F3',
          'limit': 1,
          'page': 1
        }
      },
      expected_response: {
        narrative: "Runs sql",
        code: 200,
        json_data: true,
        exact: [{ "Username": "david.upton@boston.gov" }]
      }
    },
    {
      description: "Execute a select SQL on table that does not exist",
      enabled: true,
      debug: false,
      path: "/select/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "SELECT Username from usersxx order by Username;",
          'token': '11666A1A-3E54-42C3-A523-9F38EEDD96F3',
          'limit': 1,
          'page': 1
        }
      },
      expected_response: {
        narrative: "Runs sql",
        code: 400,
        json_data: false,
        exact: { "error": "Statement 2 failed: RequestError: Invalid object name 'usersxx'." }
      }
    },
    {
      description: "Update a connection string with non-existent server",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 0,
      method: {
        type: "PATCH",
        payload: {
          ConnectionString: '{"host":"172.20.0.256", "port":"1433", "schema":"dbo", "database":"dbconnector", "user":"dbconnector", "password":"dbc0nnector@COB"}'
        }
      },
      expected_response: {
        narrative: "Changes the value of the server in the connection string.",
        code: 204,
        json_data: false,
      }
    },
    {
      description: "Execute SQL where Connection fails",
      enabled: true,
      debug: false,
      path: "/query/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': "SELECT '{a}' AS val",
          'token': '806117D6-EE39-4664-B49E-4D069610E818',
          'args': { "a": "Dataset 1" },
        }
      },
      expected_response: {
        narrative: "Tries to connect to a server which does not exist.",
        code: 400,
        json_data: true
      }
    },

  ]

};
