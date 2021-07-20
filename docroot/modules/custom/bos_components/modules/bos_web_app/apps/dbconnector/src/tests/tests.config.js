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
      {},
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
// LOGIN- UID 1
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
        narrative: "Tests that an ADMIN (UID=0) can login",
        code: 201,
        json_data: true,
      }
    },
    {
      description: "Login as UID = 4 (FAILS - from wrong ip)",
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
        narrative: "Tests that Authentication can only come from approved IPAddreses ",
        code: 403,
        json_data: true,
        exacts: { "error": "Unathorized IPAddress" }
      }
    },
    {
      description: "Try to login as unknown user (FAILS - Unknown User)",
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
        narrative: "Tests that an unknown user cannot login",
        code: 400,
        json_data: true,
        exact: { "error": "Username not found" }
      }
    },
    {
      description: "Try to login with bad password (FAILS - Bad password)",
      enabled: true,
      debug: false,
      path: "/auth",
      method: {
        type: "POST",
        payload: {
          "username": "havocint@hotmail.com",
          "password": "wrongpwd"
        }
      },
      expected_response: {
        narrative: "Tests that cannot login with a bad password for given username",
        code: 400,
        json_data: true,
        exact: {"error": "Invalid username or password"}
      }
    },
    {
      description: "Request unpaged list of all Users",
      enabled: true,
      debug: false,
      path: "/users",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Tests that an ADMIN can list all Users",
        code: 200,
        json_data: true,
      }
    },
    {
      description: "Request second page of User list",
      enabled: true,
      debug: false,
      path: "/users",
      use_creds: 1,
      method: {
        type: "GET",
        querystring: {
          "limit": 2,
          "page":1
        }
      },
      expected_response: {
        narrative: "Tests paged listing - should return 2 user records starting with the 3rd record",
        code: 200,
        json_data: true,
        exact: [{"ID":3,"Username":"havocint@hotmail.com","Password":"*****","IPAddresses":"","Enabled":true,"Role":4},{"ID":4,"Username":"david","Password":"*****","IPAddresses":"10.10.10.10","Enabled":true,"Role":1}]
      }
    },
    {
      description: "Request listing of a single User",
      enabled: true,
      debug: false,
      path: "/users/1",
      use_creds: 1,
      method: {
        type: "GET",
        querystring: {}
      },
      expected_response: {
        narrative: "Tests that an ADMIN can request a single User",
        code: 200,
        json_data: true,
        exact: [{ "ID": 1, "Username": "david.upton@boston.gov", "Password": "*****", "IPAddresses": "", "Enabled": true, "Role": 4096 }]
      }
    },
    {
      description: "Request listing of a single User using username",
      enabled: true,
      debug: false,
      path: "/users/david",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Tests that listing can use username as well as uid",
        code: 200,
        json_data: true,
        exact: [{ "ID": 4, "Username": "david", "Password": "wV1/g/3LN3gZXmxhSNImkw==$0nM+7jTxyR7DR2sGs5UJrswFtVpNscYt2eAmeKylAVYFGrpO2fvVhnz6Tsz4EkEhRAVPK7sQgTHe7x90HumE0w==", "IPAddresses": "10.10.10.10", "Enabled": true, "Role": 1 }]
      }
    },
    {
      description: "Request listing of a single User using username which is email",
      enabled: true,
      debug: false,
      path: "/users/david.upton@boston.gov",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Tests that listing can use username as well as uid",
        code: 200,
        json_data: true,
        exact: [{
          "ID": 1, "Username": "david.upton@boston.gov", "Password": "wV1/g/3LN3gZXmxhSNImkw==$0nM+7jTxyR7DR2sGs5UJrswFtVpNscYt2eAmeKylAVYFGrpO2fvVhnz6Tsz4EkEhRAVPK7sQgTHe7x90HumE0w==", "IPAddresses": "", "Enabled": true, "Role": 4096 }]
      }
    },
    {
      description: "Disable a user",
      enabled: true,
      debug: false,
      path: "/users/2",
      use_creds: 1,
      method: {
        type: "DELETE",
      },
      expected_response: {
        narrative: "Tests that an ADMIN can disable UID=2 (sets enabled = 0).",
        code: 204,
        json_data: true,
      }
    },
    {
      description: "Try to login as disabled user",
      enabled: true,
      debug: false,
      path: "/auth",
      method: {
        type: "POST",
        payload: {
          "username": "davidrkupton@gmail.com",
          "password": "123"
        }
      },
      expected_response: {
        narrative: "Tests that a disabled user cannot login",
        code: 401,
        json_data: true,
        exact: { "error": "User Disabled" }
      }
    },
    {
      description: "Update a single user (UID=2) (re-enable, change password and raise role to ADMIN)",
      enabled: true,
      debug: false,
      path: "/users/2",
      use_creds: 1,
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
        narrative: "Tests that an ADMIN can update a User, including updating the password.",
        code: 204,
        json_data: true,
      }
    },
