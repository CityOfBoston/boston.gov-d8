<?php

namespace Drupal\bos_assessing\Templates;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;

/**
 * Postmark variables for email API.
 */
class Section extends ControllerBase {

  /**
   * Test HTML output.
   */
  public function getMarkup() {

    $markup = "<div>Matt Test</div>";

    return $markup;
  }

  /**
   * Get vars for Postmark servers.
   */
  public function getVars() {

    if (isset($_ENV['POSTMARK_SETTINGS'])) {
      $postmark_env = [];
      $get_vars = explode(",", $_ENV['POSTMARK_SETTINGS']);
      foreach ($get_vars as $item) {
        $json = explode(":", $item);
        $postmark_env[$json[0]] = $json[1];
      }
    }
    else {
      $postmark_env = [
        "registry_token" => Settings::get('postmark_settings')['registry_token'],
        "contactform_token" => Settings::get('postmark_settings')['contactform_token'],
        "commissions_token" => Settings::get('postmark_settings')['commissions_token'],
        "auth" => Settings::get('postmark_settings')['auth'],
      ];
    }

    return $postmark_env;
  }

  /**
   * Send email to Postmark.
   */
  public function sendEmail($item) {

    // Send emails via Postmark API and cURL.
    $item_json = json_encode($item);

    $server_token = $item["server"] . "_token";
    $server_token = $this->getVars()[$server_token];

    try {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $item["postmark_endpoint"]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $item_json);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json",
        "Content-Type: application/json",
        "X-Postmark-Server-Token: " . $server_token,
      ]);

      $response = curl_exec($ch);
      $response_json = json_decode($response, TRUE);

      return (strtolower($response_json["Message"]) == "ok");

    }
    catch (Exception $e) {
      return FALSE;
    }
  }

  // End sendEmail.

}
// End PostmarkOps.
