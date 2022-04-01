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
   * @inheritDoc
   */
  public function __construct(bool $json = FALSE) {
    $this->base_url = 'https://dbconnector.' . $this->checkLocalEnv() . 'boston.gov/v1';
    $this->json = $json;
  }

  /**
   * Check for local env and set connector url staging option.
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
   * Connect to DBConnector and authenticate
   *
   * @param string $app_name The name of the web application / service.
   *
   * @return array|null An array containing the auth and conn tokens.
   */
  public function getToken($app_name) {

    if (isset($_ENV['DBCONNECTOR_SETTINGS'])) {
      $dbconnector_env = [];
      $get_vars = explode(",", $_ENV['DBCONNECTOR_SETTINGS']);
      foreach ($get_vars as $item) {
        $json = explode(":", $item);
        $dbconnector_env[$json[0]] = $json[1];
      }
    }
    else {
      $dbconnector_env = [
        "username_" . $app_name => Settings::get('dbconnector_settings')['username_' . $app_name],
        "password_" . $app_name => Settings::get('dbconnector_settings')['password_' . $app_name],
        "conntoken_" . $app_name => Settings::get('dbconnector_settings')['conntoken_' . $app_name],
      ];
    }

    $post_fields = json_encode([
      "username" => $dbconnector_env["username_" . $app_name],
      "password" => $dbconnector_env["password_" . $app_name],
    ]);

    // Make the request and return the response.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->base_url . '/auth');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Accept: application/json",
      "Content-Type: application/json",
    ]);
    $info = curl_exec($ch);
//    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (isset($info)) {
      $data = json_decode($info,true);
      $data = [
        self::AUTH_TOKEN => $data["authToken"],
        self::CONN_TOKEN => $dbconnector_env["conntoken_" . $app_name],
      ];
    }
    else {
      $data = null;
    }

    return $data;
  }

}
