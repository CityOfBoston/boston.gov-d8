<?php

namespace Drupal\bos_sql\Controller;

use Drupal\bos_sql\Form\DbconnectorSettingsForm;
use Drupal\Core\Controller\ControllerBase;
use CurlHandle;


/**
 * Class SQL.
 *
 * @package Drupal\bos_assessing\Controller
 */
class SQL extends ControllerBase {

  private array $settings = [];
  private array $library = [];
  public string $error = "";
  public bool $isConnected = FALSE;

  public const BAD_REQUEST = 0;
  public const NO_CURL = 1;
  public const CURL_ERROR = 2;
  public const BAD_SQL = 3;
  public const BAD_RESPONSE = 4;
  public const NO_LIBRARY_STATEMENT = 5;
  public const NOT_AUTHENTICATED = 6;
  public const TOKEN_EXPIRED = 7;

  private array $post_fields;
  private string $response_raw;
  private array $response;
  private mixed $http_code;
  private string $url;

  /**
   * @inheritDoc
   */
  public function __construct(string $appname) {
    $this->getSettings($appname);
  }

  /**
   * Fetches settings for this app.
   * Will prefer the setting defined in an ENVAR over settings defined in
   * config (bos_sql.settings).
   *
   * @param $appname string The name ofthis app in the settings.
   *
   * @return array The settings recovered from ENVAR or config.
   */
  private function getSettings(string $appname = ""): array {

    if (empty($this->settings) && !empty($appname)) {

      // Fetch any envar set.
      $envarname = DbconnectorSettingsForm::ENVAR_NAME;
      $envar = json_decode(getenv($envarname)) ?? [];

      // Fetch any config settings.
      $config = \Drupal::config("bos_sql.settings") ?? [];
      if (!empty($config)) {
        $config = (array) $config->get();
      }

      // Merge the arrays, allowing the envar to overwrite config.
      $settings = array_merge($config, $envar);

      // Save the settings we need.
      $this->settings = [
        "appname" => $appname,
        "host" => $settings["host"],
        "secure" => $settings["secure"],
        "username" => $settings[$appname]["username"] ?? "",
        "password" => $settings[$appname]["password"] ?? "",
        "token" => $settings[$appname]["token"] ?? "",
        "apiver" => $settings[$appname]["apiver"] ?? "",
        "bearer_token" => "",     // blank for now.
        "refresh_token" => "",    // blank for now.
      ];

    }

    // Return the settings array
    if ($appname = $this->settings["appname"]) {
      // If the settings array is for the expected app then return.
      return $this->settings;
    }
    else {
      // If the settings array is not for the expected app then reset and
      // retrieve again.
      // Note, this discards any bearer tokens that have been generated.
      $this->settings = [];
      return $this->getSettings($appname);
    }

  }

  /**
   * Loads an SQL statement into the class library.
   * The library statement can later be executed using runLibraryQuery().
   *
   * @param string $id The name to save the lirary statement as.
   * @param string $statement The statement to be executed. Parameters can be
   *                          escaped with !'s and substitutions will be made
   *                          e.g. "Select * from !mytable! where ID > 1;"
   *
   * @return void
   */
  public function loadLibrary(string $id, string $statement):void {
    $this->library[$id] = $statement;
  }

  /**
   * Create a properly formatted DBConnector endpoint.
   *
   * @param $path string The full URL to the desired endpoint.
   *
   * @return string
   */
  private function makeEndpointURL(string $path): string {
    $this->url = "{$this->settings["host"]}/{$this->settings["apiver"]}/{$path}";
    return $this->url;
  }

  /**
   * Authenticate and save tokens in an array.
   *
   * @return bool
   *
   * @throws \Exception
   */
  public function authenticate():bool {

    $this->prepClass();

    $this->post_fields = [
      "username" => $this->settings["username"] ?? "",
      "password" => $this->settings["password"] ?? '',
    ];
    $post_fields = json_encode($this->post_fields);
    $url = $this->makeEndpointURL("auth");

    // Make the request and return the response.
    $ch = $this->makeCurl($url, $post_fields, TRUE);
    $this->response_raw = $this->executeCurl($ch);

    if (!empty($this->response_raw) && $this->http_code == 200) {
      $data = $this->bos_sql_decode_json($this->response_raw);

      $this->settings["bearer_token"] = $data["authToken"] ?? "";
      $this->settings["refresh_token"] = $data["refreshToken"] ?? "";

      $this->isConnected = TRUE;
      return !empty($data["authToken"]);
    }
    else {
      $this->error = curl_error($ch);
      \Drupal::logger('dbconnector')->error($this->error);
      $this->isConnected = FALSE;
      return FALSE;
    }

  }

