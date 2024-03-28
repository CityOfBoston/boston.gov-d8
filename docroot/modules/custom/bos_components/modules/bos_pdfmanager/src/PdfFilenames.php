<?php

namespace Drupal\bos_pdfmanager;

/**
 * Class: PdfFilenames
 *
 * Class used to control the object structure for a full filename.
 * The class stores and calculates absolute paths, URL and Drupal route maps.
 */
class PdfFilenames {

  /**
   * @var string The public URL for the file (if any)
   */
  public string $url;

  /**
   * @var string The Drupal route for the file (if any)
   */
  public string $route;

  /**
   * @var string The absolute server path for the file.
   */
  public string $path;

  /**
   * @var string The actual filename for the file.
   */
  public string $filename;

  /**
   * @var string A checksum for the file (right now uses the modified date)
   */
  public string $checksum;

  /**
   * @var bool Does this file exist (if $path is provided)
   */
  public bool $exists;

  /**
   * @var bool Should this file be deleted during cleanup
   */
  public bool $delete;

  /**
   * Initializes the object. Takes the provided filename and tries to work out
   * if it is a URL, an absolute path or a Drupal route, and updates the object
   * accordingly.
   *
   * @param string $raw The raw file+path. Can be a url, a path or a Drupal
   * route.
   *
   * @throws \Exception
   */
  public function __construct(string $raw, bool $check_exists = TRUE) {

    global $base_url;

    $public_base = \Drupal::service('file_system')->realpath("public://");
    $this->delete = FALSE;

    // get the actual filename.
    $file = explode("/", $raw);
    $this->filename = array_pop($file);

    if (str_starts_with($raw, "//")) {
      // make this into a url.
      $raw = "https:{$raw}";
    }

    if (str_starts_with($raw, "public://")
      || str_starts_with($raw, "private://")) {
      if (str_starts_with($raw, "private://")) {
        $path = \Drupal::service('file_system')->realpath(str_replace($this->filename, '', $raw));
        copy($path, "{$public_base}/tmp/{$this->filename}");
        $this->delete = TRUE;
        $raw = "public://tmp/{$this->filename}";
      }
      $this->route = $raw;
      $this->setPath(str_replace('public://', '$public_base', $raw));
    }

    elseif (str_starts_with($raw, "http")) {
      // This looks like a url, so try to download the file
      $this->url = $raw;
      if (str_starts_with($raw, $base_url)) {
        // so this is one of ours
        $base_path = \Drupal::service('file_system')->realpath("");
        $this->setPath(str_replace($base_url, $base_path, $raw));
      }
    }

    else {
      // This looks like a (local server) file path.
      if (file_exists($raw)) {
        if (str_starts_with($raw, $public_base)) {
          // is in the path for public:// so just create url etc.
          $this->setPath($raw);
          $this->route = "public:/" . substr($raw, strlen($public_base));
        }
        else {
          // Copy the file to public://tmp and then create url etc.
          copy($raw, "{$public_base}/tmp/{$this->filename}");
          $this->delete = TRUE;
          $this->setPath("{$public_base}/tmp/{$this->filename}");
          $this->route = "public://tmp/{$this->filename}";
        }
      }
      else {
        $this->exists = FALSE;
        if ($check_exists) {
          throw new \Exception("File not found " . $raw);
        }
      }

    }

    if (isset($this->route)) {
      $this->url = \Drupal::service('file_url_generator')
        ->generateAbsoluteString($this->route);
    }

  }

  /**
   * Set the path, query the file properties and save in object.
   *
   * @param string $path an absolute path.
   *
   * @return void
   */
  public function setPath(string $path = "") {
    if (!empty($path)) {
      $this->path = $path;
      $this->exists = file_exists($path);
      $file = explode("/", $path);
      $this->filename = array_pop($file);
      $this->checksum = filemtime($path);
    }
  }

}
