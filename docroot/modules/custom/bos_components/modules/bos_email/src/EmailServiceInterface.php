<?php

namespace Drupal\bos_email;

interface EmailServiceInterface {

  /**
   * Send email via the Service.
   *
   * @param array $item Containing email fields for the service to send.
   *
   * @returns bool Whether the send was successful or not.
   */
  public function sendEmail(array $item): bool;

  /**
   * Fetches any specific settings for this service.
   * @return array
   */
  public function getVars():array;

}