  /**
   * Using refresh token, re-authenticate and save tokens in an array.
   *
   * @return bool
   *
   * @throws \Exception
   */
  public function refreshAuth():bool {

    $this->prepClass();

    $this->post_fields = [
      "token"  => $this->settings["token"]
    ];
    $post_fields = json_encode($this->post_fields);
    $url = $this->makeEndpointURL("auth/refresh");

    // Make the request and return the response.
    $ch = $this->makeCurl($url, $post_fields);
    $this->response_raw = $this->executeCurl($ch);

    if (!empty($this->response_raw) && $this->http_code == 200) {
      $data = $this->bos_sql_decode_json($this->response_raw);

      $this->settings["bearer_token"] = $data["authToken"] ?? "";
      $this->settings["refresh_token"] = $data["refreshToken"] ?? "";

      $this->isConnected = TRUE;
      return !empty($data["authToken"]);
    }
    else {
      $this->error = curl_error($ch);
      \Drupal::logger('dbconnector')->error($this->error);
      $this->isConnected = FALSE;
      return FALSE;
    }
  }

  /**
   * Execute an SQL statement.
   * NOTE: this will only work if the credentials have permission to run SQL
   *        statements.  Best practice will be to limit credentials to using
   *        runSelect statement.
   *
   * @param $bearer_token string
   * @param $connection_token string
   * @param $statement string
   *
   * @return array|bool
   * @throws \Exception
   */
  public function runQuery(string $statement): array|bool {

    $this->prepClass();

    $this->post_fields = [
      "token"  => $this->settings["token"],
      "statement" => $statement,
    ];
    $post_fields = json_encode($this->post_fields);
    $url = $this->makeEndpointURL("query/mssql");

    // Make the request and return the response.
    $ch = $this->makeCurl($url, $post_fields);
    $this->response_raw = $this->executeCurl($ch);

    if (FALSE === $this->response_raw) {
      // The token expired and has been refreshed, try again.
      $this->response_raw = $this->runQuery($statement);
    }

    $this->response = $this->bos_sql_decode_json($this->response_raw);
    return $this->response;

  }

  /**
   * Runs a query which has been loaded up into the library.
   *
   * @param string $id The string the lbrary statement was saved with
   * @param array $params An associative array for key=>values.
   *                      The library statement string is searched for key
   *                      string matches escaped with |'s and the value is
   *                      substituted in.
   *                      See nodes in code.
   *
   * @return array|bool
   * @throws \Exception
   */
  public function runLibraryQuery(string $id, array $params = []): array|bool {

    /*
     * This function allows a previously saved query string to be used with
     * different parameters at different times in your code without redefining
     * the entire SQL string.
     *
     * Example:
     *  $statement1 = "SELECT id, name FROM mytable WHERE id = |idval|";
     *  $sql->loadLibrary("query1", $statement1);
     *  $data = $sql->runLibraryQuery("query1", ["idval"=>1234]);
     * ... then later ...
     *  $data = $sql->runLibraryQuery("query1", ["idval"=>4567]);
     *
     */

    if (empty($this->library[$id])) {
      // Abort if $id not found in library array.
      return FALSE;
    }

    // Make string substitutions.
    $statement = "";
    foreach(explode("|", $this->library[$id]) as $part) {
      if (!empty($this->library[$part])) {
        $statement .= $this->library[$part];
      }
      else {
        $statement .= $part;
      }
    }

    // Run query and return results.
    if (!empty($statement)) {
      return $this->runQuery($statement);
    }
    throw new \Exception("Library statement not found", self::NO_LIBRARY_STATEMENT);

  }

