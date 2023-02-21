<?php

namespace Drupal\bos_email\Controller;

use Drupal\Core\Site\Settings;
use Exception;

/**
 * Postmark variables for email API.
 */
class PostmarkOps {

  public string $error;

  private bool $debug = FALSE;

  /**
   * Check token and authenticate.
   */
  public function checkAuth($post) {

   $matches = [];
   // Read the auth key from settings.
   $token = $this->getVars()["auth"];
   // Fetch the token from the posted form.
   $post_token = explode("Token ",$post);
   $quad_chunk = str_split($post_token[1], 4);

   foreach ($quad_chunk as $item) {
      if (str_contains($token, $item)) {
        array_push($matches, $item);
      }
    }

    return count(array_unique($matches)) == 15;
  }

  /**
   * Get vars for Postmark servers.
   */
  public function getVars() {

    $postmark_env = [];
    if (getenv('POSTMARK_SETTINGS')) {
      $get_vars = explode(",", getenv('POSTMARK_SETTINGS'));
      foreach ($get_vars as $item) {
        $json = explode(":", $item);
        if (!empty($json[0]) && !empty($json[1])) {
          $postmark_env[$json[0]] = $json[1];
        }
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

    $this->debug = str_contains(\Drupal::request()->getHttpHost(), "lndo.site");

    // Send emails via Postmark API and cURL.
    $item_json = json_encode($item);

    try {
      $server_token = $item["server"] . "_token";
      if (empty($this->getVars()[$server_token])) {
        throw new \Exception("Cannot find token for {$item['server']}");
      }
      $server_token = $this->getVars()[$server_token];

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
      $response_json = curl_exec($ch);

      if (!$response_json) {
        if ($e = curl_error($ch)) {
          throw new \Exception("Error from Curl: {$e}");
        }
        else {
          throw new \Exception("Unknown Error");
        }
      }
      $response = json_decode($response_json, TRUE);

      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($http_code != 200) {
        throw new \Exception("Postmark returns {$http_code}");
      }

      if ($this->debug) {
        \Drupal::logger("bos_email:PostmarkOps")
          ->info("<table><tr><td>Email</td><td>{$item_json}</td>
                          </tr><tr><td>Response</td><td>{$response_json}</td></tr>
                          </tr><tr><td>HTTPCode</td><td>{$http_code}</td></tr>
                          </table>");
      }

      if (strtolower($response["ErrorCode"]) != "0") {
        throw new \Exception("Postmark responds: {$response['ErrorCode']} - {$response['Message']}, Postmark internal ID: {$response['MessageID']}");
      }

      return TRUE;

    }
    catch (Exception $e) {
      $this->error = $e->getMessage();
      \Drupal::logger("bos_email:PostmarkOps")->error($e->getMessage());
      return FALSE;
    }
  }
}
