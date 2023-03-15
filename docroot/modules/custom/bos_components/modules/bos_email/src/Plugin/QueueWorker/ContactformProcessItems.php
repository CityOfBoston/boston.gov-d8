<?php

namespace Drupal\bos_email\Plugin\QueueWorker;

use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\bos_email\Controller\PostmarkOps;

/**
 * Processes emails through Postmark API.
 *
 * @QueueWorker(
 *   id = "email_contactform",
 *   title = @Translation("Sends emails through Postmark."),
 *   cron = {"time" = 15}
 * )
 */
class ContactformProcessItems extends QueueWorkerBase {

  /**
   * Process each record.
   *
   * @param mixed $item
   *   The item stored in the queue.
   */
  public function processItem($item) {
    try {

      $postmark_ops = new PostmarkOps();
      if (!empty($item["postmark_error"])) {
        unset($item["postmark_error"]);
      }
      $postmark_send = $postmark_ops->sendEmail($item);

      if (!$postmark_send) {
        throw new \Exception("There was a problem in bos_email:PostmarkOps. {$postmark_ops->error}");
      }

    }
    catch (\Exception $e) {
      \Drupal::logger("contactform")->error($e->getMessage());
      throw new \Exception($e);
    }

  }

}