  /**
   * Executes a stored procedure.
   *
   * @param string $sp_name The procedure to run
   * @param array $input_params Assoc array of parameters to pass
   * @param array $output_params (byref) Assoc array of input/output parameters.
   *                             This is updated by the function with the values
   *                              of the sp's output variables.
   *
   * @return array|bool Array of results from the select query.
   */
  public function runSP(string $sp_name, array $input_params = [], array &$output_params = []) {

    $this->prepClass();

    $this->post_fields = [
      "token"  => $this->settings["token"],
      "procname" => $sp_name,
    ];
    if (!empty($input_params)) {
      $this->post_fields["params"] = $input_params;
    }
    if (!empty($output_params)) {
      $this->post_fields["output"] = $output_params;
    }

    $post_fields = json_encode($this->post_fields);

    $url = $this->makeEndpointURL("exec/mssql");

    // Make the request and return the response.
    $ch = $this->makeCurl($url, $post_fields);
    $this->response_raw = $this->executeCurl($ch);

    $response = $this->bos_sql_decode_json($this->response_raw);

    if (!empty($response["result"])) {
      $output_params = $response["output"] ?? [];
      $this->response = $response["result"];
    }
    else {
      $this->response = $response;
    }

    return $this->response;

  }

  /**
   * Run an abstracted select query against the token's connection string.
   *
   * @param $table string The base table to query.
   * @param $fields array|null List of fields to return. ["ID","name"]
   * @param $filter array|null Assoc array of fields and filter values. ["ID"=>1,"enabled"=>"false"]
   * @param $sort array|null List of fields sort. ["ID DESC","name"]
   * @param $limit string|null The number of records to return in a page.
   * @param $page string|null The page number to return (0=first page).
   *
   * @return array|bool Array of results from the select query.
   */
  public function runSelect(string $table, array|null $fields, array|null $filter = NULL, array|null $sort = NULL, string|null $limit = NULL, string|null $page = NULL): array|bool {

    $this->prepClass();

    $this->post_fields = [
      "token"  => $this->settings["token"],
      "table"  => $table,
    ];
    if ($filter !== NULL){
      $this->post_fields["filter"] = $filter;
    }
    if ($sort !== NULL){
      $this->post_fields["sort"] = $sort;
    }
    if ($limit !== NULL){
      $this->post_fields["limit"] = $limit;
    }
    if ($page !== NULL){
      $this->post_fields["page"] = $page;
    }
    if ($fields !== NULL){
      $this->post_fields["fields"] = $fields;
    }

    $post_fields = json_encode($this->post_fields);
    $post_url = $this->makeEndpointURL("select/mssql");

    // Make the request and return the response.
    $ch = $this->makeCurl($post_url, $post_fields);
    $this->response_raw = $this->executeCurl($ch);

    $this->response = $this->bos_sql_decode_json($this->response_raw);
    return $this->response;

  }

  /**
   * Creates a standardized CuRL object and returns its handle.
   *
   * @param $post_url string The endpoint to post to
   * @param $post_fields string Payload as an assoc array.
   * @param $auth bool Flag that this does not need a bearer token.
   *
   * @return \CurlHandle|false
   * @throws \Exception
   */
  private function makeCurl(string $post_url, string $post_fields, bool $auth = FALSE): CurlHandle {

    try {
      $ch = curl_init();
    }
    catch (\Exception $e) {
      throw new \Exception("CuRL not properly installed", self::NO_CURL);
    }

    $headers = [
      "Accept: application/json",
      "Content-Type: application/json",
    ];
    if (!$auth && !empty($this->settings["bearer_token"])) {
      $headers[] =  "Authorization: Bearer {$this->settings["bearer_token"]}";
    }

    curl_setopt($ch, CURLOPT_URL, $post_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($this->settings["secure"] == 0) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    }

    return $ch;

  }

