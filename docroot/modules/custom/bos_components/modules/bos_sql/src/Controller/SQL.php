<?php

namespace Drupal\bos_sql\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\CacheableJsonResponse;

/**
 * Class SQL.
 *
 * @package Drupal\bos_assessing\Controller
 *
 * @see https://docs.boston.gov/digital/guides/amazon-web-services/services/microservices-2021/sql-proxy-2021
 */
class SQL extends ControllerBase {

  public const AUTH_TOKEN = 0;
  public const CONN_TOKEN = 1;

  /**
   * @var string pointer to the DBConnector endpoint-url used by this instance
   */
  private $base_url = "";

  /**
   * @var bool Flag as to the return format. If json=true then returns a jsonrequest
   */
  private $json = FALSE;

  /**
   * @var array Holds the settings for this app.
   */
  private $dbconnector_env = [];

  /**
   * @var array An array of errors encountered
   */
  private $errors = [];

  /**
   * @inheritDoc
   */
  public function __construct(bool $json = FALSE, $app_name = "") {
    $this->base_url = 'https://dbconnector.' . $this->checkLocalEnv() . 'boston.gov/v1';
    $this->json = $json;
    if ($app_name != "") {
      // Get the dettings now, this may override the default base_url.
      $this->getSettings($app_name);
    }
  }

  /**
   * Check for local env and set connector url staging option.
   * @see getSettings()
   */
  public function checkLocalEnv() {
    $local = (isset($_ENV['DBCONNECTOR_SETTINGS'])) ? '' : 'digital-staging.';
    return $local;
  }

  /**
   * Run query against SQL database and return JSON response.
   * @see https://docs.boston.gov/digital/guides/amazon-web-services/services/microservices-2021/sql-proxy-2021#execute-sql-statement
   *
   * @param $bearer_token string An active auth token
   * @param $connection_token string A valid connection string token
   * @param string $statement string A valid SQL statement
   *
   * @return array|string
   */
  public function runQuery(string $bearer_token, string $connection_token, string $statement) {
    $post_fields = json_encode([
      "token"  => $connection_token,
      "statement" => $statement,
    ]);

    // Make the request and return the response.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->base_url . '/query/mssql');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Accept: application/json",
      "Content-Type: application/json",
      "Authorization: Bearer " . $bearer_token,
    ]);
    $info = curl_exec($ch);
//    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (isset($info)) {
      $data = json_decode($info);
    }
    else {
      $data = "error connecting to service";
    }

    //$response = new CacheableJsonResponse($data);
    return $data;
  }

  /**
   * Execute an abstracted select statement.
   * Works on SQL Database conn strings - simple query without concept of joins.
   *
   * @see https://docs.boston.gov/digital/guides/amazon-web-services/services/microservices-2021/sql-proxy-2021#run-select-query
   *
   * @param $bearer_token string An active auth token
   * @param $connection_token string A valid connection string token
   * @param $table string The table to extract data from
   * @param $fields array
   * @param $filter string
   * @param $sort array
   * @param $limit string
   * @param $page string
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse json encoded recordset
   */
  public function runSelect(string $bearer_token, string $connection_token, string $table, array $fields, array $filter, array $sort, string $limit, string $page) {

    $post_fields = [
      "token"  => $connection_token,
      "table"  => $table,
    ];
    if($filter){
      $post_fields["filter"] = $filter;
    }
    if($sort !== null){
      $post_fields["sort"] = $sort;
    }
    if($limit !== null){
      $post_fields["limit"] = $limit;
    }
    if($page !== null){
      $post_fields["page"] = $page;
    }
    if($fields !== null){
      $post_fields["fields"] = $fields;
    }

    $post_fields = json_encode($post_fields);

    // Make the request and return the response.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->base_url . '/select/mssql');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Accept: application/json",
      "Content-Type: application/json",
      "Authorization: Bearer " . $bearer_token,
    ]);
    $info = curl_exec($ch);
//    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if (isset($info)) {
      $data = json_decode($info);
    }
    else {
      $data = "error connecting to service";
    }


    $response = new CacheableJsonResponse($data);
    return $response;
  }

  /**
   * Execute a stored procedure.
   *
   * @see https://docs.boston.gov/digital/guides/amazon-web-services/services/microservices-2021/sql-proxy-2021#execute-stored-procedure
   *
   * @param $bearer_token string An active auth token
   * @param $connection_token string A valid connection string token
   * @param $proc_name string The procedure to execute
   * @param $params array Parameters to pass to the procedure - key=>value pairs
   * @param $output array Ouput params for the procedure - key=>value pairs
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse json encoded results of the stored proc execution, or a message
   */
  public function runProcedure(string $bearer_token, string $connection_token, string $proc_name, array $params, array $output) {
    $post_fields = json_encode([
      "token"  => $connection_token,
      "procname" => $proc_name,
      "params" => $params,
      "output" => $output,
    ]);

    // Make the request and return the response.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->base_url . '/exec/mssql');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Accept: application/json",
      "Content-Type: application/json",
      "Authorization: Bearer " . $bearer_token,
    ]);
    $info = curl_exec($ch);
