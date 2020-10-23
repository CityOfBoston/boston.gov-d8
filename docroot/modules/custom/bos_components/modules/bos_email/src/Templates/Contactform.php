<?php

namespace Drupal\bos_email\Templates;

use Drupal\Core\Controller\ControllerBase;

/**
 * Template class for Postmark API.
 */
class Contactform extends ControllerBase {

  /**
   * Template for plain text message.
   *
   * @param string $message_sender
   *   The message sent by the user.
   * @param string $name
   *   The name supllied by the user.
   * @param string $from_address
   *   The from address supplied by the user.
   * @param string $url
   *   The page url from where form was submitted.
   */
  public static function templatePlainText($message_sender, $name, $from_address, $url) {

    $message = "-- REPLY ABOVE THIS LINE -- \n\n";
    $message .= $message_sender . "\n\n";
    $message .= "-------------------------------- \n";
    $message .= "This message was sent using the contact form on Boston.gov.";
    $message .= " It was sent by " . $name . " from " . $from_address . ".";
    $message .= " It was sent from " . $url . ".\n\n";
    $message .= "-------------------------------- \n";

    return $message;
  }

}
