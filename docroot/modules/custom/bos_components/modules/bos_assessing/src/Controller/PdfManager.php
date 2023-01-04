<?php

namespace Drupal\bos_assessing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Picqer\Barcode\BarcodeGeneratorJPG;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Assessing.
 *
 * @package Drupal\bos_assessing\Controller
 */
class PdfManager extends ControllerBase {
  protected string $tmppath;
  protected $pdf;
  protected $text;
  protected $template;
  protected $barcode;
  protected string $default_font;
  protected string $default_size;
  protected array $default_color;
  protected string $outputname;
  protected array $tmpfiles;
  protected $meta_data;

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

  public function __construct(string $default_font = "Helvetica", int $default_size = 12, array $default_color = [0,0,0]) {
    $this->tmppath = \Drupal::service('file_system')->realpath("public://") . "/tmp";
    if (!file_exists($this->tmppath)) {
      mkdir($this->tmppath);
    }
    $this->pdf = new Fpdi();
    $this->default_font = $default_font;
    $this->default_size = $default_size;
    $this->default_color = $default_color;
    $this->tmpfiles = [];
  }

  public function setTemplate($filename): PdfManager {
    $this->template = $filename;
    return $this;
  }

  public function setOutputFilename($filename): PdfManager {
    $this->outputname = $filename;
    return $this;
  }

  public function setPageData($page_data) {
    if (!empty($page_data)) {
      foreach ($page_data as $page_number => $page) {
        foreach ($page as $page_element) {
          switch (strtolower($page_element->type)) {
            case "text":
              /**
               * @var $page_element \Drupal\bos_assessing\Controller\pdfTextElement
               */
              if (!isset($this->text[$page_number + 1])) {
                $this->text[$page_number + 1] = [];
              }
              $this->text[$page_number + 1][] = new pdfTextElement(
                $page_element->x,
                $page_element->y,
                $page_element->txt,
                isset($page_element->size) ? $page_element->size : $this->default_size,
                isset($page_element->font) ? $page_element->font : $this->default_font,
                isset($page_element->color) ? $page_element->color : $this->default_color,
              );
              break;

            case "barcode":
              if (!isset($this->barcode[$page_number + 1])) {
                $this->barcode[$page_number + 1] = [];
              }
              // Only support Code128 for now.
              $encode = ($page_element->encode == "Code128" ? BarcodeGeneratorJPG::TYPE_CODE_128 : NULL);

              $this->barcode[$page_number + 1][] = new pdfBarcodeElement(
                $page_element->x,
                $page_element->y,
                $page_element->val,
                $encode,
                isset($page_element->color) ? $page_element->color : $this->default_color
              );
              break;
          }
        }
      }
    }
    return $this;
  }

  public function setDocumentData(array $document_data) {
    if (!empty($document_data)) {
      $this->meta_data = $document_data;
    }
    return $this;
  }

