<?php

namespace Drupal\bos_migration;

/**
 * Logic relevant to filesystem reorganization.
 */
trait FilesystemReorganizationTrait {

  /**
   * Defines mapping/organization for files not in public:// root folder.
   *
   * @var array
   *   Array mapping regex old folder name to a new folder.
   */
  protected $folderMappings = [
    "~//intro_images~" => "//img/unk/intro_images",
    "~//department\-icon\-([0-9]*)\-([0-9]*)~" => "//img/icons/department/$2/$1",
    "~//department_icons~" => "//img/icons/department",
    "~//fyi\-icon\-([0-9]*)\-([0-9]*)~" => "//img/icons/fyi/$2/$1",
    "~//fyi\-icon~" => "//img/icons/fyi",
    "~//paragraphs_type_icon~" => "//img/icons/paragraphs_type",
    "~//status_icons~" => "//img/icons/status",
    "~//status\-item\-icon\-([0-9]*)\-([0-9]*)~" => "//img/icons/status/$2/$1",
    "~//transactions\-icon\-([0-9]*)\-([0-9]*)~" => "//img/icons/transactions/$2/$1",
    "~//default_images~" => "//file/document_files/unk",
    "~//document\-file\-([0-9]*)\-([0-9]*)~" => "//file/document_files/$2/$1",
    "~//document\-file\-([0-9]{4})([0-9]{2})[0-9]{2}~" => "//file/document_files/$1/$2",
    "~//event_intro_images~" => "//img/event/intro_images",
    "~//event\-intro\-image(s|)\-([0-9]*)\-([0-9]*)~" => "//img/event/intro_images/$3/$2",
    "~//event\-thumbnail\-([0-9]*)\-([0-9]*)~" => "//img/event/thumbnails/$2/$1",
    "~//feature_icons~" => "//img/icons/feature",
    "~//field\-columns\-image\-([0-9]*)\-([0-9]*)~" => "//img/columns/$2/$1",
    "~//hero\-image\-([0-9]*)\-([0-9]*)~" => "//img/hero_image/$2/$1",
    "~//how\-to\-intro\-image\-([0-9]*)\-([0-9]*)~" => "//img/how_to/intro_images/$2/$1",
    "~//how_to_intro_images~" => "//img/how_to/intro_images",
    "~//imce\-uploads~" => "//embed/file",
    "~//listing\-page\-intro\-image\-([0-9]*)\-([0-9]*)~" => "//img/listing_page/intro_images/$2/$1",
    "~//listing_page_intro_images~" => "//img/listing_page/intro_images",
    "~//person\-profile\-photo\-([0-9]*)\-([0-9]*)~" => "//img/person_profile/photos/$2/$1",
    "~//photo\-image\-([0-9]*)\-([0-9]*)~" => "//img/library/photos/$2/$1",
    "~//pictures~" => "//img/library/photos/unk",
    "~//place\-profile\-intro\-image\-([0-9]*)\-([0-9]*)~" => "//img/place_profile/intro_images/$2/$1",
    "~//post\-intro\-image\-([0-9]*)\-([0-9]*)~" => "//img/post/intro_images/$2/$1",
    "~//post\-thumbnail\-([0-9]*)\-([0-9]*)~" => "//img/post/thumbnails/$2/$1",
    "~//program\-intro\-image\-([0-9]*)\-([0-9]*)~" => "//img/program/intro_images/$2/$1",
    "~//program\-logo\-([0-9]*)\-([0-9]*)~" => "//img/program/logo/$2/$1",
    "~//quote\-person\-photo\-([0-9]*)\-([0-9]*)~" => "//img/quote_person/photos/$2/$1",
    "~//tabbed\-intro\-image\-([0-9]*)\-([0-9]*)~" => "//img/tabbed/intro_images/$2/$1",
    "~//thumbnails~" => "//img/unk/thumbnails",
    "~//topic\-intro\-image\-([0-9]*)\-([0-9]*)~" => "//img/topic/intro_images/$2/$1",
    "~//topic\-thumbnail\-([0-9]*)\-([0-9]*)~" => "//img/topic/thumbnails/$2/$1",
    "~//video\-image\-([0-9]*)\-([0-9]*)~" => "//img/video/$2/$1",
  ];

