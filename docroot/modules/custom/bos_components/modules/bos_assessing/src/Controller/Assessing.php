<?php

namespace Drupal\bos_assessing\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\geolocation\Element;
use Drupal\bos_sql\Controller\SQL;


/**
 * Class Assessing.
 *
 * @package Drupal\bos_assessing\Controller
 */
class Assessing extends ControllerBase {

  /**
   * Logger object for class.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  private SQL $sql;

  /**
   * Get poly coordinates from arcGIS layer
   * @param string $parcel_id
   *   The id of the parcel requested by user
   */
  public function getPolyCoords($parcel_id) {

      $url = 'https://services.arcgis.com/sFnw0xNflSi8J0uh/ArcGIS/rest/services/AbutterParcels_Oct2020/FeatureServer/0/query?where=PID_LONG%3D%27'.$parcel_id.'%27&f=pgeojson';
      // Make the request and return the response.
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HTTPGET, true);
      $info = curl_exec($ch);

      if (isset($info)) {
        //$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $coords = json_decode($info);
      } else {
        return $coords = NULL;
      }

  }

  /**
   * Get parcel info from endpoint
   * @param string $parcel_id
   *   The id of the parcel requested by user
   */
  public function assessingDetails($parcel_id) {

    $this->sql = new SQL("assessing");

    $this->sql->loadLibrary("tax_info", "SELECT t.*, TP.*, AD.*
        FROM taxbill AS t
        LEFT JOIN tax_preliminary AS tp ON t.parcel_id = TP.parcel_id
        LEFT JOIN additional_data AS ad ON t.parcel_id = AD.parcel_id
        WHERE t.parcel_id = '!parcel_id!'");
    $this->sql->loadLibrary("residential_attributes", "SELECT * FROM `RESIDENTIAL PROPERTY ATTRIBUTES`
        WHERE parcel_id = '|parcel_id|'");
    $this->sql->loadLibrary("condo_attributes", "SELECT * FROM `CONDO PROPERTY ATTRIBUTES`
         WHERE parcel_id = '|parcel_id|'");
    $this->sql->loadLibrary("value_history", "SELECT TOP 10 * FROM value_history
         WHERE parcel_id = '|parcel_id|'");
    $this->sql->loadLibrary("owner", "SELECT owner FROM taxbill
         WHERE parcel_id = '|parcel_id|'");
    $this->sql->loadLibrary("current_owner", "SELECT owner_name FROM current_owners
         WHERE parcel_id = '|parcel_id|'");

    $params = ["parcel_id" => $parcel_id];

    $sqlQuery_main = $this->sql->runLibraryQuery("tax_info", $params);
    $sqlQuery_res = $this->sql->runLibraryQuery("residential_attributes", $params);
    $sqlQuery_condo = $this->sql->runLibraryQuery("condo_attributes", $params);
    $sqlQuery_value_history = $this->sql->runLibraryQuery("value_history", $params);
    $sqlQuery_owner = $this->sql->runLibraryQuery("owner", $params);
    $sqlQuery_owners_current = $this->sql->runLibraryQuery("current_owner", $params);

    $coords = $this->getPolyCoords($parcel_id);
    $fiscal_year = ( date('m') > 6) ? intval(date('Y')) + 1 : date('Y');

    return [
      '#theme' => 'bos_assessing',
      '#data_full' => $sqlQuery_main,
      '#data_res' => $sqlQuery_res,
      '#data_condo' => $sqlQuery_condo,
      '#data_owner' => $sqlQuery_owner,
      '#data_owners_current' => $sqlQuery_owners_current,
      '#data_value_history' => $sqlQuery_value_history,
      '#data_coords' => $coords,
      '#data_year_current' => date('Y'),
      '#data_year_fiscal' => $fiscal_year,
    ];
  }

   /**
   * Get post data and apply to select endpoint.
   *
   */
  public function assessingLookup() {

    $data = \Drupal::request()->query;
    $sql = new SQL("assessing");

    //required
    $sql->authenticate("assessing");

    $table = "taxbill";
    $filter = [];

    if($data->get("parcel_id")){
      $filter[] = ["parcel_id" => $data->get("parcel_id")];
    }
    if($data->get("street_number")){
      $filter[] = ["street_number" => $data->get("street_number")];
    }
    if($data->get("apt_unit")){
      $filter[] = ["apt_unit" => $data->get("apt_unit")];
    }
    if($data->get("street_name_only")){
      $street_name_only = $data->get("street_name_only");
      if(strlen($street_name_only) == 1 && ctype_alpha($street_name_only) == TRUE) {
        $filter[] = ["street_name_only" => $street_name_only];
      }
      else {
        $filter[] = ["street_name_only" => "%{$street_name_only}%"];
      }
    }
    if($data->get("street_name_suffix")){
      $sns = explode(",", $data->get("street_name_suffix"));
      $filter[] = ["street_name_suffix" => $sns];
    }

    $sort = ($data->get("sort")) ? $data->get("sort") : ["street_name","street_number","apt_unit"];
    $limit = ($data->get("limit")) ? $data->get("limit") : 500;
    $page = ($data->get("page")) ? $data->get("page") : null;
    $fields = ($data->get("fields")) ? $data->get("fields") : null;

    $response = $sql->runSelect($table, $fields, $filter, $sort, $limit, $page);

    return new CacheableJsonResponse($response);

  }

}
