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
      'connectionString' in req.body && req.body.connectionString != ""
    ) {
      return next();
    }
    else {
      console.log("Malformed payload in body");
      return res.status(400).send({ error: 'malformed payload' });
    }
  }
  else {
    console.log("No payload was supplied in body")
    return res.status(400).send({ error: 'need to pggass a payload field' });
  }
};
