<?php

namespace Drupal\bos_emergency_alerts\Controller;

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
  protected bool $debug_headers = FALSE;

  /**
   * @var string Tracks errors which occur.
   */
  protected string $error;

  /**
   * @var string Stores the current/last endpoint url used by a CuRL request.
   */
  protected string $url;

  /**
   * @var string|array Stores the current/last payload sent by CuRL.
   */
  protected string|array $post_fields;

  /**
   * @var int Stores the HTTP_Code received from the current/last CuRL response.
   */
  protected int $http_code;

  /**
   * @var string Stores the current/last raw response received from the endpoint.
   */
  protected string $response_raw;

  /**
   * Creates a standardized CuRL object and returns its handle.
   *
   * @param $post_url string The endpoint to post to.
   * @param $post_fields array|string Payload as an assoc array or urlencoded string.
   * @param $headers array (optional) Headers as an assoc array ([header=>value]).
   * @param $type string The request type lowercase (post, get etc). Default post.
   *
   * @return \CurlHandle
   * @throws \Exception
   */
  protected function makeCurl(string $post_url, array|string $post_fields, array $headers = [], string $type = "post"): CurlHandle {

    // Merge and encode the headers for CuRL.
    // Any supplied headers will overwrite these defaults.
    $headers = array_merge([
      "Cache-Control" => "no-cache",
      "Accept" => "*/*",
      "Content-Type" => "application/json",
    ], $headers);
    $_headers = [];
    foreach($headers as $key => $value) {
      $_headers[] = "{$key}: {$value}";
    }

    // Save/reset the request values for later.
    $this->post_fields = $post_fields;
    $this->url = $post_url;
    $this->error = "";
    $this->http_code = "";
    $this->response_raw = "";

    // Make the CuRL object and return its handle.
    try {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $post_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
      curl_setopt($ch, CURLOPT_HEADER, $this->debug_headers);
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

    try {

      $response = curl_exec($handle);

      if ($this->debug_headers) {
        // Need to separate the headers and the response body.
        $headersize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headersize);
        \Drupal::logger('bos_emergency_alerts')->log("
          <table>
            <tr><td>Endpoint</td><td>{$this->url}</td></tr>
            <tr><td>Response Header</td><td>" . print_r($header) . "</td></tr>
            <tr><td>response Body</td><td>" . print_r($this->response_raw, TRUE) . "</td></tr>
          </table>");
        $response = substr($response, $headersize);
      }

      $this->response_raw = $response;

    }
    catch (\Exception $e) {
      $this->error = $e->getMessage() ?? "Error executing CuRL";
      $this->writeError($this->error);
      throw new \Exception($this->error, self::CURL_ERROR);
    }

    $this->http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    /**************************
     * VALIDATE
     **************************/
    if ($response === FALSE) {

      // Got false from curl - This is a problem of some sort.
      $this->error = curl_error($handle) ?? "HTTP_CODE: {$this->http_code}";

      if ($this->error = "Empty reply from server") {
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
          if ($response === FALSE) {
            // Still getting false ... Probably bad SQL statement return an error.
            $this->error = "CuRL failed (after 3 retries)";
            $this->writeError($this->error);
            throw new \Exception($this->error, self::BAD_SQL);
          }
        }
        catch (\Exception $e) {
          // CuRl itself threw an error.
          $this->error = "Error executing CuRL - {$e->getMessage()}";
          $this->writeError($this->error);
          throw new \Exception($this->error, self::CURL_ERROR);
        }
      }
    }

    $content_type = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    $is_json = str_contains($content_type, "/json");
    try {
      if ($is_json) {
        // Expecing JSON, so decode it.
        $resp = (array) json_decode($response);
      }
      else {
        // Make an array out of the (hopefully) text/html response.
        $resp = ["response" => urldecode($response)];
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

    // Now validate.
    if ($this->http_code == 500 ) {
      $this->writeError("Vendor API internal error: {$this->response_raw}");
      throw new \Exception($this->error, self::VENDOR_INTERNAL_ERROR);
    }

    elseif ($this->http_code == 401) {
      $this->error = "Authentication/access error: {$this->response_raw}";
      $this->writeError($this->error);
      throw new \Exception($this->error, self::AUTHENTICATION_ERROR);
    }

    elseif ($this->http_code == 403) {
      // Permission issue.
      if ($retry) {
        // Something is very wrong, throw an error.
        $this->error = "Permission denied: {$this->response_raw}";
        $this->writeError($this->error);
        throw new \Exception($this->error, self::PERMISSION_DENIED);
      }
      return $this->executeCurl($handle, TRUE);
    }

    elseif ($this->http_code >= 300 && $this->http_code < 200){
      // Got an error or non-200 code - throw error
      $this->error = "Unexpected Endpoint Error (HTTP Code: $this->http_code): {$this->response_raw}";
      $this->writeError($this->error);
      throw new \Exception($this->error, self::BAD_REQUEST);
    }

    // Looks good, return the response, as an array.
    return (array) $resp;

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
          <tr><td>Endpoint</td><td>" . $this->url ?? "unknown" . "</td></tr>
          <tr><td>JSON Payload</td><td>" . json_encode($this->post_fields ?? []) . "</td></tr>
          <tr><td>JSON Response</td><td>" . print_r($this->response_raw, TRUE) . "</td></tr>
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
   *
   * @return array
   */
  protected function getSettings(string $ENVAR, string $app_name):array {

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
    // information can handled by the Drupal configuration system and therefore
    // managed via the Drupal GUI.

    $config = [];

    if (getenv($ENVAR)) {
      $config = getenv($ENVAR);
      $config = json_decode($config);
      $config["config"] = array_keys($config); // list of fields found from envar
    }

    $settings = $this->config("bos_emergency_alerts.settings")->get("api_config")[$app_name];

    if ($settings) {
      $config = array_merge($settings, $config);
    }

    return $config;
  }

}
