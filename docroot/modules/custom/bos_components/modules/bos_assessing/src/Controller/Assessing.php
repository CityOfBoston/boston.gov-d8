<?php

namespace Drupal\bos_assessing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\geolocation\Element;



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

      $url = 'https://services.arcgis.com/sFnw0xNflSi8J0uh/ArcGIS/rest/services/AbutterParcels_Oct2020/FeatureServer/0/query?where=PID%3D%27'.$parcel_id.'%27&f=pgeojson';
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
      $url = 'https://data.boston.gov/api/3/action/datastore_search_sql?sql=SELECT%20*%20from%20%228de4e3a0-c1d2-47cb-8202-98b9cbe3bd04%22%20WHERE%20%22PID%22%20LIKE%20%27'.$parcel_id.'%27';
      // Make the request and return the response.
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HTTPGET, true);
      $info = curl_exec($ch);

      if (isset($info)) {
        //$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = json_decode($info);
      } else {
        $data = NULL;
      }

      $coords = $this->getPolyCoords($parcel_id);
  
      return [
        '#theme' => 'bos_assessing',
        '#data_full' => $data,
        '#data_coords' => $coords,
      ];
  }
  
}
