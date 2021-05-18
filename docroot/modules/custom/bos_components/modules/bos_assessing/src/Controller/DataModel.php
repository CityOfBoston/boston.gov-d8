<?php

namespace Drupal\bos_assessing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Data.
 *
 * @package Drupal\bos_assessing\Controller
 */
class DataModel extends ControllerBase {

  /**
   * Logger object for class.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   *
   */
  protected $log;

  /**
   * Get data from main Assessing controller
   * @param array $data
   */
  public function dataCondo($data) {
      //$dataCondo = json_decode($data);
      //$dataCondo = $dataCondo.result.records[0]['YR_BUILT'];
      //$dataCondo = $data;
      /*foreach ($dataCondo->result->records[0] as $item )
        {
           if($key == "YR_BUILT") {
              return $value;
            }
        }
        */
      $dataCondo = $dataCondo->result->records[0];
      $condos = [
        "Year Built" => $dataCondo->{"YR_BUILT"},
        "Master Parcel" => $dataCondo->{"GIS_ID"},
        "Grade" => "",
        "Exterior Condition" => "",
        "Exterior Finish" => "",
        "Foundation" => "",
        "Roof Cover" => "",
        "Roof Structure" => "",
      ];
      return $condos;
  }
}
