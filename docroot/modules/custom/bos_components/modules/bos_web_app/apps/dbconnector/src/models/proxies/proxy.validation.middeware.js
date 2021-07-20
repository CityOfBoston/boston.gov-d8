/**
 * Functions which validate aspects of the Proxy services
 *
 * These functions are intended to be used in an express.js app (i.e. rest API endpoint).
 *
  */

/**
 * Validate that the body contains a payload in the required format.
 * @param {*} req
 * @param {*} res
 * @param {*} next
 * @returns next function or an error status code.
 *
 * Payload in the format
 *  {
 *     'statement': 'statement',
 *     'connectionString': ''
 *     'args': [],
 *   }
 */
exports.IsPayloadValid = (req, res, next) => {
  if ('body' in req) {
    if (
      'statement' in req.body && req.body.statement != "" &&
      'token' in req.body && req.body.token != ""
    ) {
      return next();
    }
    else {
      // console.log("Malformed payload in body");
      return res.status(400).send({ error: 'malformed payload' });
    }
  }
  else {
    // console.log("No payload was supplied in body")
    return res.status(400).send({ error: 'need to pass a payload field' });
  }
};
/**
 *Select style query can only have one statement, no use etc.
 * @param {*} req
 * @param {*} res
 * @param {*} next
 */
exports.IsSelectQueryValid = (req, res, next) => {
  if ('body' in req && 'statement' in req.body) {
    let sql = req.body.statement.trim();
    if (sql.endsWith(";")) {
      // remove trailing ;'s
      sql = sql.split(";")[0]
    }
    if (sql.includes(";")) {
      // console.log("Can only pass a single sql select in statement.")
      return res.status(400).send({ error: 'Can only pass a single sql select in statement' });
    }
    if (!sql.toLowerCase().includes("select")) {
      // console.log("Statement must be a select statement, (stored procedure not supported).")
      return res.status(400).send({
        error: 'Statement must be a select statement, (stored procedure not supported)'});
    }
    if ('body' in req && 'limit' in req.body) {
      if (!sql.toLowerCase().includes("order")) {
        // console.log("Paged query must have an order by clause.")
        return res.status(400).send({ error: 'Paged query must have order by clause' });

      }
    }
    return next();
  }
  else {
    // console.log("Missing (SQL)statement in payload.")
    return res.status(400).send({ error: 'need to pass a, statement field in payload' });
  }
}
