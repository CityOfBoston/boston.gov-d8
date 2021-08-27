<?php

namespace Drupal\bos_sql\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * Public construct for Request.
   */
  public function __construct(RequestStack $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('request_stack')
    );
  }

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
     
    $response = new CacheableJsonResponse($data);
    return $response;
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
    $data = $this->request->getCurrentRequest();
    
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
    if($data->get("street_name_only")){
      $sno = explode(",",$data->get("street_name_only"));
      array_push($filter, ["street_name_only" => $sno]);
    }
    if($data->get("street_name_suffix")){
      $sns = explode(",",$data->get("street_name_suffix"));
      array_push($filter, ["street_name_suffix" => $sns]);
    }
    
    $sort = ($data->get("sort")) ? $data->get("sort") : null;
    $limit = ($data->get("limit")) ? $data->get("limit") : null;
    $page = ($data->get("page")) ? $data->get("page") : null;
    
  
    //$response = new CacheableJsonResponse($filter . $page);
    //return $response;
    return $this->runSelect($bearer_token,$connection_token,$table,$filter,$sort,$limit,$page);
  }

  /**
   * Get parcel_id and query details info.
   *
   */
  public function assessingDetails(string $parcel_id) {
    $bearer_token = $this->getToken();
    $connection_token = "AA05bf6a-7c30-4a64-9ba7-7ba7100070d7";
    $statement = "SELECT t.*, TP.*, RA.*, CA.*
                  FROM taxbill AS t
                    LEFT JOIN tax_preliminary AS tp
                      ON t.parcel_id = TP.parcel_id 
                    LEFT JOIN residential_attributes AS ra
                      ON t.parcel_id = RA.parcel_id
                    LEFT JOIN condo_attributes AS ca
                      ON t.parcel_id = CA.parcel_id
                    WHERE t.parcel_id = '$parcel_id'";
  
    return $this->runQuery($bearer_token,$connection_token,$statement);
    //$response = new CacheableJsonResponse($bearer_token);
    //return $response;
  }

}