  /**
   * Execute a CuRL POST using a CuRL handle which may or may not have been
   * created using $this->>makeCurl().
   * If FALSE is returned, then the token expired but was refreshed. The caller
   * should rebuild the CuRL (with updated token) and try again.
   *
   * @param $handle CurlHandle A valid curl Handle/object.
   *
   * @return string|bool The JSON string response from the curl request.
   * @throws \Exception
   */
  private function executeCurl(CurlHandle $handle): string|bool {

    try {
      $this->error = "";
      $response = curl_exec($handle);
    }
    catch (\Exception $e) {
      $this->error = $e->getMessage() ?? "Error executing CuRL";
      \Drupal::logger('dbconnector')->error($this->error);
      throw new \Exception($this->error, self::CURL_ERROR);
    }

    $this->http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    /**************************
     * VALIDATE
     **************************/
    if ($response === FALSE) {
      // Got false from curl - throw error.
      $this->error = curl_error($handle) ?? "HTTP_CODE: {$this->http_code}";

      if ($this->error = "Empty reply from server") {
        try {
          $retry = 1;
          while (!$response = curl_exec($handle)) {
            sleep(1);
            if ($retry++ >= 5) {
              break;
            }
          }
        }
        catch (\Exception $e) {
          $this->error = $e->getMessage() ?? "Error executing CuRL";
          \Drupal::logger('dbconnector')->error($this->error);
          throw new \Exception($this->error, self::CURL_ERROR);
        }
      }
    }
    if ($response === FALSE) {
      $this->writeError($this->error);
      throw new \Exception($this->error, self::BAD_SQL);
    }
    else {
      try {
        $resp = (array) json_decode($response);
      }
      catch (\Exception $e) {
        $this->error = "Bad JSON response from curl:\n{$e->getMessage()}";
        $this->writeError($this->error);
        throw new \Exception($this->error, self::BAD_RESPONSE);
      }

      if ($this->http_code == 401 && isset($resp["error"])
        && $resp["error"] == "Expired Token") {
          // Token is expired, try to refresh the token.
        $this->isConnected = FALSE;
        if (!$this->refreshAuth()) {
          // Refresh failed, maybe refresh token is too old .. authenticate again
          if (!$this->authenticate()) {
            // Something is very wrong, throw an error.
            $this->error = "Could not re-authenticate";
            $this->writeError($this->error);
            throw new \Exception($this->error, self::TOKEN_EXPIRED);
          }
        }
        return FALSE;  // Alerts caller to try Curl again.
      }
      elseif (!$this->isConnected && $this->http_code != 200){
          // Got a non-200 HTTP code during authentication -throw error.
        $this->error = "Authentication Error";;
        $this->writeError($this->error);
        throw new \Exception($this->error, self::NOT_AUTHENTICATED);
      }
      elseif ($this->isConnected && isset($resp["error"])){
          // Got a response with an error in the body, throw error.
        $this->error = $resp["error"];
        $this->writeError($this->error);
        throw new \Exception($this->error, self::BAD_SQL);
      }
      elseif
        ($this->http_code != 200){
          // Got a non-200 code - throw error
        $this->error = "Unspecified Endpoint Error";
        $this->writeError($this->error . "\n" . $response);
        throw new \Exception($this->error, self::NOT_AUTHENTICATED);
      }
    }

    // Looks good, return the response.
    return $response;

  }

  /**
   * Decode a JSON string into an array or object.
   *
   * @param string $json_string
   *
   * @return array
   * @throw \Exception
   */
  private function bos_sql_decode_json(string $json_string): array {
    try {
      $this->response_raw = $json_string;
      return (array) json_decode($json_string);
    }
    catch (\Exception $e) {
      $this->error = "Error decoding JSON string: {$e->getMessage()}";
      $this->writeError($this->error);
      throw new \Exception($e->getMessage(), self::BAD_RESPONSE);
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
  private function writeError(string $narrative): void {
    $this->getLogger("dbconnector")
      ->error("<br>
        <table>
          <tr><td>Issue</td><td>{$narrative}</td></tr>
          <tr><td>Endpoint</td><td>{$this->url}</td></tr>
          <tr><td>JSON Payload</td><td>" . json_encode($this->post_fields) . "</td></tr>
          <tr><td>JSON Response</td><td>" . print_r($this->response_raw, TRUE) . "</td></tr>
        </table>
      ");

  }

  /**
   * Resets the class variables/properties for a new query.
   *
   * @return void
   */
  private function prepClass(): void {
    $this->post_fields = [];
    $this->response_raw = "";
    $this->response = [];
    $this->http_code = "";
    $this->url = "";
  }

}
