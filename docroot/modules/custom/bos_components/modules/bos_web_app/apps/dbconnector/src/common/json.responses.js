exports.json_response = (res, http_code, response, type) => {
  res.status(http_code).send(response);
};

exports.json_error = (res, http_code, message) => {
  res.status(http_code).send(message)
};