  public function generate_flat() {
    try {
      $pageCount = $this->pdf->setSourceFile($this->template);
      $this->pdf->SetFont($this->default_font);
      $this->pdf->SetFontSize($this->default_size);
      $this->pdf->SetTextColor($this->default_color[0], $this->default_color[1], $this->default_color[2]);
      for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $this->pdf->importPage($pageNo);
        $size = $this->pdf->getTemplateSize($templateId);
        $this->pdf->AddPage($size['orientation'], $size);
        $this->pdf->useTemplate($templateId);
        $this->insertText($pageNo);
        $this->insertBarcode($pageNo);
      }
      $this->cleanUp($this->tmpfiles);
      $this->pdf->SetAuthor(!empty($this->meta_data["author"]) ? $this->meta_data["author"] : "City of Boston");
      $this->pdf->SetTitle(!empty($this->meta_data["title"]) ? $this->meta_data["title"] : "Official PDF Document");
      $this->pdf->SetSubject(!empty($this->meta_data["subject"]) ? $this->meta_data["subject"] : "");
      return $this->pdf->Output(!empty($this->meta_data["output_dest"]) ? $this->meta_data["output_dest"] : "D", $this->outputname);
    }
    catch (PdfParserException $e) {
      return;
    }
    catch (PdfReaderException $e) {
      return;
    }

  }

  public function generate_fillable() {
    $this->makeBarcodePdf($this->barcode);
  }

  private function insertText(int $pageNo) {
    if (!empty($this->text[$pageNo])) {
      /**
       * @var $element pdfTextElement
       */
      foreach ($this->text[$pageNo] as $element) {

        // Override the default text attributes.
        if (isset($element->font)) {
          $this->pdf->SetFont($element->font);
        }
        if (isset($element->size)) {
          $this->pdf->SetFontSize($element->size);
        }
        if (isset($element->color)) {
          $this->pdf->SetTextColor($element->color[0], $element->color[1], $element->color[2]);
        }

        $this->pdf->Text($element->x, $element->y, trim($element->text));

        // Reset text attributes to default (if they were set).
        if (isset($element->font)) {
          $this->pdf->SetFont($this->default_font);
        }
        if (isset($element->size)) {
          $this->pdf->SetFontSize($this->default_size);
        }
        if (isset($element->color)) {
          $this->pdf->SetTextColor($this->default_color[0], $this->default_color[1], $this->default_color[2]);
        }

      }
    }
  }

  private function insertBarcode(int $pageNo) {
    if (!empty($this->barcode[$pageNo])) {
      /**
       * @var $element pdfBarcodeElement
       */
      foreach ($this->barcode[$pageNo] as $element) {
        $barcode = new BarcodeGeneratorJPG();
        $tmpfilename = str_ireplace(".pdf", "_{$pageNo}.jpg", $this->outputname);
        $tmpfilename = "{$this->tmppath}/{$tmpfilename}";
        $color = empty($element->color) ? $this->default_color : $element->color;
        if (file_put_contents($tmpfilename, $barcode->getBarcode($element->val, $element->encode, 2, 45, $color))) {
          $this->tmpfiles[] = $tmpfilename;
          $this->pdf->Image($tmpfilename, $element->x, $element->y);
        }
      }
    }
  }

  private function makeBarcodePdf($elements) {
    $tmpfiles = [];
    $pdf = new \FPDF();
    foreach ($elements as $page) {
      $pdf->AddPage("P", "letter");
      foreach ($page as $key => $element) {
        $barcode = new BarcodeGeneratorJPG();
        $tmpfilename = str_ireplace(".pdf", "_" . time() . "{$key}.jpg", $this->outputname);
        $tmpfilename = "{$this->tmppath}/{$tmpfilename}";
        $color = isset($element->color) ? $element->color : $this->default_color;
        if (file_put_contents($tmpfilename, $barcode->getBarcode($element->val, $element->encode, 2, 45, $color))) {
          $tmpfiles[] = $tmpfilename;
          $pdf->Image($tmpfilename, $element->x, $element->y);
        }
      }
    }
    $pdf->Output("F", str_ireplace(".pdf", "_" . time() . ".pdf", "{$this->tmppath}/{$this->outputname}"));
    $this->cleanUp($tmpfiles);
    return;
  }

  private function cleanUp(array &$file_list) {
    foreach($file_list as $key => $file) {
      if (file_exists($file)) {
        unlink($file);
        unset($file_list[$key]);
      }
    }
    return $file_list;
  }

}

class pdfTextElement {
  public int $x;
  public int $y;
  public string $font;
  public int $size;
  public array $color;
  public string $text;
  public function __construct(int $x, int $y, string $text, int $size = NULL, string $font = NULL, array $color = []) {
    $this->x = $x;
    $this->y = $y;
    $this->text = $text;
    if (!empty($font)) {
      $this->font = $font;
    }
    if (!empty($size)) {
      $this->size = $size;
    }
    if (!empty($color)) {
      $this->color = $color;
    }
    return $this;
  }
}
class pdfBarcodeElement {
  public int $x;
  public int $y;
  public string $val;
  public string $encode; // a constant from BarcodeGeneratorJPG eg ::Code128 (C128)
  public array $color;
  public function __construct(int $x, int $y, string $val, string $encode, array $color = []) {
    $this->x = $x;
    $this->y = $y;
    $this->val = $val;
    $this->encode = $encode;
    if (!empty($color)) {
      $this->color = $color;
    }
    return $this;
  }
}