// LOGIN UID 2
    {
      description: "Login as the updated (and re-enabled) user (UID=2)",
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
        narrative: "Tests that the updated user can login using new password",
        code: 201,
        json_data: true,
      }
    },
    {
      description: "Request listing of a single User as (UID=4) (FAILS Not Logged In)",
      enabled: true,
      debug: false,
      path: "/users/1",
      use_creds: 4,
      method: {
        type: "GET",
        querystring: {}
      },
      expected_response: {
        narrative: "Tests that you cannot list another User when not an Admin",
        code: 401,
        json_data: true,
        exact: {"error":"Missing Authentication Token"}
      }
    },
    {
      description: "Request listing of yourself when not ADMIN",
      enabled: true,
      debug: false,
      path: "/users/2",
      use_creds: 2,
      method: {
        type: "GET",
        querystring: {}
      },
      expected_response: {
        narrative: "Tests that you cannot list another User when not an Admin",
        code: 200,
        json_data: true,
        exact: [{ "ID": 2, "Username": "havocint@gmail.com", "Password": "*****", "IPAddresses": "", "Enabled": true, "Role": 2048 }]
      }
    },
    {
      description: "Insert a new User",
      enabled: true,
      debug: false,
      path: "/users",
      use_creds: 1,
      method: {
        type: "POST",
        payload: {
          "username": "someone@somewhere.com",
          "password": "123",
          "role": config.permissionLevels.NORMAL_USER
        }
      },
      expected_response: {
        narrative: "Tests that a new User can be added",
        code: 201,
        json_data: true,
      }
    },
    {
      description: "Insert a new User (FAILS - duplicate username)",
      enabled: true,
      debug: false,
      path: "/users",
      use_creds: 1,
      method: {
        type: "POST",
        payload: {
          "username": "someone@somewhere.com",
          "password": "123"
        }
      },
      expected_response: {
        narrative: "Tests that the same username cannot be added twice",
        code: 400,
        json_data: true,
        exact: { "error": "Statement 3 failed: RequestError: Cannot insert duplicate key row in object 'dbo.users' with unique index 'UK_Username'. The duplicate key value is (someone@somewhere.com)." }
      }
    },
