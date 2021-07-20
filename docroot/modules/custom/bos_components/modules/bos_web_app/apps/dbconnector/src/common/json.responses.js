
/**
 * This is a wrapper to send a properly formatted JSON response (as a string) to the caller.
 *
 * @param {*} res The response object
 * @param {string} http_code The HTTP_STATUS code to return to the caller
 * @param {string|Object} body The payload to return in the response body
 * @returns Terminates the endpoint request by returning the response to the caller.
 */
exports.json_response = (res, http_code, body) => {

  switch (http_code) {
    case 204, 403:
      // These http_status codes should not have any body.
      body = "";
      break;

    default:
      break;
  }

  if (!body || typeof body === "undefined" || body == "") {
    return res.status(parseInt(http_code)).send();
  }
  else {
    if (typeof body !== "string") {
      body = JSON.stringify(body);
    }
    return res.status(parseInt(http_code)).send(body);
  }
};
