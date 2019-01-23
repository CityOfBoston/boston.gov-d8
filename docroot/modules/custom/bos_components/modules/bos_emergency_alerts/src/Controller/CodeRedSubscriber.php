<?php

namespace Drupal\bos_emergency_alerts\Controller;

use Drupal\Core\Controller\ControllerBase;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CodeRedSubscriber extends ControllerBase {

  protected $action;

  protected $uri = [
    "login" => "/api/login",          // GET or POST
    "contact-list" => "/api/contacts",    // POST only
    "contact" => "/api/contacts/{}",
  ];

  /**
   * Magic function.  Catches calls to endpoints that dont exist.
   *
   * @param string $name
   *   Name.
   * @param mixed $arguments
   *   Arguments.
   */
  public function __call($name, $arguments) {
    throw new NotFoundHttpException();
  }

  /**
   * Extends the config function.
   *
   * @param string $name
   *   The config(setting) to be managed.
   *
   * @return array|\Drupal\Core\Config\Config|mixed|null
   *   The value of the setting being managed.
   */
  public function config($name) {
    $config = parent::config("bos_emergency_alerts.settings");
    if (isset($name)) {
      return $config->get($name);
    }
    return $config->getRawData();
  }

  /**
   * The main entrypoint for the controller.
   *
   * @param string $action
   *   The action being called via the endpoint uri.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The JSON output string.
   */
  public function api($action) {
    $this->action = $action;
    switch (\Drupal::request()->getMethod()) {
      case "POST":
        return $this->$action(\Drupal::request()->request->all());
    }
  }

  /**
   * This is the local /rest/codered/subscribe endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response from the codered remote API.
   */
  private function subscribe($payload) {
    $codered = $this->config("codered_settings");

    if (!empty($codered['api_base']) && !empty($codered['api_pass']) && !empty($codered['api_user'])) {
      $uri = $this->uri['contact-list'];

      // Make a customKey.
      if (!empty($payload['email'])) {
        $customKey = $this->stringtohex($payload['email']);
      }
      elseif (!empty($payload['phone_number'])) {
        $customKey = $this->stringtohex($payload['phone_number']);
      }
      else {
        $customKey = $this->stringtohex($payload['first_name'] . $payload['last_name']);
      }

      $fields = [
        "CustomKey" => $customKey,
        "FirstName" => $payload['first_name'],
        "LastName" => $payload['last_name'],
        "OtherPhone" => $payload['phone_number'],
        "TextNumber" => $payload['phone_number'],
        'MobileProvider' => "Sprint",
        "HomeEmail" => $payload['email'],
        "Zip" => $payload['zip'],
        "PreferredLanguage" => $payload['language'],
        "Groups" => "digital test",
      ];
      $headers = [
        "Cache-Control: no-cache",
      ];
      $result = $this->post($uri, $fields, $headers, TRUE);
    }
    else {
      $result = ["output" => "Missing Drupal Configuration.", "HTTP_CODE" => "500"];
    }

    return $this->responseOutput($result['output'], $result['http_code']);
  }

  /**
   * Helper: Formats a standardised Response object.
   *
   * @param string $message
   *   Message to JSON'ify.
   * @param int $type
   *   Response constant for the HTTP Status code returned.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Full Response object to be returned to caller.
   */
  private function responseOutput($message, $type) {
    $json = [
      'status' => 'error',
      'contact' => $message,
    ];
    $response = new Response(
      json_encode($json),
      $type,
      [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'must-revalidate, no-cache, private',
        'X-Generator-Origin' => 'City of Boston (https://www.boston.gov)',
        'X-COB-Cityscore' => $this->action,
        'Content-Language' => 'en',
      ]
    );
    switch ($type) {
      case Response::HTTP_CREATED:
      case Response::HTTP_OK:
      case Response::HTTP_NON_AUTHORITATIVE_INFORMATION:
        $json['status'] = 'success';
        $response->setContent(json_encode($json));
        break;

      case Response::HTTP_UNAUTHORIZED:
      case Response::HTTP_NO_CONTENT:
      case Response::HTTP_FORBIDDEN:
      case Response::HTTP_BAD_REQUEST:
      case Response::HTTP_METHOD_NOT_ALLOWED:
      case Response::HTTP_INTERNAL_SERVER_ERROR:
        $json['status'] = 'error';
        $json['errors'] = "Sorry, internal error.";
        unset($json['contact']);
        $response->setContent(json_encode($json));
        break;
    }
    return $response;
  }

  private function post($uri, $fields, $headers, $cachebuster = FALSE) {

    $codered = $this->config("codered_settings");
    $url = $codered['api_base'] . "/" . ltrim($uri,"/");

    // Add a random string at end of post to bust any caches.
    if ($cachebuster) {
      if ( stripos($url, "?") > 0) {
        $url .= "&cobcb=" . rand();
      }
      else {
        $url .= "?cobcb=" . rand();
      }
    }

    //url-encode the data for the POST
    $fields_string = "";
    foreach($fields as $key=>$value) {
      $fields_string .= $key . '=' . urlencode($value) . '&';
    }
    $fields_string = rtrim($fields_string, '&');

    // Build headers.
    // $headers[] = "";

    // Make the post and return the response.
    $login_url = $codered['api_base'] . "/api/login?username=" . $codered['api_user'] . "&password=" . $codered['api_pass'];
    $public_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $login_url);
    curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl_handle, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($curl_handle, CURLOPT_POST, FALSE);
    curl_setopt($curl_handle, CURLOPT_COOKIEJAR, $public_path . '/codered.cookie');
    $output = curl_exec($curl_handle);

    if ($output) {
      $curl_handle = curl_init();
      curl_setopt($curl_handle, CURLOPT_URL, $url);
      curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, TRUE);
      curl_setopt($curl_handle, CURLOPT_AUTOREFERER, TRUE);
      curl_setopt($curl_handle, CURLOPT_POST, count($fields));
      curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields_string);
      curl_setopt($curl_handle, CURLOPT_COOKIEFILE, $public_path . '/codered.cookie');

      if (!($output = curl_exec($curl_handle))) {
        $output = NULL;
      }
      $http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
      curl_close($curl_handle);
      unlink($public_path . '/codered.cookie');

      return [
        "output" => json_decode($output),
        "http_code" => $http_code,
      ];
    }
    else {
      return;
    }
  }

  /**
   * Convert each char in a string to a hex "number" and output new string.
   *
   * @param string $string
   *   A string which is to be converted into a hex number.
   *
   * @param int $maxlen
   *   Maximum number of chars to be returned.
   *
   * @return string
   *   The string in hex format.
   */
  private function stringtohex($string, $maxlen = 140) {
    $hex = '';

    for ($i=0; $i<strlen($string); $i++){
      $ord = ord($string[$i]);
      $hex .= substr('0' . dechex($ord), -2);
    }

    return substr(strtoupper($hex), 0, $maxlen);
  }
}