//LOGIN- UID 5
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
        narrative: "Tests that the new user can login",
        code: 201,
        json_data: true,
      }
    },
    {
      description: "New user attempts to disable a user (FAILS - Not Authorized)",
      enabled: true,
      debug: false,
      path: "/users/2",
      use_creds: 5,
      method: {
        type: "DELETE",
      },
      expected_response: {
        narrative: "Tests that a non-ADMIN cannot disable other Users",
        code: 403,
        json_data: false,
      }
    },
    {
      description: "Logged in User refreshes existing Token",
      enabled: true,
      debug: false,
      path: "/auth/refresh",
      use_creds: 5,
      method: {
        type: "POST",
      },
      expected_response: {
        narrative: "Tests that the refresh Token can used to issue a new AuthToken",
        code: 201,
        json_data: true,
      }
    },
    {
      description: "Request User listing (using refreshed Token)",
      enabled: true,
      debug: false,
      path: "/users/5",
      use_creds: 5,
      method: {
        type: "GET"
      },
      expected_response: {
        narrative: "Tests that the updated token an be used",
        code: 200,
        json_data: true,
        exact: [{ "ID": 5, "Username": "someone@somewhere.com", "Password": "*****", "IPAddresses": "", "Enabled": true, "Role": 1 }]
      }
    },
    {
      description: "List All Connection Strings",
      enabled: true,
      debug: false,
      path: "/connections",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Tests that an ADMIN can return all connection strings",
        code: 200,
        json_data: true,
        exact: [{"ID":1,"Token":"806117D6-EE39-4664-B49E-4D069610E818","ConnectionString":"****","Description":"dummy entry","Username":"david.upton@boston.gov","Enabled":true},{"ID":2,"Token":"11666A1A-3E54-42C3-A523-9F38EEDD96F3","ConnectionString":"****","Description":"dummy entry","Username":"havocint@gmail.com","Enabled":true}]
      }
    },
    {
      description: "Update a connection string with correct server IP",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 1,
      method: {
        type: "PATCH",
        payload: {
          ConnectionString: `{"host":"${config.apiConfig.server}", "port":"${config.apiConfig.options.port}", "schema":"dbo", "database":"CMDB", "user":"admin", "password":"7sUSVGG%3g6a"}`,
          enabled: 1
        }
      },
      expected_response: {
        narrative: "Tests that an ADMIN can changes the values of some fields in connection string.",
        code: 204,
        json_data: false,
      }
    },
    {
      description: "Check Connection String was updated",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Test that the connection string is updated (token: 806117D6-EE39-4664-B49E-4D069610E818)",
        code: 200,
        json_data: true,
        exact: [{
          "ID": 1, "Token": "806117D6-EE39-4664-B49E-4D069610E818", "ConnectionString": `{"host":"${config.apiConfig.server}", "port":"${config.apiConfig.options.port}", "schema":"dbo", "database":"CMDB", "user":"admin", "password":"7sUSVGG%3g6a"}`, "Description": "dummy entry", "CreatedBy": 1, "Enabled": true
        }]
      }
    },
    {
      description: "Update a connection string with correct server IP",
      enabled: true,
      debug: false,
      path: "/connections/11666A1A-3E54-42C3-A523-9F38EEDD96F3",
      use_creds: 1,
      method: {
        type: "PATCH",
        payload: {
          ConnectionString: `{"host":"${config.apiConfig.server}", "port":"${config.apiConfig.options.port}", "schema":"dbo", "database":"${config.apiConfig.options.database}", "user":"${config.apiConfig.authentication.options.userName}", "password":"${config.apiConfig.authentication.options.password}"}`,
          enabled: 1
        }
      },
      expected_response: {
        narrative: "Tests that an ADMIN can changes the values of some fields in connection string.",
        code: 204,
        json_data: false,
      }
    },
    {
      description: "Check Connection String was updated",
      enabled: true,
      debug: false,
      path: "/connections/11666A1A-3E54-42C3-A523-9F38EEDD96F3",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Test that the connection string is updated (token: 11666A1A-3E54-42C3-A523-9F38EEDD96F3)",
        code: 200,
        json_data: true,
        exact: [{
          "ID": 2, "Token": "11666A1A-3E54-42C3-A523-9F38EEDD96F3", "ConnectionString": `{"host":"${config.apiConfig.server}", "port":"${config.apiConfig.options.port}", "schema":"dbo", "database":"${config.apiConfig.options.database}", "user":"${config.apiConfig.authentication.options.userName}", "password":"${config.apiConfig.authentication.options.password}"}`, "Description": "dummy entry", "CreatedBy": 2, "Enabled": true
        }]
      }
    },
    {
      description: "Request a single Connection String using connToken",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Tests that a connToken can be used to retrieve a connection string (token: 806117D6-EE39-4664-B49E-4D069610E818)",
        code: 200,
        json_data: true,
        exact: [{ "ID": 1, "Token": "806117D6-EE39-4664-B49E-4D069610E818", "ConnectionString": `{"host":"${config.apiConfig.server}", "port":"${config.apiConfig.options.port}", "schema":"dbo", "database":"CMDB", "user":"admin", "password":"7sUSVGG%3g6a"}`,"Description":"dummy entry","CreatedBy":1,"Enabled":true}]
      }
    },
    {
      description: "ADMIN insert a new connection string",
      enabled: true,
      debug: false,
      path: "/connection",
      use_creds: 1,
      method: {
        type: "POST",
        payload: {
          connectionString: "mysql:123.123.123.123:3000/hellp@123",
          description: "A new test connection string",
          createdBy: 0
        }
      },
      expected_response: {
        narrative: "Tests that a new connection string can be added by an Admin",
        code: 201,
        json_data: false,
      }
    },
    {
      description: "Insert a new connection string (FAILS - Not an ADMIN)",
      enabled: true,
      debug: false,
      path: "/connection",
      use_creds: 5,
      method: {
        type: "POST",
        payload: {
          connectionString: "mysql:123.123.123.123:3000/hellp@123",
          description: "A new test connection string",
          createdBy: 0
        }
      },
      expected_response: {
        narrative: "Tests that a only ADMIN can insert a new connection string",
        code: 403,
        json_data: false,
      }
    },
    {
      description: "Disable a connection string",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 1,
      method: {
        type: "DELETE",
      },
      expected_response: {
        narrative: "Tests that an ADMIN can disable a connection string (806117D6-EE39-4664-B49E-4D069610E818)",
        code: 204,
        json_data: false,
      }
    },
    {
      description: "Check Connection String was disabled",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Test that the connection string is disabled (token: 806117D6-EE39-4664-B49E-4D069610E818)",
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
      use_creds: 1,
      method: {
        type: "PATCH",
        payload: {
          description: "Updated Dummy",
          enabled: 1
        }
      },
      expected_response: {
        narrative: "Tests that an ADMIN can changes the values of some fields in connection string.",
        code: 204,
        json_data: false,
      }
    },
    {
      description: "Check Connection String was updated",
      enabled: true,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Tests that the changed fields are changed (token: 806117D6-EE39-4664-B49E-4D069610E818)",
        code: 200,
        json_data: true,
        exact: [{ "ID": 1, "Token": "806117D6-EE39-4664-B49E-4D069610E818", "ConnectionString": `{"host":"${config.apiConfig.server}", "port":"1433", "schema":"dbo", "database":"CMDB", "user":"admin", "password":"7sUSVGG%3g6a"}`,"Description":"Updated Dummy","CreatedBy":1,"Enabled":true}]
      }
    },
    {
      description: "Retrieve connectionString (FAIL - you are not permissioned).",
      enabled: true,
      debug: false,
      path: "/connections/11666a1a-3e54-42c3-a523-9f38eedd96f3",
      use_creds: 5,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Tests that a User cannot request a connectionString they are not authorized for - should get a 403.",
        code: 403,
      }
    },
    {
      description: "List connection strings available to a user (by userid)",
      enabled: true,
      debug: false,
      path: "/users/1/connections",
      use_creds: 1,
      method: {
        type: "GET",
      },
      expected_response: {
        narrative: "Tests that an ADMIN User can get a list of connectionStrings a User is permissioned for",
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
      use_creds: 1,
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
