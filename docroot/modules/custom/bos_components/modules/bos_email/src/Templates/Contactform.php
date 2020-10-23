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
   *   The message sent from user.
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
