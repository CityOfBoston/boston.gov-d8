<?php

namespace Drupal\bos_email\Controller;

/**
 * Class to create (and save), remove, and get/verify a session token.
 */
class TokenOps {

  public $session;

  public function __construct() {
    $this->session = \Drupal::request()->getSession();
  }

  /**
   * Creates a session token and saves in the session object.
   *
   * @return array The new Session Token just created.
   */
  public function tokenCreate(): array {
    $date_time = \Drupal::time()->getCurrentTime();
    $this->session->set("token_session_{$date_time}", $date_time);
    return ['token_session' => $date_time];
  }

  /**
   * Removes the token from the session object.
   *
   * @param string $data The session Token to remove.
   *
   * @return string[] Status.
   */
  public function tokenRemove(string $data): array {
    if ($this->session->remove("token_session_{$data}") !== null) {
      return ['token_session' => "removed"];
    }
    return ['token_session' => "not found"];
  }

  /**
   * Returns an array with token_session indicating if it exists.
   * This is usually used to check if a session token is valid. The field
   * token_value is populated if the token is found.
   *
   * @param string $data The Session Token.
   *
   * @return array
   */
  public function tokenGet(string $data): array {
      if ($this->session->get("token_session_{$data}", FALSE)) {
        return [
          'token_session' => TRUE,
          'token_value' => "token_session_{$data}",
        ];
      }
      return  ['token_session' => FALSE];
  }

}