//    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (isset($info)) {
      $data = json_decode($info);
    }
    else {
      $data = "error connecting to service";
    }

    $response = new CacheableJsonResponse($data);
    return $response;
  }

  /**
   * Get the dbconnector username & password, and the connection token from
   * either
   *    a JSON encoded environment variable,
   *    a settings array, or
   *    a config array.
   * Saves the credentials in the dbconnector_env class variable.
   *
   * @param $app_name The Aapplication Name, as registered in envar or settings.
   *
   * @return void
   */
  private function getSettings($app_name) {

    if (isset($_ENV['DBCONNECTOR_SETTINGS'])) {
      // This will read and decode a JSON string from an environment variable.
      $get_vars = explode(",", $_ENV['DBCONNECTOR_SETTINGS']);
      foreach ($get_vars as $item) {
        $json = explode(":", $item);
        $this->dbconnector_env[$json[0]] = $json[1];
      }
    }
    else {
      $get_vars = Settings::get('dbconnector_settings', []);
      if ($get_vars != []) {
        // This will read an array which is set in a settings.php file.
        $this->dbconnector_env = [
          "username_" . $app_name => $get_vars['username_' . $app_name],
          "password_" . $app_name => $get_vars['password_' . $app_name],
          "conntoken_" . $app_name => $get_vars['conntoken_' . $app_name],
        ];
        if (!empty($get_vars['base_url_' . $app_name])) {
          $this->base_url = $get_vars['base_url_' . $app_name];
        }
      }
      else {
        // This will read an array from a xxx.settings.yml config file.
        $get_vars = \Drupal::config("dbconnector.settings");
        if ($get_vars != []) {
          $this->dbconnector_env = [
            "username_" . $app_name => $get_vars->get('username.' . $app_name),
            "password_" . $app_name => $get_vars->get('password.' . $app_name),
            "conntoken_" . $app_name => $get_vars->get('conntoken.' . $app_name),
          ];
          if (!empty($get_vars->get('base_url.' . $app_name))) {
            $this->base_url = $get_vars->get('base_url.' . $app_name);
          }
        }
      }

    }

  }

  /**
   * Connect to DBConnector and authenticate
   *
   * @param string $app_name The name of the web application / service.
   *
   * @return array|null An array containing the auth and conn tokens.
   */
  public function getToken($app_name = "") {

    $this->errors = NULL;

    if ($app_name != "") {
      $this->getSettings($app_name);
    }

    if ($this->dbconnector_env == []) {
      $this->addError("Environment Settings could not be resolved.");
      return NULL;
    }

    else {

      if ($ch = curl_init()) {
        curl_setopt($ch, CURLOPT_URL, $this->base_url . '/auth');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
          "username" => $this->dbconnector_env["username_" . $app_name],
          "password" => $this->dbconnector_env["password_" . $app_name],
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "Accept: application/json",
          "Content-Type: application/json",
        ]);

        if ($info = curl_exec($ch)) {
          $data = json_decode($info, TRUE);
          if (empty($data['error'])) {
            $data = [
              self::AUTH_TOKEN => $data["authToken"],
              self::CONN_TOKEN => $this->dbconnector_env["conntoken_" . $app_name],
            ];
          }
          else {
            $this->addError($data["error"]);
            return NULL;
          }
        }

        else {
          if (curl_error($ch)) {
            // Check if Curl error exists, and if so return it
            $this->addError(curl_error($ch));
          }
          else {
            // Return generic error.
            $this->addError("API returned nothing!");
          }
          $data = NULL;

        }

        return $data;
      }
      else {
        $this->addError("Could not initialize CURL.");
        return NULL;
      }
    }



  }

  /**
   * Adds an error to the class errors variable.
   *
   * @param array|string $error
   *
   * @return void
   */
  private function addError(array|string $error) {
      if (empty($this->errors)) {
        $this->errors = [];
      }
      if (is_array($error)) {
       $this->errors = array_merge($this->errors, array_values($error));
      }
      else {
        $this->errors[] = $error;
      }
    }

  /**
   * Fetch any errors.
   *
   * @return array|false Array of errors, or FALSE.
   */
  public function getErrors() {
    if ($this->errors === []) {
      return FALSE;
    }
    else {
      return $this->errors;
    }
  }
}
