/**
 * Functions which manipulate data within the users table.
 */
const sql_exec = require('../../common/services/tedious.exec.service')

/**
 * A structure which represents the columns in users table.
 * This structure is used for create and updates.
 */
const userSchema = {
  userid: 0,
  username: '',
  password: '',
  ipaddresses: '',
  enabled: 1,
  role: 1,
  session: ''
};

String.prototype.trimRight = function(charlist) {
  if (charlist === undefined)
  charlist = "\s";

  return this.replace(new RegExp("[" + charlist + "]+$"), "");
};

/**
 * Finds a user from its username.
 * @param  {String} username A username.
 * @return {Object} An object with the user record.
 */
exports.findByUsername = (username) => {

  return new Promise((resolve, reject) => {

    // Need to return an un-obvuscated password so that password verification
    // can occur.
    sql = `SELECT ID, Username, Password, IPAddresses, Enabled, Role, Session
           FROM dbo.users
           WHERE Username = '${username}';`

    // console.log(sql);

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        console.log("error" + err);
        reject(err);
      }
      else {
        // console.log("Exec Rows: " + rows)
        if (typeof rows === "undefined" || rows == [] || rows == "") {
          reject("Username not found");
        }
        else {
          resolve(rows[0]);
        }
      }
    });

  });

};

/**
 * Finds a user from its UserID (ID).
 * @param  {String} id A user ID.
 * @return {Object} An object with the user record.
 */
 exports.findByUserId = (id) => {

  return new Promise((resolve, reject) => {

    sql = `SELECT ID, Username, '*****' as Password, IPAddresses, Enabled, Role, Session
           FROM dbo.users
           WHERE ID = ${id};`

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        resolve(rows[0]);
      }
    });

  });

};

/**
 * Inserts a new user into the users table.
 * @param  {userSchema} userData An object with fields mapping to the userSchema object.
 * @return {String} The ID for the user just created.
 */
exports.create = (userData) => {
  return new Promise((resolve, reject) => {
    userSchema.IPAddresses = "";
    let data = {
      ...userSchema,
      ...userData
    }
    sql = `INSERT INTO dbo.users(
              Username,
              Password,
              IPAddresses,
              Enabled,
              Role)
            VALUES(
              '${data.username}',
              '${data.password}',
              '${data.ipaddresses}',
              ${data.enabled},
              '${data.role}'
            );
            SELECT @@IDENTITY as 'id';`

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        resolve(rows[1][0].id);
      }
    });

  })
};

/**
 * Provides a paged list of users.
 * @param  {Number} perPage Number of records per page.
 * @param  {Number} page The page to return.
 * @return {Array} An array of n=perPage records.
 */
exports.list = (perPage, page) => {
  return new Promise((resolve, reject) => {

    let offset = perPage * page;

    sql = `SELECT ID, Username, '*****' as Password, IPAddresses, Enabled, Role
           FROM dbo.users
           ORDER BY ID ASC
           OFFSET ${offset} ROWS FETCH NEXT ${perPage} ROWS ONLY;`

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        resolve(rows[0]);
      }
    });

  });
};

/**
 * Updates an existing user in the users table.
 * @param  {Number} id The ID for a user.
 * @param  {userSchema} userData An object with fields mapping to the userSchema object.
 * @return {String} Narrative of what happened.
 */
exports.update = (id, userData) => {
  let nothingDone = 'Not Found';

  return new Promise((resolve, reject) => {
    let fields = "";
    for (const field in userData) {
      fields += `${field} = '${userData[field]}',`
    }
    fields = fields.trimRight(",");
    sql = `
      IF EXISTS (SELECT * FROM dbo.users WHERE ID = ${id})
        BEGIN
          SELECT 'Updated' as action;

          UPDATE dbo.users
            SET ${fields}
          WHERE ID = ${id};
        END

      ELSE
        SELECT '${nothingDone}' as action;
    `

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        if (rows[0][0].action == nothingDone) {
          reject(nothingDone)
        }
        else {
          resolve(rows[0][0]);
        }
      }
    });

  })

};

/**
 * Disables a record in the users table.
 * @param  {Number} id The ID for a user.
 * @return {String} Narrative of what happened.
 */
exports.disableById = (userId) => {

  let nothingDone = 'Not Found';

  return new Promise((resolve, reject) => {
    sql = `
      IF EXISTS (SELECT * FROM dbo.users WHERE ID = ${userId})
        BEGIN
          SELECT 'Removed' as action;

          UPDATE dbo.users
            SET Enabled = 0
          WHERE ID = ${userId};
        END

      ELSE
        SELECT '${nothingDone}' as action;
    `

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        if (rows[0][0].action == nothingDone) {
          reject(nothingDone)
        }
        else {
          resolve(rows[0][0]);
        }
      }
    });

  })

};
