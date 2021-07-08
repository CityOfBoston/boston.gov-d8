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
      enabled: false,
      debug: false,
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
      enabled: false,
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
        exact: [{"ID":3,"Username":"havocint@hotmail.com","Password":"*****","IPAddresses":"","Enabled":true,"Role":4},{"ID":4,"Username":"david","Password":"*****","IPAddresses":"","Enabled":true,"Role":1}]
      }
    },
    {
      description: "List single user",
      enabled: false,
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
        exact: [{"ID":1,"Username":"david.upton@boston.gov","Password":"*****","IPAddresses":"","Enabled":true,"Role":4096}]
      }
    },
    {
      description: "Add a single user",
      enabled: false,
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
      enabled: false,
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
      }
    },
    {
      description: "Try to fetch a user that is not yourself when not an admin",
      enabled: false,
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
        json_data: false,
      }
    },
    {
      description: "Disable a user",
      enabled: false,
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
      enabled: false,
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
      enabled: false,
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
      enabled: false,
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
      enabled: false,
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
      enabled: false,
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
      enabled: false,
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
      enabled: false,
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
        exact: [{"ID":1,"Token":"806117D6-EE39-4664-B49E-4D069610E818","ConnectionString":"test/12345:abd database=abc","Description":"dummy entry","CreatedBy":1,"Enabled":true}]
      }
    },
    {
      description: "Insert a new connection string",
      enabled: false,
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
      enabled: false,
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
      enabled: false,
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
        exact: [{"ID":1,"Token":"806117D6-EE39-4664-B49E-4D069610E818","ConnectionString":"test/12345:abd database=abc","Description":"dummy entry","CreatedBy":1,"Enabled":false}]
      }
    },
    {
      description: "Update a connection string",
      enabled: false,
      debug: false,
      path: "/connections/806117D6-EE39-4664-B49E-4D069610E818",
      use_creds: 0,
      method: {
        type: "PATCH",
        payload: {
          connectionString: "test/12345:abd database=abcde",
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
      enabled: false,
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
        exact: [{"ID":1,"Token":"806117D6-EE39-4664-B49E-4D069610E818","ConnectionString":"test/12345:abd database=abcde","Description":"dummy entry","CreatedBy":1,"Enabled":true}]
      }
    },
    {
      description: "Try to get connectionstring you are not permissioned for.",
      enabled: false,
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
      enabled: false,
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
        exact: [{"Username":"david.upton@boston.gov","userid":1,"connid":1,"Token":"806117D6-EE39-4664-B49E-4D069610E818","ConnectionString":"test/12345:abd database=abcde","Description":"dummy entry","Enabled":true,"Count":0}]
      }
    },
    {
      description: "List connection strings available to a user (by username)",
      enabled: false,
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
        exact: [{"Username":"david.upton@boston.gov","userid":1,"connid":1,"Token":"806117D6-EE39-4664-B49E-4D069610E818","ConnectionString":"test/12345:abd database=abcde","Description":"dummy entry","Enabled":true,"Count":0}]
      }
    },
    {
      description: "List users who can use a connection string",
      enabled: false,
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
      enabled: false,
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
      enabled: false,
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
      description: "Execute some SQL",
      enabled: true,
      debug: true,
      path: "/query/mssql",
      use_creds: 0,
      method: {
        type: "POST",
        payload: {
          'statement': 'SELECT * FROM dbo.{a}',
          'connectionString': '{"host":"172.18.0.2", "port":"1433", "schema":"dbo", "db":"cmdb", "user":"dbconnector", "password":""}',
          'args': {"a": "cmdb"},
        }
      },
      expected_response: {
        narrative: "Runs sql",
        code: 204,
        json_data: false,
      }
    },
  ]

};
