<?php

namespace Drupal\bos_email\Plugin\QueueWorker;

use Drupal;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\bos_email\Controller\PostmarkVars;

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
    // Send emails via Postmark.

    $item_array = unserialize($item[0]->data);
    $item_array_encode = json_encode($item_array);
    $item_array_decode = json_decode($item_array_encode, TRUE);

    $postmark_env = new PostmarkVars();
    $server_token = $item_array["server"] . "_token";
    $server_token = $postmark_env->varsPostmark()[$server_token];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $item_array_decode["postmark_endpoint"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $item_array_encode);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Accept: application/json",
      "Content-Type: application/json",
      "X-Postmark-Server-Token: " . $server_token,
    ]);

    $response = curl_exec($ch);
    $response_json = json_decode($response, TRUE);

    return strtolower($response_json["Message"]);

  }

}
