<?php

namespace Drupal\bos_sql\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SQL.
 *
 * @package Drupal\bos_assessing\Controller
 */
class SQL extends ControllerBase {

  /**
   * Class var.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public $request;


  /**
   * Run query against SQL database and return JSON response.
   */
  public function runQuery($bearer_token,$connection_token,$statement) {
    $post_fields = [
      "token"  => $connection_token,
      "statement" => $statement,
    ];
    $post_fields = json_encode($post_fields);
    $url = 'https://dbconnector.digital-staging.boston.gov/v1/query/mssql';
    
    // Make the request and return the response.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Accept: application/json",
      "Content-Type: application/json",
      "Authorization: Bearer " . $bearer_token,
    ]);
    $info = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    
    if (isset($info)) {
      $data = json_decode($info);
    } else {
      $data = "error connecting to service";
    }
     
    //$response = new CacheableJsonResponse($data);
    return $data;
  }

  public function runSelect($bearer_token,$connection_token,$table,$filter,$sort,$limit,$page) {

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

    $post_fields = json_encode($post_fields);
    $url = 'https://dbconnector.digital-staging.boston.gov/v1/select/mssql';
    
    // Make the request and return the response.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Accept: application/json",
      "Content-Type: application/json",
      "Authorization: Bearer " . $bearer_token,
    ]);
    $info = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    
    if (isset($info)) {
      $data = json_decode($info);
    } else {
      $data = "error connecting to service";
    }
     

    $response = new CacheableJsonResponse($data);
    return $response;
  }

  /**
   * Request DB operations
   */
  public function getToken() {

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
        "username" => Settings::get('dbconnector_settings')['username'],
        "password" => Settings::get('dbconnector_settings')['password'],
      ];
    }

    $post_fields = [
      "username" => $dbconnector_env["username"],
      "password" => $dbconnector_env["password"],
    ];
    $post_fields = json_encode($post_fields);
    $url = 'https://dbconnector.digital-staging.boston.gov/v1/auth';
    
    // Make the request and return the response.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Accept: application/json",
      "Content-Type: application/json",
    ]);
    $info = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (isset($info)) {
      $data = json_decode($info,true);
      $data = $data["authToken"];
    } else {
      $data = null;
    }

    return $data;
  }

  /**
   * Get post data and apply to select endpoint.
   *
   */
  public function assessingLookup() {
    $data = \Drupal::request()->query;
    
    //required
    $bearer_token = $this->getToken();
    $connection_token = "AA05bf6a-7c30-4a64-9ba7-7ba7100070d7";
    $table = "taxbill";
    $filter = [];

    if($data->get("parcel_id")){
      array_push($filter, ["parcel_id" => $data->get("parcel_id")]);
    }
    if($data->get("street_number")){
      array_push($filter, ["street_number" => $data->get("street_number")]);
    }
    if($data->get("apt_unit")){
      array_push($filter, ["apt_unit" => $data->get("apt_unit")]);
    }
    if($data->get("street_name_only")){
      $street_name_only = $data->get("street_name_only");
      if(strlen($street_name_only) == 1 && ctype_alpha($street_name_only) == TRUE) {
        array_push($filter, ["street_name_only" => $street_name_only]);
      } else {
        array_push($filter, ["street_name_only" => "%" . $street_name_only . "%" ]);
      }
    }
    if($data->get("street_name_suffix")){
      $sns = explode(",",$data->get("street_name_suffix"));
      array_push($filter, ["street_name_suffix" => $sns]);
    }
    
    $sort = ($data->get("sort")) ? $data->get("sort") : ["parcel_id"];
    $limit = ($data->get("limit")) ? $data->get("limit") : 500;
    $page = ($data->get("page")) ? $data->get("page") : null;
    
  
    //$response = new CacheableJsonResponse($filter . $page);
    //return $response;
    return $this->runSelect($bearer_token,$connection_token,$table,$filter,$sort,$limit,$page);
  }

}
