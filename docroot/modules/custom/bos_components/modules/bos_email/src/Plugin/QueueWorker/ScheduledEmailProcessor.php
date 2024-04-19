<?php

namespace Drupal\bos_email\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\bos_email\Controller\PostmarkOps;

/**
 * Processes emails through Postmark API.
 *
 * @QueueWorker(
 *   id = "scheduled_email",
 *   title = @Translation("Queues emails for later sending."),
 *   cron = {"time" = 15}
 * )
 */
class ScheduledEmailProcessor extends QueueWorkerBase {

  /**
   * Process each record.
   *
   * @param mixed $item
   *   The item stored in the queue.
   */
  public function processItem($item) {
    try {

      $config = \Drupal::configFactory()->get("bos_email.settings");

      if (!$config->get("q_enabled")) {
        throw new \Exception("All queues are paused by settings at /admin/config/system/boston/email_services.");
      }
      elseif (!empty($item["server"])
        && !$config->get(strtolower($item["server"]))["q_enabled"]) {
        throw new \Exception("The queue for {$item["server"]} is paused by settings at /admin/config/system/boston/email_services.");
      }

      if (!empty($item["postmark_error"])) {
        unset($item["postmark_error"]);
      }

      if ($item["senddatetime"] <= strtotime("Now")) {
        $email_ops = new PostmarkOps();
        $postmark_send = $email_ops->sendEmail($item);
      }

      if (!$postmark_send) {
        throw new \Exception("There was a problem in bos_email:PostmarkOps. {$email_ops->error}");
      }

    }
    catch (\Exception $e) {
      \Drupal::logger("contactform")->error($e->getMessage());
      throw new \Exception($e->getMessage());
    }

  }

}
