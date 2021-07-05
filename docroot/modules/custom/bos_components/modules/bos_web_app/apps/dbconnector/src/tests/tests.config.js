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
      description: "Login as OWNER user (UID=1)",
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
        exact: [{"ID":3,"Username":"havocint@hotmail.com","Password":"*****","IPAddresses":"","Enabled":true,"Role":4},{"ID":4,"Username":"david","Password":"*****","IPAddresses":"","Enabled":true,"Role":1}]
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
        exact: [{"ID":1,"Username":"david.upton@boston.gov","Password":"*****","IPAddresses":"","Enabled":true,"Role":4096}]
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
          "role": 2048
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
      description: "Update a single user (and re-enable)",
      enabled: true,
      debug: false,
      path: "/users/2",
      use_creds: 0,
      method: {
        type: "PATCH",
        payload: {
          "username": "havocint@gmail.com",
          "password": "new",
          "role": 1024,
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
  ]
};
