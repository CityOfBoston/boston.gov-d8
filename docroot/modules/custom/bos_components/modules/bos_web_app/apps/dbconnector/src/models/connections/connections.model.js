/**
 * Functions which manipulate data within the connTokens table.
 */
const sql_exec = require('../../common/services/tedious.exec.service')

/**
 * A structure which represents the columns in connTokens table.
 * This structure is used for create and updates.
 */
const connSchema = {
  'id': 0,
  'connectionString': '',
  'description': '',
  'createdDate': 'GETDATE()',
  'createdBy': 0,
  'enabled': 1
};

String.prototype.trimRight = function(charlist) {
  if (charlist === undefined)
  charlist = "\s";

  return this.replace(new RegExp("[" + charlist + "]+$"), "");
  };

/**
 * Finds a record from its Token.
 * @param  {String} id A connection Token.
 * @return {Object} An object with the connToken record (incl ConnectionString).
 */
exports.findByToken = (id) => {

  return new Promise((resolve, reject) => {

    sql = `SELECT
              ID,
              Token,
              ConnectionString,
              Description,
              CreatedDate,
              CreatedBy,
              Enabled
            FROM dbo.connTokens
           WHERE token = '${id}';`

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
 * Inserts a new connectionstring into the connTokens table..
 * @param  {connSchema} connData An object with fields mapping to the connSchema object.
 * @return {String} The Token for the connection just created.
 */
exports.create = (connData) => {
  return new Promise((resolve, reject) => {
    // Cleanup the input data.
    if (typeof connData.token === "undefined" || connData.token.trim() == "") {
      // If there is no token provided, then remove it so one gets created by default.
      delete connData.token;
    }
    if (typeof connData.description === "undefined" || connData.description.trim() == "") {
      connData.description = `API Insert`;
    }
    if (typeof connData.createdDate !== "undefined") {
      // Always want to remove this so the server adds its own time to the creation.
      delete connData.createdDate;
    }
    if (!'createdBy' in connData || connData.createdBy == 0 || connData.createdBy == "") {
      reject("Must identify the creators UserID");
    }

    // Create a data object with all fields from the schema, and supplied or default values.
    let data = {
      ...connSchema,
      ...connData
    }
console.log(JSON.stringify(data))
    sql = `
    INSERT INTO dbo.connTokens(
        Token,
        ConnectionString,
        Description,
        CreatedDate,
        CreatedBy,
        Enabled)
      VALUES(
        NEWID(),
        '${data.connectionString}',
        '${data.description}',
        ${data.createdDate},
        ${data.createdBy},
        ${data.enabled}
      );
    SELECT Token as connTokens
      FROM dbo.connTokens
    WHERE ID = @@IDENTITY;`

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        resolve(rows[1][0].connToken);
      }
    });

  })
};

/**
 * Provides a paged list of connection strings in the connTokens table..
 * @param  {Number} perPage Number of records per page.
 * @param  {Number} page The page to return.
 * @return {Array} An array of n=perPage records.
 */
exports.list = (perPage, page) => {
  return new Promise((resolve, reject) => {

    let offset = perPage * page;

    sql = `SELECT
              tok.ID,
              tok.Token,
              '****' as ConnectionString,
              tok.Description,
              tok.CreatedDate,
              users.Username,
              tok.Enabled
           FROM dbo.connTokens tok
              LEFT JOIN dbo.users users on tok.createdBy = users.ID
           ORDER BY tok.ID ASC
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
 * Updates an existing connectionstring into the connTokens table.
 * @param  {Number} id The ID for a connToken record.
 * @param  {connSchema} connData An object with fields mapping to the connSchema object.
 * @return {String} Narrative of what happened.
 */
exports.update = (id, connData) => {
  let nothingDone = 'Not Found';

  return new Promise((resolve, reject) => {
    let fields = "";
    for (const field in connData) {
      if (field == 'createdBy' || field == 'enabled') {
        fields += `${field} = ${connData[field]},`;
      }
      else {
        fields += `${field} = '${connData[field]}',`;
      }
    }
    fields = fields.trimRight(",");
    sql = `
      IF EXISTS (SELECT * FROM dbo.connTokens WHERE token = '${id}')
        BEGIN
          SELECT 'Updated' as action;

          UPDATE dbo.connTokens
            SET ${fields}
          WHERE token = '${id}';
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
 * Disables a record in the connTokens table.
 * @param  {Number} id The ID for a connToken record.
 * @return {String} Narrative of what happened.
 */
exports.disableByToken = (id) => {

  let nothingDone = 'Not Found';

  return new Promise((resolve, reject) => {
    sql = `
        UPDATE dbo.connTokens
            SET Enabled = 0
          WHERE token = '${id}';
        IF @@ROWCOUNT > 0
          SELECT 'Removed' as action;
        ELSE
          SELECT '${nothingDone}' as action;    `

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        if (rows[1][0].action == nothingDone) {
          reject(nothingDone)
        }
        else {
          resolve(rows[1][0]);
        }
      }
    });

  })

};

