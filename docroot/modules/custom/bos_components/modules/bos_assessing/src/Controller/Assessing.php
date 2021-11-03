<?php

namespace Drupal\bos_assessing\Controller;

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
  public function lookupParcel($parcel_id) {
      $statement1 = "SELECT t.*, TP.*, AD.*
                   FROM taxbill AS t
                    LEFT JOIN tax_preliminary AS tp
                      ON t.parcel_id = TP.parcel_id
                    LEFT JOIN additional_data AS ad
                      ON t.parcel_id = AD.parcel_id
                    WHERE t.parcel_id = '$parcel_id'";
      $statement2 = "SELECT * FROM [RESIDENTIAL PROPERTY ATTRIBUTES] WHERE parcel_id = '$parcel_id'";
      $statement3 = "SELECT * FROM [CONDO PROPERTY ATTRIBUTES] WHERE parcel_id = '$parcel_id'";
      $statement4 = "SELECT TOP 10 * FROM value_history WHERE parcel_id = '$parcel_id'";
      $statement5 = "SELECT * FROM current_owners WHERE parcel_id = '$parcel_id'";

      $sql = new SQL();
      $sqlBearerToken = $sql->getToken();
      $sqlConnToken = "AA05bf6a-7c30-4a64-9ba7-7ba7100070d7";

      $sqlQuery_main = $sql->runQuery($sqlBearerToken,$sqlConnToken,$statement1);
      $sqlQuery_res = $sql->runQuery($sqlBearerToken,$sqlConnToken,$statement2);
      $sqlQuery_condo = $sql->runQuery($sqlBearerToken,$sqlConnToken,$statement3);
      $sqlQuery_value_history = $sql->runQuery($sqlBearerToken,$sqlConnToken,$statement4);
      $sqlQuery_owners = $sql->runQuery($sqlBearerToken,$sqlConnToken,$statement5);

      $coords = $this->getPolyCoords($parcel_id);
      $fiscal_year = ( date('m') > 6) ? date('Y') + 1 : date('Y');
      
      return [
        '#theme' => 'bos_assessing',
        '#data_full' => $sqlQuery_main,
        '#data_res' => $sqlQuery_res,
        '#data_condo' => $sqlQuery_condo,
        '#data_owners' => $sqlQuery_owners,
        '#data_value_history' => $sqlQuery_value_history,
        '#data_coords' => $coords,
        '#data_year_current' => date('Y'),
        '#data_year_fiscal' => $fiscal_year,
      ];
  }
  
}
