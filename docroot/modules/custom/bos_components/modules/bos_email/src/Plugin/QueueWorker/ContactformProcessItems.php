<?php

namespace Drupal\bos_email\Plugin\QueueWorker;

use Drupal\bos_email\Services\DrupalService;
use Drupal\Core\Annotation\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Exception;

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

      $config = \Drupal::configFactory()->get("bos_email.settings");

      if (!$config->get("q_enabled")) {
        throw new \Exception("All queues are paused by settings at /admin/config/system/boston/email_services.");
      }
      elseif (!empty($item["server"])
        && !$config->get(strtolower($item["server"]))["q_enabled"]) {
        throw new \Exception("The queue for {$item["server"]} is paused by settings at /admin/config/system/boston/email_services.");
      }

      if (!empty($item["send_error"])) {
        unset($item["send_error"]);
      }

      if (!empty($item["service"])) {
        try {
          $email_ops = new $item["service"];
        }
        catch (Exception $e) {}
      }

      if (!isset($email_ops) || empty($email_ops)) {
        // Defaults to Drupal.
        $email_ops = new DrupalService();
      }

      if (!$email_ops->sendEmail($item)) {
        throw new \Exception("There was a problem in {$email_ops->id()}. {$email_ops->error}");
      }

    }
    catch (\Exception $e) {
      \Drupal::logger("contactform")->error($e->getMessage());
      throw new \Exception($e->getMessage());
    }

  }

}
