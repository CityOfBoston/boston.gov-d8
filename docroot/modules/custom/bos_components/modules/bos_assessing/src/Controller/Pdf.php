<?php

namespace Drupal\bos_assessing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\bos_sql\Controller\SQL;

/**
 * Class Assessing.
 *
 * @package Drupal\bos_assessing\Controller
 */
class Pdf extends ControllerBase {

  private $data;
  private string $parcel_id;
  private string $year;
  private string $output_dest;

  /**
   * Magic function.  Catches calls to endpoints that dont exist.
   *
   * @param string $name
   *   Name.
   * @param mixed $arguments
   *   Arguments.
   */
  public function __call($name, $arguments) {
    throw new NotFoundHttpException();
  }

  public function generate(string $type, string $year, string $parcel_id) {
    $path = \Drupal::service('file_system')->realpath("") . "/";
    $path .= \Drupal::service('extension.list.module')->getPath('bos_assessing');
    $this->parcel_id = $parcel_id;
    $this->year = $year;
    $type = strtolower($type);

    $pdf_manager = new PdfManager("Helvetica", "12", [0,0,0]);

    $dbdata = $this->fetchDBData($type);

    switch($type) {
      case 'abatement':
        $template = "Abatement_Application_long";
        if (in_array($dbdata["land_use"],["R1", "R2", "R3"])) {
          $template = "Abatement_Application_short";
        }
        break;
      case 'resexempt':
        $template = "Residential_Exemption";
        break;

      case 'persexempt':
        $template = "Personal_Exemption";
        break;
    }

    if ($data = file_get_contents("{$path}/pdf/{$year}/{$year}_{$template}.json")) {
        $this->subData($data, $dbdata, $type);
        $this->data = json_decode($data);
        unset($data);
    }

    return $pdf_manager
      ->setTemplate("{$path}/pdf/{$year}/{$year}_{$template}.pdf")
      ->setPageData($this->data->page)
      ->setDocumentData((array) $this->data->document)
      ->setOutputFilename("{$year}_{$template}_{$parcel_id}.pdf")
      ->generate_flat();

  }

  /**
   * Does a pattern substitution into the json file data.
   *
   * @param string $template_data A string imported from the json config file.
   *
   * @return string JSON String with replaced patterns.
   */
  private function subData(string &$template_data, array $dbdata, string $type): string {
    foreach ($dbdata as $search => $replace) {
      $template_data = str_ireplace("%{$search}%", strtoupper($replace), $template_data);
    }
    return $template_data;
  }

  private function fetchDBData($type) {
    $sql = new SQL();
    $tokens = $sql->getToken("assessing");
    $statement = "SELECT * FROM dbo.taxbill WHERE parcel_id = '{$this->parcel_id}'";
    $map = $sql->runQuery($tokens[0], $tokens[1], $statement);
    $map = (array) $map[0];
    // Make sure the parcel_id is the expected parcel id
    if ($map["parcel_id"] != $this->parcel_id) {
      throw new \Exception("Data error - unexpected pacel_id");
    }
    $map["year"] = is_numeric($this->year) ? $this->year : substr($this->year, 2,4);
    // Reformat some data
    $map["total_value"] = number_format($map["total_value"], 0, ".", ",");
    if ($type == "abatement" || $type == "abatementl") {
      $map["ward"] = preg_replace("~(.)(.)~", "$1 $2 $3 $4 $5", substr($map["parcel_id"], 0, 2));
      $map["parcel-0"] = preg_replace("~(.)(.)(.)(.)(.)~", "$1 $2 $3 $4 $5", substr($map["parcel_id"], 2, 5));
      $map["parcel-1"] = preg_replace("~(.)(.)(.)~", "$1 $2 $3 $4 $5", substr($map["parcel_id"], 7, 3));
      $seqnum = $sql->runSP($tokens[0], $tokens[1], "dbo.sp_get_pdf_data", [
        "parcel_id" => $this->parcel_id,
        "form_type" => "overval"
      ]);
      $map["seq_num"] = "{$map["year"]}10000";
      if (isset($seqnum[0][0])) {
        $map["seq_num"] = $seqnum[0][0]->application_number;
      }
    }
    return $map;
  }

}
