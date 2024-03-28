<?php

namespace Drupal\bos_assessing\Controller;

use Drupal\bos_pdfmanager\Controller\PdfManager;
use Drupal\bos_pdfmanager\PdfFilenames;
use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\bos_sql\Controller\SQL;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Pdf
 * Used to generate PDF for bos_assessing.
 *
 * @package Drupal\bos_assessing\Controller
 */
class Pdf extends ControllerBase {

  /**
   * @var array Holds data to be inserted into the document.
   */
  protected array $data;

  /**
   * @var string Unique ID (parcel_id) (argument from endpoint).
   */
  protected string $id;

  /**
   * @var string holds the year: fmt=FY20XX (argument from endpoint).
   */
  protected string $year;

  /**
   * @var CacheBackendInterface
   */
  protected CacheBackendInterface $cacheBackend;

  public function __construct(CacheBackendInterface $cacheBackend) {
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.assessing_pdf')
    );
  }

  /**
   * Magic function.
   * Catch calls to endpoints that don't exist.
   *
   * @param string $name
   *   Name.
   * @param mixed $arguments
   *   Arguments.
   */
  public function __call($name, $arguments) {
    throw new NotFoundHttpException();
  }

  /**
   * Send the desired document and settings to the PDFManager.
   *
   * @param string $type
   * @param string $year
   * @param string $parcel_id
   *
   * @return Response
   * @throws \Exception
   */
  public function generate(string $type, string $year, string $parcel_id): Response {

    $this->id = $parcel_id;
    $this->year = strtoupper($year);
    $type = strtolower($type);

    $pdf_manager = new PdfManager("Helvetica", "12", [0,0,0]);
    $path = $pdf_manager->getTemplatePath();

    try {
      $dbdata = $this->fetchDBData($type);
    }
    catch(\exception $e) {
      return $this->error($e->getMessage(), 400);
    }

    switch(strtolower($type)) {
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
      default:
        return $this->error("Template for {$type} form not found", 404);
    }

    $template_name = "{$path}/pdf/{$this->year}/nonfillable/{$this->year}_{$template}";
    if (!file_exists("{$template_name}.json")) {
      return $this->error("Configuration for {$template_name} not found.", 400);
    }
    if ($data = file_get_contents("{$template_name}.json")) {
        $this->subData($data, $dbdata, $type);
        $data = json_decode($data, TRUE);
        $this->data["page"] = $data["flat"];
        $this->data["document"] = $data["document"];
        if (empty($this->data['document']['output_dest'])) {
          // Ensure there is a delivery method defined.
          $this->data['document']['output_dest'] = "D";
        }
        unset($data);
    }

    // Check that the file exists, if not, then throw a 400 error.
    if (!file_exists("{$template_name}.pdf")) {
      return $this->error("Template {$template_name}.pdf not found", 400);
    }

    $outfile = "{$this->year}_{$template}_{$parcel_id}-1.pdf";
    $elapsed_time = 0;
    $step = 5;  // Seconds.
    $timeout = 60;  // Seconds.

    while (!$pdf_manager->setLock($outfile, "", $timeout)) {
      // Could not get a lock, means a generation is already in process.
      // Wait for a bit and see if the lock clears.  The code will then resume
      // from here and maybe pick up the cached file if it was created.
      // NOTE: This lock will be cleared when it times-out, or is cleared in
      // this function, or when $pdf_manager shuts down normally.
      sleep($step);
      $elapsed_time += $step;
      if ($elapsed_time >= $timeout ) {
        // After timeout report a deadlock.
        return $this->error("PDF generation already in process.", 400);
      }
    }

    $cache_folder = \Drupal::service('file_system')->realpath("private://assessing-cache");

    if ($this->cacheBackend->get("{$outfile}")) {
      // This file is cached (for a default of 1 week), so return the
      // cached file rather than regenerating.

      $document = NULL;

      if (file_exists("{$cache_folder}/{$outfile}")) {
        $document = new PdfFilenames("{$cache_folder}/{$outfile}");
        $document = $document->path;
      }
      else {
        $this->cacheBackend->delete("{$outfile}");
      }

    }

    if (empty($document))  {
      // This pdf has not been cached, generate it now.

      // Delete the output file if it is already there.
      // TODO - should we do this?
      if (file_exists($outfile)) {
        unlink($outfile);
      }

      // Create the PDF.
      try {
        $document = $pdf_manager
          ->setTemplate("{$template_name}.pdf")
          ->setPageData($this->data["page"])
          ->setDocumentData($this->data["document"])
          ->setOutputFilename($outfile)
          ->generate_flat();
      }
      catch (\Exception $e) {
        $pdf_manager->clearLock($outfile);
        return $this->error($e->getMessage(), 400);
      }

      if (empty($document)) {
        $pdf_manager->clearLock($outfile);
        return $this->error("Generation failed.", 400);
      }

      // Cache this file.
      $this->cacheBackend->set($outfile, "{$outfile}", strtotime("+1week"));
      if (!file_exists("${cache_folder}")) {
        mkdir("${cache_folder}");
      }
      copy($document, "${cache_folder}/${outfile}");

    }

    // Decide how to handle the return.
    switch(strtoupper($this->data['document']['output_dest'])) {

      Case "I":
        // Display the PDF in the users browser if a viewer exists.
        $response = new BinaryFileResponse($document, 200, [
          'Content-Type' => 'application/pdf',
        ], true);
        break;

      Case "F":
        // Return the file location.
        $response = new Response(json_encode($document), 200, [
          'Content-Type' => 'application/json',
        ]);
        break;

      Case "D":
      Default:
        // Download the PDF in the user's browser. This is the default.
        $response = new BinaryFileResponse($document, 200, [
          'Content-Type' => 'application/pdf',
          'Content-Disposition' => "attachment; filename=\"{$outfile}\""
        ], true);
        break;

    }

    $pdf_manager->clearLock($outfile);
    return $response;

  }

  /**
   * Does a pattern substitution, adding data from a DB Query into the supplied
   * string.
   * Searches for %-wrapped strings and substitutes with a value for the
   * matching field in the dbdata array.
   *   e.g.  "Hi %name%" in $template_data expands to "Hi David" if there is
   *   an element $dbdata["name"] = "David".
   *
   * @param string $source A string imported from the json config file.
   * @param array $dbdata
   * @param string $type
   *
   * @return string String with replaced patterns.
   */
  protected function subData(string &$source, array $dbdata, string $type): string {
    foreach ($dbdata as $search => $replace) {
      $replace = $replace ?: "";
      $source = str_ireplace("%{$search}%", strtoupper($replace), $source);
    }
    return $source;
  }

  /**
   * Runs a query against the current database to extract dsata that can be
   * used when updating the PDF template.
   *
   * @param $type string The PDF Template type
   *
   * @return array An array containing the data from the Query.
   * @throws \Exception
   */
  protected function fetchDBData($type):array {
    $sql = new SQL("assessing");
    $count = 0;
    while (!$sql->authenticate()) {
      $count++;
      if ($count <= 10) {
        sleep(10);
      }
    }

    $count = 0;
    while (!$map = $sql->runSelect("taxbill", NULL, [["parcel_id" => $this->id]])) {
      $count++;
      if ($count <= 10) {
        sleep(10);
      }
    }

    if (empty($map)) {
      throw new \Exception("Data error - unknown parcel_id");
    }
    $map = (array) reset( $map);
    // Make sure the parcel_id is the expected parcel id
    if ($map["parcel_id"] != $this->id) {
      throw new \Exception("Data error - unexpected parcel_id");
    }
    $map["year"] = is_numeric($this->year) ? $this->year : substr($this->year, 2,4);
    // Reformat some data
    $map["total_value"] = number_format($map["total_value"], 0, ".", ",");
    $map["streetno"] = trim($map["street_number"] . (empty($map["street_number_suffix"]) ? "": "-" . $map["street_number_suffix"])) ;
    $map["text_address_nolocale"] = "{$map['streetno']} {$map['street_name']}" . (empty($map["apt_unit"]) ? "": " #{$map['apt_unit']}") ;
    $map["text_address_nozip"] = "{$map['text_address_nolocale']}, {$map['city']}";
    $map["text_address"] = "{$map['text_address_nozip']} {$map['location_zip_code']}";
    $map["streetno"] = "{$map['streetno']}" . (empty($map["apt_unit"]) ? "": ", #{$map['apt_unit']}");
    if ($type == "abatement" || $type == "abatementl") {
      $map["ward"] = preg_replace("~(.)(.)~", "$1 $2 $3 $4 $5", substr($map["parcel_id"], 0, 2));
      $map["parcel-0"] = preg_replace("~(.)(.)(.)(.)(.)~", "$1 $2 $3 $4 $5", substr($map["parcel_id"], 2, 5));
      $map["parcel-1"] = preg_replace("~(.)(.)(.)~", "$1 $2 $3 $4 $5", substr($map["parcel_id"], 7, 3));
      $seqnum = $sql->runSP("dbo.sp_get_pdf_data", [
        "parcel_id" => $this->id,
        "form_type" => "overval"
      ]);
      $map["seq_num"] = "{$map["year"]}10000";
      if (isset($seqnum[0][0])) {
        $map["seq_num"] = $seqnum[0][0]->application_number;
      }
    }
    return $map;
  }

  /**
   * Manages the return of an error message to caller.
   *
   * @param string $message a message to return.
   * @param int $code the http response code to pass back.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  protected function error(string $message, int $code = 400): Response {
    if (\Drupal::request()->getMethod() == "GET") {
      // just send a 404
      \Drupal::logger("PDFGenerator")->error("Dispatched {$code} because: {$message}");
      throw new NotFoundHttpException($message);
    }
    else {
      \Drupal::logger("PDFGenerator")->error("Returned {$code} error because: {$message}");
      return new Response(json_encode([
        "error" => $message
      ]), $code);
    }
  }

}
