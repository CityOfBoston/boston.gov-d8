<?php

namespace Drupal\bos_email\Services;

use Boston;
use Drupal;
use Drupal\bos_email\EmailServiceInterface;

/**
 * Postmark class for API.
 */
class DrupalService implements EmailServiceInterface {

  const MESSAGE_SENT = 'Message sent.';
  const MESSAGE_QUEUED = 'Message queued.';

  public null|string $error;

  /**
   * @inheritDoc
   */
  public function id():string {
    return "bos_email.DrupalService";
  }

  /**
   * Send the email via Postmark.
   *
   * @param \Drupal\bos_email\CobEmail $mailobj The email object
   *
   * @return array
   */
  public function sendEmail(array $item):bool {


    /**
     * @var \Drupal\Core\Mail\MailManager $mailManager
     */
    try {

      // Send the email.
      $item["_error_message"] = "";
      $key = "{$this->service}.{$item["Tag"]}";

      $mailManager = Drupal::service('plugin.manager.mail');

      $sent = $mailManager->mail("bos_email", $key , $item["To"], "en", $item, NULL, TRUE);

      if (!$sent || !$sent["result"]) {
        if (!empty($params["_error_message"])) {
          throw new \Exception($params["_error_message"]);
        }
        else {
          throw new \Exception("Error sending email.");
        }
      }

      $response_message = self::MESSAGE_SENT;

    }
    catch (\Exception $e) {
      try {
        $this->addQueueItem($item);
      }
      catch (\Exception $ee) {
        Drupal::logger("bos_email:DrupalService")->info("Failed to queued mail item in {$item->getField("server")}");
        return [
          'status' => 'error',
          'response' => "Error sending message {$e->getMessage()}, then error queueing item {$ee->getMessage()}.",
        ];
      }

      if (Boston::is_local()) {
        Drupal::logger("bos_email:DrupalService")->info("Queued {$item->getField("server")}");
      }

      $response_message = self::MESSAGE_QUEUED;
    }


    return [
      'status' => 'success',
      'response' => $response_message,
    ];

  }

  /**
   * @inheritDoc
   */
  public function getVars(): array {
    // TODO: Implement getVars() method.
  }

}