/**
 * IMPLEMENTING CONNECTIONS <=> USERS
 */


/**
 * Finds all connections that a user can access (plus stats) from UserID (ID).
 * @param  {Number} id A user ID.
 * @return {Object} An object populated with the connections.
 */
 exports.findConnectionsByUserId = (id) => {

  return new Promise((resolve, reject) => {

    sql = `SELECT
              u.Username,
              u.ID as userid,
              conn.ID as connid,
              conn.Token,
              conn.ConnectionString,
              conn.Description,
              conn.Enabled,
              map.Count,
              map.LastUse
          FROM dbo.users u
            INNER JOIN dbo.permissionsMap map ON u.ID = map.UserID
            INNER JOIN dbo.connTokens conn ON map.ConnID = conn.ID
          WHERE u.ID = '${id}';`

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
 * Finds all connections that a user can access (plus stats) from username.
 * @param  {String} username A username.
 * @return {Object} AAn object populated with the connections.
 */
 exports.findConnectionsByUsername = (username) => {

  return new Promise((resolve, reject) => {

    sql = `SELECT
              u.Username,
              u.ID as userid,
              conn.ID as connid,
              conn.Token,
              conn.ConnectionString,
              conn.Description,
              conn.Enabled,
              map.Count,
              map.LastUse
           FROM dbo.users u
             INNER JOIN dbo.permissionsMap map ON u.ID = map.UserID
             INNER JOIN dbo.connTokens conn ON map.ConnID = conn.ID
           WHERE u.Username = '${username}';`

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
 * Finds all users permitted to use a token (plus stats).
 * @param  {String} token A valid connection Token.
 * @return {Object} An object with the user records.
 */
 exports.findUsersByToken = (token) => {

  return new Promise((resolve, reject) => {

    sql = `SELECT
              u.ID,
              u.Username,
              u.Enabled,
              map.Count,
              map.LastUse
           FROM dbo.users u
             INNER JOIN dbo.permissionsMap map ON u.ID = map.UserID
             INNER JOIN dbo.connTokens conn ON map.ConnID = conn.ID
           WHERE conn.Token= '${token}';`

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
 * Inserts a new mapping between a connection and a user: this implies a permission.
 * @param  {String} token A valid connection Token.
 * @param  {Number} id A user ID.
 * @return {String} Narrative of what happened.
 */
 exports.createMapping = (token, id) => {
  return new Promise((resolve, reject) => {
      sql = `INSERT INTO dbo.permissionsMap (
                UserID,
                ConnID)
              SELECT ${id}, tok.ID
                FROM dbo.connTokens tok
              WHERE Token = '${token}';`

      sql_exec.exec(sql, function (rows, err) {
        if (err) {
          reject(err);
        }
        else {
          resolve({"result": "Done"});
        }
      });
  })
};

/**
 * Deletes a record in the permissionsMap table. Removes a permission.
 * @param  {String} token A valid connection Token.
 * @param  {Number} id A user ID.
 * @return {String} Narrative of what happened.
 */
 exports.deleteMapping = (token, id) => {

  let nothingDone = 'Not Found';

  return new Promise((resolve, reject) => {
    sql = `
      DELETE dbo.permissionsMap
      FROM dbo.permissionsMap map
        INNER JOIN dbo.connTokens tok ON map.ConnID = tok.ID
        WHERE map.UserID = ${id} AND tok.Token = '${token}'

      IF @@ROWCOUNT > 0
        SELECT 'Removed' as action;
      ELSE
        SELECT '${nothingDone}' as action;
    `

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        if (rows[1][0].action == nothingDone) {
          reject(nothingDone)
        }
        else {
          resolve(rows[1][0]);
        }
      }
    });

  })

};

/**
 * Records the use of a connection token by a user.
 * @param  {String} token A valid connection Token..
 * @param  {Number} id A user ID.
 * @return {Object} Narrative on result.
 */
 exports.incrementConnectionUse = (token, id) => {

  return new Promise((resolve, reject) => {

    sql = `
      UPDATE dbo.permissionsMap
      SET
        Count = Count + 1,
        LastUse = GETDATE()
      FROM dbo.permissionsMap
        INNER JOIN dbo.connTokens on dbo.permissionsMap.ConnID = dbo.connTokens.ID
      WHERE dbo.connTokens.Token= '${token}';
    `

    sql_exec.exec(sql, function (rows, err) {
      if (err) {
        reject(err);
      }
      else {
        resolve({"result": "Done"});
      }
    });

  });

};
