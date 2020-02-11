<?php

namespace Drupal\slackposter\Integrate;

/**
 * This class creates a standardized response.
 */
class SlackRestResponse {

  /**
   * Error.
   *
   * @var string
   */
  protected $error;
  /**
   * Result.
   *
   * @var string
   */
  protected $result;
  /**
   * Message.
   *
   * @var array
   */
  protected $message;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $message = [], bool $success = TRUE, string $errMessage = "") {
    $this->setResult($success, $errMessage);
    $this->setMessage($message);
    return $this;
  }

  /**
   * Updates properties in the class with post result and any error messages.
   *
   * @param bool $success
   *   Whether the posting was successful.
   * @param string $errMessage
   *   The error message if the post failed.
   *
   * @return \Drupal\slackposter\Integrate\SlackRestResponse
   *   This class object with the result & error fields populated.
   */
  public function setResult(bool $success, string $errMessage = "") {
    $this->result = ($success ? "OK" : "error");
    if (!$success && $errMessage != "") {
      $this->setError($errMessage);
    }
    return $this;
  }

  /**
   * Set the error property in this class.
   *
   * @param string $errorMessage
   *   The error message to set.
   *
   * @return \Drupal\slackposter\Integrate\SlackRestResponse
   *   This class object with the error message populated.
   */
  public function setError(string $errorMessage) {
    $this->error = $errorMessage;
    return $this;
  }

  /**
   * Set a message property on this class.
   *
   * @param array $message
   *   Array of messages.
   *
   * @return \Drupal\slackposter\Integrate\SlackRestResponse
   *   This class with the message set.
   */
  public function setMessage(array $message) {
    $this->message = $message;
    return $this;
  }

  /**
   * Output this class as an array.
   *
   * @return array
   *   Properties of this class as an array.
   */
  public function toArray() {
    $arr = [
      'result' => $this->result,
      'message' => $this->message,
    ];
    if ($this->result == 'error') {
      $arr['error'] = $this->error;
    }

    return $arr;
  }

}
