exports.json_response = (res, http_code, response) => {
  res.status(http_code).send(response);
};

exports.json_error = (res, http_code, message) => {
  if (!message || typeof message === "undefined" || message == "") {
    res.status(http_code).send();
  }
  else {
    res.status(http_code).send(message);
  }
};
