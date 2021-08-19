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
   * Run query against SQL database and return JSON response.
   */
  public function runQuery($bearer_token,$type,$connection_token,$statement,$table,$filter,$args,$sort,$limit,$page) {

    $post_fields = [
      "token"  => $connection_token,
      "table"  => $table,
      "filter" => $filter,
      //"args"   => $args,
      "sort"   => $sort,
      "limit"  => $limit,
      "page"   => $page,
    ];
    $post_fields = json_encode($post_fields);
    //print $post_fields;
    $url = 'https://dbconnector.digital-staging.boston.gov/v1/'. $type.'/mssql';
    
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
    $post_fields = [
      "username" => "devuser",
      "password" => "Boston2021",
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
   * Get service name and set relevenat parameters.
   *
   */
  public function routeService(string $service_name) {
    $bearer_token = $this->getToken();
    $type = null;
    $connection_token = null;
    $statement = null;
    $table = null;
    $filter = null;
    $args = null;
    $sort = null;
    $limit = null;
    $page = 0;

    if($service_name == 'assessing'){
      $connection_token = "45826BE6-1E29-CC64-B49E-550B9610C2EA";
      $type = "query";
      $statement = 'SELECT TOP 1 [taxbillw].*, COALESCE([tax_preliminary].tax,0) as taxprelim, [Landuse_Described].description, [propertycodes_described].*, [Res_exempt].personal_exemption, [Res_exempt].residential_exemption, [parcel].condo_main 
      & FROM [taxbillw] 
      & LEFT OUTER JOIN [cpa] 
      & ON [taxbillw].parcel_id=[cpa].parcel_id 
      & LEFT OUTER JOIN [tax_preliminary]
      & ON [taxbillw].parcel_id=[tax_preliminary].parcel_id
      & LEFT OUTER JOIN [Landuse_Described]
      & ON  [taxbillw].land_use=[Landuse_Described].short_description
      & LEFT OUTER JOIN [propertycodes_described]
      & ON  [taxbillw].property_type=[propertycodes_described].[property-code]
      & LEFT OUTER JOIN [Res_exempt]
      & ON  [taxbillw].parcel_id=[Res_exempt].parcel_id
      & LEFT OUTER JOIN [parcel]
      & ON  [taxbillw].parcel_id=[parcel].parcel_id
      & WHERE [taxbillw].parcel_id=2001717000';
      
      //$sort = ["Name"];
      //$limit = 3;
    }
    elseif($service_name == 'test') {
      $connection_token = "45826BE6-1E29-CC64-B49E-550B9610C2EA";
      //$statement = "SELECT * FROM dbo.testTable WHERE name='{where}' ORDER BY [name]";
      $table = "testTable";
      $filter = [ 
          ["Name" => "%A%"],
      ];
      $sort = ["Name"];
      $limit = 3;
    }
    else {
    }
    
    return $this->runQuery(
      $bearer_token,
      $type,
      $connection_token,
      $statement,
      $table,
      $filter,
      $args,
      $sort,
      $limit,
      $page
    );

    //$response = new CacheableJsonResponse($bearer_token);
    //return $response;
  }

}
