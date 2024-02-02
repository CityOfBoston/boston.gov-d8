<?php

namespace Drupal\bos_emergency_alerts\Controller;

use CurlHandle;
use Drupal\Core\Controller\ControllerBase;

/**
 * This base class provides functionality that is likely to be needed by any
 * custom subscribers created for use in the bos_emergency_alerts component.
 */
class EmergencyAlertsSubscriberBase extends ControllerBase {

  public const BAD_REQUEST = 0;
  public const NO_CURL = 1;
  public const CURL_ERROR = 2;
  public const BAD_SQL = 3;
  public const BAD_RESPONSE = 4;
  public const PERMISSION_DENIED = 6;
  public const AUTHENTICATION_ERROR = 7;
  public const VENDOR_INTERNAL_ERROR = 8;
  public const NEEDS_VALIDATION = 9;

  /**
   * This is set when the ApiRouter->route() routing function is called from
   * the route attached to the endpoint.
   *
   * Allows the subscriber class to access Request, LoggerChannel, MailManager
   * and BosCoreGAPost objects.;
   */
  protected ApiRouter $router;

  /**
   * @var bool Use for debugging so that response headers can be inspected.
   */
  protected bool $debug_headers;

  /**
   * @var string Tracks errors which occur.
   */
  protected string $error;

  /**
   * @var array Retains the request
   */
  protected array $request;

  /**
   * @var array Stores response.
   */
  protected array $response;

  /**
   * Creates a standardized CuRL object and returns its handle.
   *
   * @param $post_url string The endpoint to post to.
   * @param $post_fields array|string Payload as an assoc array or urlencoded string.
   * @param $headers array (optional) Headers as an assoc array ([header=>value]).
   * @param $type string The request type lowercase (post, get etc). Default post.
   * @param bool $insecure All self-signed certs etc (local testing). Default false.
   *
   * @return \CurlHandle
   * @throws \Exception
   */
  protected function makeCurl(string $post_url, array|string $post_fields, array $headers = [], string $type = "POST", bool $insecure = FALSE) {

    // Merge and encode the headers for CuRL.
    // Any supplied headers will overwrite these defaults.
    $this->request["headers"] = array_merge([
      "Cache-Control" => "no-cache",
      "Accept" => "*/*",
      "Content-Type" => "application/json",
      "Connection" => "keep-alive",
    ], $headers);
    $_headers = [];
    foreach($this->request["headers"] as $key => $value) {
      $_headers[] = "{$key}: {$value}";
    }

    // Save/reset the request values for later.
    $this->request["body"] = $post_fields;
    $this->request["headers"]["host"] = $post_url;
    $this->request["type"] = $type;
    $this->error = "";
    $this->response = ["headers" => []];
    // Make the CuRL object and return its handle.
    try {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $post_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
      curl_setopt($ch, CURLOPT_HEADER, $this->debug_headers);
      curl_setopt($ch, CURLINFO_HEADER_OUT, $this->debug_headers);

      if ($insecure) {
        /*
         * Allows CuRl to connect to less secure endpoints, for example ones
         * which have self-signed, or expired certs.
         */
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      }

      return $ch;
    }
    catch (\Exception $e) {
      throw new \Exception("CuRL not properly installed: {$e->getMessage()}", self::NO_CURL);
    }

  }

  /**
   * Execute a CuRL POST using a CuRL handle which may or may not have been
   * created using $this->>makeCurl().
   * Throws a NEEDS_VALIDATION error if curl request responds with a validation
   * type (401) error
   *
   * @param $handle CurlHandle A valid curl Handle/object.
   * @param $retry bool Control flag to indicate if this is a retry of a
   * previous attempt.
   *
   * @return array The JSON string response from the curl request.
   * @throws \Exception
   */
  protected function executeCurl(CurlHandle $handle, bool $retry = FALSE): array {

    /**************************
     * EXECUTE
     **************************/
    try {

      $response = curl_exec($handle);

      $this->extractHeaders($handle, $response, $this->response["headers"]);

    }
    catch (\Exception $e) {
      $this->error = $e->getMessage() ?? "Error executing CuRL";
      if ($err = curl_error($handle)) {
        $this->error .= ': ' . $err;
      }
      $this->writeError($this->error);
      throw new \Exception($this->error, self::CURL_ERROR);
    }

    $this->response["response_raw"] = $response;
    $this->response["http_code"] = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    /**************************
     * PROCESS
     **************************/
    if ($response === FALSE) {

      // Got false from curl - This is a problem of some sort.
      $this->error = curl_error($handle) ?? "HTTP_CODE: {$this->response["http_code"]}";

      if ($this->error == "Empty reply from server") {
        try {
          $retry = 1;
          while (!$response = curl_exec($handle)) {
            // Retrying 3 times with 3 sec spacing.
            $this->writeError($this->error);
            sleep(3);
            if ($retry++ >= 3) {
              break;
            }
          }
        }
        catch (\Exception $e) {
          // CuRl itself threw an error.
          $this->error = "Error executing CuRL - {$e->getMessage()}";
          $this->writeError($this->error);
          throw new \Exception($this->error, self::CURL_ERROR);
        }
      }

      if ($response === FALSE) {
        // Still getting false ...
        $this->writeError($this->error);
        throw new \Exception($this->error, self::CURL_ERROR);
      }

    }

    // Convert the response into an array.
    $content_type = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    $is_json = str_contains($content_type, "/json");
    try {
      if ($is_json) {
        // Expecing JSON, so decode it.
        $this->response["body"] = (array) json_decode($response);
      }
      else {
        // Make an array out of the (hopefully) text/html response.
        $this->response["body"] = ["response" => urldecode($response)];
      }
    }
    catch (\Exception $e) {
      if ($is_json) {
        $this->error = "Bad JSON response from curl:\n{$e->getMessage()}";
      }
      else {
        $this->error = "Error decoding response from curl:\n{$e->getMessage()}";
      }
      $this->writeError($this->error);
      throw new \Exception($this->error, self::BAD_RESPONSE);
    }

    /**************************
     * VALIDATE
     **************************/
    if ($this->response["http_code"] == 500 ) {
      $this->writeError("Vendor API internal error: {$this->response["response_raw"]}");
      throw new \Exception($this->error, self::VENDOR_INTERNAL_ERROR);
    }

    elseif ($this->response["http_code"] == 401) {
      $this->error = "Authentication/access error: {$this->response["response_raw"]}";
      $this->writeError($this->error);
      throw new \Exception($this->error, self::AUTHENTICATION_ERROR);
    }

    elseif ($this->response["http_code"] == 403) {
      // Permission issue.
      if ($retry) {
        // Something is very wrong, throw an error.
        $this->error = "Permission denied: {$this->response["response_raw"]}";
        $this->writeError($this->error);
        throw new \Exception($this->error, self::PERMISSION_DENIED);
      }
      return $this->executeCurl($handle, TRUE);
    }

    elseif ($this->response["http_code"] >= 300 || $this->response["http_code"] < 200) {
      // Got an error or non-200 code - throw error
      $this->error = "Unexpected Endpoint Error (HTTP Code: {$this->response["http_code"]}): {$this->response["response_raw"]}";
      $this->writeError($this->error);
      throw new \Exception($this->error, self::BAD_REQUEST);
    }

    // Looks good, return the response body (an array).
    return  $this->response["body"];

  }