  /**
   * Moves images in root public files directory into subdirectory.
   *
   * @param string $uri
   *   URI to rewrite. Must be in stream wrapper format.
   * @param array $properties
   *   The file objects properties (if known).
   *
   * @return string
   *   The uri.
   */
  public function rewriteUri(string $uri, array $properties = []) {

    // Move public files out of root directory.
    if (strpos($uri, 'public://') !== FALSE) {
      $relative_uri = str_replace('public://', NULL, $uri);
      // Now that we have removed he public stream wrapper, files in the root
      // directory should not contain a slash in their URI.
      if (strpos($relative_uri, '/') === FALSE) {
        $fileType = isset($properties['filemime'])
          ? $this->resolveFileTypeMime($properties['filemime'])
          : $this->resolveFileTypeArray($properties['uri']);

        if (isset($fileType)) {
          if (in_array('image', $fileType)) {
            $hash = "img/";
            $hash .= isset($properties['timestamp']) ? date("Y\/", $properties['timestamp']) : "";
            $hash .= strtolower($relative_uri[0]);
          }
          elseif (in_array('file', $fileType)) {
            if (!empty($properties['timestamp'])) {
              $hash = "file/" . date("Ymd", $properties['timestamp']);
            }
            else {
              $hash = "file/migrate";
            }
          }
          else {
            // The class calling this trait can set the path.
            if (method_exists($this, "setPath")) {
              $hash = trim($this->setPath($uri), "/");
            }
            else {
              // Sets a last-chance default.
              $hash = "unk/migrate";
            }
          }
        }
        else {
          // The class calling this trait can set the path.
          if (method_exists($this, "setPath")) {
            $hash = trim($this->setPath($uri), "/");
          }
          else {
            // Sets a last-chance default.
            $hash = "unk/migrate";
          }
        }
        $uri = "public://{$hash}/{$relative_uri}";
      }
      else {
        $count = 0;
        // Relocate file based on regex mapping on uri.
        foreach ($this->folderMappings as $find => $replace) {
          $uri = preg_replace($find, $replace, $uri, -1, $count);
          if ($count > 0) {
            break;
          }
        }
      }
    }

    return $uri;
  }

  /**
   * Determine filetype.
   *
   * @param string $uri
   *   The URI.
   *
   * @return array
   *   File type - image, file or link.
   */
  private function resolveFileTypeArray($uri) {
    // White list files based on file_managed table in D7.
    $parts = explode('/', $uri);
    $index = count($parts) - 1;
    $type = [];
    $allowedFormats = MigrationFixes::allowedFormats();
    foreach ($allowedFormats as $file_type => $formats) {
      foreach ($formats as $extension) {
        if (strpos($parts[$index], $extension) !== FALSE) {
          $type[] = $file_type;
        }
      }
    }
    if (!empty($type)) {
      return $type;
    }

    // If there is no extension, or the extension is not matched, then return
    // a type of "link".
    return ["link"];
  }

  /**
   * Determine filetype.
   *
   * @param string $mime
   *   The URI.
   *
   * @return array
   *   File type - image, file or link.
   */
  private function resolveFileTypeMime($mime) {
    // White list files based on file_managed table in D7.
    $parts = explode('/', $mime);
    $index = count($parts) - 1;
    $type = [];
    $allowedFormats = MigrationFixes::allowedFormats();
    foreach ($allowedFormats as $file_type => $formats) {
      if (in_array($parts[$index], $formats)) {
        $type[] = $file_type;
      }
    }
    if (!empty($type)) {
      return $type;
    }

    // If there is no extension, or the extension is not matched, then return
    // a type of "link".
    return ["link"];
  }

}
