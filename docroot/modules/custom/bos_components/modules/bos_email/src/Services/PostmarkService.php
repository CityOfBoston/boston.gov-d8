<?php

namespace Drupal\bos_email\Services;

use Drupal\bos_core\Controllers\Curl\BosCurlControllerBase;
use Drupal\bos_email\EmailServiceInterface;
use Drupal\Core\Site\Settings;
use Exception;

/**
 * Postmark variables for email API.
 */
class PostmarkService extends BosCurlControllerBase implements EmailServiceInterface {

  // Make this protected var from BosCurlControllerBase public
  public null|string $error;

  public array $response;

  /**
   * @inheritDoc
   */
  public function id():string {
    return "bos_email.PostmarkService";
  }

  /**
   * Get vars for Postmark servers.
   */
  public function getVars(): array {

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
      $postmark_env = Settings::get('postmark_settings') ?? [];
    }

    return $postmark_env;
  }

  /**
   * Send email to Postmark.
   */
  public function sendEmail(array $item):bool {

    // Check if we are sending out emails.
    $config = \Drupal::configFactory()->get("bos_email.settings");
    if (!$config->get("enabled")) {
      $this->error = "Emailing temporarily suspended for all PostMark emails";
      \Drupal::logger("bos_email:PostmarkService")->error($this->error);
      return FALSE;
    }
    elseif ($item["server"] && !$config->get(strtolower($item["server"]))["enabled"]) {
      $this->error = "Emailing temporarily suspended for {$item["server"]} emails.";
      \Drupal::logger("bos_email:PostmarkService")->error($this->error);
      return FALSE;
    }

    try {
      $server_token = $item["server"] . "_token";
      $server_token = $this->getVars()[$server_token];
      if (empty($server_token)) {
        throw new \Exception("Cannot find token for {$item['server']}");
      }

      $headers = [
        "Accept" => "application/json",
        "Content-Type" => "application/json",
        "X-Postmark-Server-Token" => $server_token,
      ];
      if (\Drupal::request()->headers->has("X-PM-Bounce-Type")) {
        $headers["X-PM-Bounce-Type"] =  \Drupal::request()->headers->get("X-PM-Bounce-Type");
      }

      try {
        $response = $this->post($item["endpoint"], $item, $headers);
      }
      catch (Exception $e) {
        $headers = json_encode($this->request["headers"]);
        $item = json_encode($item);
        $response = json_encode($this->response);
        $this->error = "Error posting to Postmark - {$e->getMessage()}";
        throw new \Exception("Posting Error {$this->response["http_code"]}<br>HEADERS: {$headers}<br>PAYLOAD: {$item}<br>RESPONSE:{$response}");
      }

      if (strtolower($response["ErrorCode"]) != "0") {
        $headers = json_encode($headers);
        $item = json_encode($item);
        $response = json_encode($this->response);
        $this->error = "Error code returned from Postmark - {$response["Message"]}";
        throw new \Exception("Return Error Code: {$response['ErrorCode']}<br>HEADERS: {$headers}<br>PAYLOAD: {$item}<br>RESPONSE:{$response}");
      }

      return TRUE;

    }
    catch (Exception $e) {
      $this->error = $e->getMessage();
      return FALSE;
    }
  }

}
