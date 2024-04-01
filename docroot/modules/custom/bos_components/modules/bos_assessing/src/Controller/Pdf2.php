<?php

namespace Drupal\bos_assessing\Controller;

use Drupal\bos_pdfmanager\Controller\PdfManager;
use Drupal\bos_pdfmanager\PdfFilenames;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class Pdf2 extends Pdf {

  /**
   * @inheritDoc
   */
  public function generate(string $type, string $year, string $parcel_id): Response {

    global $base_url;

//    if (str_contains($base_url, "lndo.site")) {
//      $base_url = "https://boston_appserver_1";
//    }

    $this->id = $parcel_id;
    $this->year = strtoupper($year);
    $type = strtolower($type);

    $pdf_manager = new PdfManager();
    $path = $pdf_manager->getTemplatePath();

    try {
      $dbdata = $this->fetchDBData($type);
    }
    catch (\Exception $e) {
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

    $template_name = "{$path}/pdf/{$this->year}/fillable/{$this->year}_{$template}";
    if (!file_exists("{$template_name}.json")) {
      return $this->error("Configuration for {$template_name} not found.", 400);
    }

    if ($data = file_get_contents("{$template_name}.json")) {
      $this->subData($data, $dbdata, $type);
      $data = json_decode($data, TRUE);
      $this->data["page"] = $data["fillable"];
      $this->data["document"] = $data["document"];
      if (empty($this->data['document']['output_dest'])) {
        // Ensure there is a delivery method defined.
        $this->data['document']['output_dest'] = "D";
      }
      unset($data);
    }
    if ($form_data = file_get_contents("{$template_name}.fdf")) {

      $this->subData($form_data, $dbdata, $type);
      $publicpath= \Drupal::service('file_system')->realpath("public://tmp/");
      $fdf_filename = "{$publicpath}/{$this->year}_{$template}_{$parcel_id}.fdf";
      file_put_contents($fdf_filename, $form_data);

      if (empty($this->data['document']['output_dest'])) {
        // Ensure there is a delivery method defined.
        $this->data['document']['output_dest'] = "D";
      }
      unset($form_data);
    }

    // Check that the file exists, if not, then throw a 400 error.
    if (!file_exists("{$template_name}.pdf")) {
      return $this->error("Template {$template_name}.pdf not found", 400);
    }

    $path = \Drupal::service('file_system')->realpath("") ;
    $pdf_filename = str_replace($path, $base_url, "{$template_name}.pdf" );

    $outfile = "{$this->year}_{$template}_{$parcel_id}-2.pdf";
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
    if (empty($document)) {

      // Delete the output file if it is already there.
      if (file_exists($outfile)) {
        unlink($outfile);
      }

      // Create the PDF.
      try {
        $document = $pdf_manager
          ->setTemplate("{$pdf_filename}")
          ->setFormData("{$fdf_filename}")
          ->setPageData($this->data["page"])
          ->setOutputFilename($outfile)
          ->setDocumentData($this->data["document"], "pdftk")
          ->generate_fillable();
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
        // Diplay the PDF in the users browser if a viewer exists.
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
   * @inheritDoc
   */
  protected function subData(string &$source, array $dbdata, string $type):string {
    return parent::subData($source, $dbdata, $type);
  }

  /**
   * @inheritDoc
   */
  protected function fetchDBData($type):array {
    // do more updating into the necessary fmt for fdf
    return parent::fetchDBData($type);
  }

  /**
   * @inheritDoc
   */
  protected function error(string $message, int $code = 400):Response {
    return parent::error($message, $code);
  }
}
