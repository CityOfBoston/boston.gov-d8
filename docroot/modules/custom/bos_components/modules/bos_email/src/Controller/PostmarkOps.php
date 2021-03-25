<?php

namespace Drupal\bos_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;

/**
 * Postmark variables for email API.
 */
class PostmarkOps extends ControllerBase {

  /**
   * Check token and authenticate.
   */
  public function checkAuth($post) {

   $matches = [];
   $token = $this->getVars()["auth"];
   $post_token = explode("Token ",$post);
   $quad_chunk = str_split($post_token[1], 4);
   
   foreach ($quad_chunk as $item) {
        $pos = strpos($token, $item);
        if ($pos !== false) {
          array_push($matches, $item);
        }
    }

    return $valid = (count(array_unique($matches)) == 15 ? TRUE : FALSE);
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
