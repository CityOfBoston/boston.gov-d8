<?php

namespace Drupal\bos_email\Plugin\QueueWorker;

use Drupal\bos_email\Services\DrupalService;
use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Exception;

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
  public function processItem($item): void {
    try {

      $config = \Drupal::configFactory()->get("bos_email.settings");

      if (!$config->get("q_enabled")) {
        throw new \Exception("All queues are paused by settings at /admin/config/system/boston/email_services.");
      }
      elseif (!empty($item["server"])
        && !$config->get(strtolower($item["server"]))["q_enabled"]) {
        throw new \Exception("The queue for {$item["server"]} is paused by settings at /admin/config/system/boston/email_services.");
      }

      if (intval($item["senddatetime"]) <= strtotime("Now")) {
        if (!empty($item["senddatetime"])) {
          unset($item["senddatetime"]);
        }
        if (!empty($item["send_date"])) {
          unset($item["send_date"]);
        }

        // load the correct email service.
        if (!empty($item["service"])) {
          try {
            $email_ops = new $item["service"];
          }
          catch (Exception $e) {}
        }

        if (empty($email_ops)) {
          // Defaults to Drupal so we can always send an email.
          $email_ops = new DrupalService();
        }

        // This will throw an error if the mail does not send, and will cause
        // the item to remain in the queue.
        $send = $email_ops->sendEmail($item);

      }
      else {
        // Delay the resending of this email until its scheduled time.
        throw new DelayedRequeueException(
          intval($item["senddatetime"]) - strtotime("Now"),
          "Email scheduled for the future."
        );
      }

      if (!$send) {
        throw new \Exception("There was a problem in bos_email:PostmarkService. {$email_ops->error}");
      }

    }
    catch (DelayedRequeueException $e) {
      throw new DelayedRequeueException($e->getDelay(), $e->getMessage());
    }
    catch (\Exception $e) {
      \Drupal::logger("contactform")->error($e->getMessage());
      throw new \Exception($e->getMessage());
    }

  }

}
