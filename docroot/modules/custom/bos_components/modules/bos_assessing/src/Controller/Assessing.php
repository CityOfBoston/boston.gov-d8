<?php

namespace Drupal\bos_assessing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;


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
      }
  
      return [
        '#theme' => 'bos_assessing',
        '#test_var' => $data,
      ];
  }
  
}
