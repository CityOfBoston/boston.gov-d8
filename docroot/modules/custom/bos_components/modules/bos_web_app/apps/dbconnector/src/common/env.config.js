module.exports = {
  "port": 3600,
  "appEndpoint": "http://localhost:3600",
  "apiEndpoint": "http://localhost:3600",
  "jwt_secret": "myS33!!creeeT",
  "jwt_expiration_in_seconds": "180s",
  // Abuse Strategy: if more than flood_level API requests received in flood_time, then
  // the requests get silently rejected with a 500 error.
  // This is the time block to count to see if flood_level has been exceeded
  "flood_time": 30,
  // This is the maxmum number of API requests by a user in the flood_time
  "flood_level": 60,
  "environment": "dev",
  "permissionLevels": {
    //  This is a regular user who can make requests.
    "NORMAL_USER": 1,
    // This is a Super-user who can make enhanced requests.
    "SUPER_USER": 4,
    // This is an administrator who can make all requests and do admin actions.
    "ADMIN_USER": 2048,
    // This equates to user #1 == owner.
    // Automatically given all rights.
    "OWNER": 4096
  },
  "apiConfig":{
    server: "172.20.0.3",
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
  }
};