  /**
   * Splits the headers out of a CuRL response when the
   * curl_setopt["CURLOPT_HEADER"] has been set to true.
   *
   * @param CurlHandle $handle The post-execution curl handle
   * @param string $response The raw_response from curl_exec to be processed
   * @param array $headers An array to take the response headers.
   *
   * @return void
   */
  protected function extractHeaders($handle, string &$response, array &$headers): void {

    $headers = [];

    if ($this->debug_headers && $response) {

      // Need to separate the headers and the response body.
      $headersize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
      $_headers = substr($response, 0, $headersize);
      $response = substr($response, $headersize);

      // Make response headers into an array.
      foreach(explode("\r\n", $_headers) as $header) {
        $bits = explode(":", $header, 2);
        if (!empty($bits[0])) {
          $headers[empty($bits[1]) ? 0 : $bits[0]] = $bits[1] ?? $bits[0];
        }
      }

      // Log
      \Drupal::logger("bos_emergency_alerts")->info("
        <table>
          <tr><td>Endpoint</td><td>{$this->request["headers"]["host"]}</td></tr>
          <tr><td>Response Headers</td><td>{$_headers}</td></tr>
          <tr><td>response Body</td><td>" . print_r($response ?? "FALSE", TRUE) . "</td></tr>
        </table>");

    }

  }

  /**
   * Writes a standardized log message when an error occurs.
   *
   * @param string $narrative
   * @param string $url
   * @param string $payload
   * @param array $response
   *
   * @return void
   */
  protected function writeError(string $narrative = "Error"): void {
    $this->getLogger("bos_emergency_alerts")
      ->error("<br>
        <table>
          <tr><td>Issue</td><td>{$narrative}</td></tr>
          <tr><td>Endpoint</td><td>" . $this->request["headers"]["host"] ?? "unknown" . "</td></tr>
          <tr><td>JSON Payload</td><td>" . json_encode($this->request["body"] ?? []) . "</td></tr>
          <tr><td>JSON Response</td><td>" . print_r($this->response["response_raw"]??"", TRUE) . "</td></tr>
        </table>
      ");

  }

  /**
   * Return an array of settings.
   *
   * Use settings from an environment variable if present, or from the
   * drupal configuration (bos_emergency_alerts.settings) if no envar found.
   *
   * @param string $ENVAR The environment veriable to check for settings.
   * @param string $app_name The application name for note in drupal configuration.
   * @param array $envar_list List of settings that can be read from envar (all others ignored).
   *
   * @return array
   */
  protected function getSettings(string $ENVAR, string $app_name, array $envar_list = []):array {

    // The connection details should be stored in an environment variable
    // on the Acquia environment.
    // However, for local-dev, the connection details may be in the site config
    // because it's easier to use config locally than envars.
    //
    // We are expecting a json string in the envar which can be decoded into an
    // array, or else an array in the config object.
    //
    // SECURITY BEST PRACTICE:
    // Data stored in environment variables on the server are generally
    // considered to be more secure than data stored in the Drupal configuration
    // system.
    // However, for ease of management, the envar really only needs to contain
    // the endpoint, usernames and any password/secrets. Additional config
    // information can be handled by the Drupal configuration system and
    // therefore managed via the Drupal GUI.

    $config = [];

    if (getenv($ENVAR)) {
      $config = getenv($ENVAR);
      $config = (array) json_decode($config);
      if (!empty($envar_list)) {
        // Only keep envar settings permitted by envar_list
        $config = array_intersect_key($config, array_flip($envar_list));
      }
      $config["config"] = array_keys($config); // list of fields found from envar
    }

    $settings = $this->config("bos_emergency_alerts.settings")->get("api_config")[$app_name];

    if ($settings) {
      $config = array_merge($settings, $config);
    }

    return $config;
  }

}
