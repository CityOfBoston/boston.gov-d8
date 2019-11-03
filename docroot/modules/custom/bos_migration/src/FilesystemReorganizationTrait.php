<?php

namespace Drupal\bos_migration;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Logic relevant to filesystem reorganization.
 */
trait FilesystemReorganizationTrait {

  /**
   * Regex to capture relevant subdomains of boston.gov.
   *
   * @var string
   */
  protected static $localReferenceREGEX = '((http(s)?://)??((edit|www)\.)?boston\.gov|^(/)?sites/default/files/)';
  protected static $assetsReferenceREGEX = '((http(s)?://)??(assets|patterns).boston\.gov)';

  /**
   * Array to identify file extensions allowed by media type.
   *
   * @var array
   */
  protected static $allowedFormats = [
    'image' => [
      'jpg',
      'png',
      'jpeg',
      'gif',
      'tif',
      'svg',
      'svg+xml',
    ],
    'link' => [
      'pdf',
      'xls',
      'xlsx',
      'docx',
      'doc',
      'pptx',
      'pptm',
      'ppt',
      'rtf',
      'ppt',
      'jnlp', /* Not sure we should allow this. */
      'xlsm',
      'mp3',
      'mp4',
      'jpg',
      'png',
      'jpeg',
      'tif',
      'svg',
    ],
  ];

  /**
   * Array to identify file by extension and/or mime type.
   *
   * @var array
   */
  protected static $matchFormats = [
    'image' => [
      'jpg',
      'png',
      'jpeg',
      'gif',
      'tif',
    ],
    'icon' => [
      'svg',
      'svg+xml',
    ],
    'document' => [
      'pdf',
      'xls',
      'xlsx',
      'docx',
      'doc',
      'pptx',
      'pptm',
      'ppt',
      'rtf',
      'ppt',
      'jnlp', /* Not sure we should allow this. */
      'xlsm',
      'mp3',
      'mp4',
    ],
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
    /*
     * Defines mapping/organization for files not in public:// root folder.
     *
     * @var array
     *   Array mapping regex old folder name to a new folder.
     */
    $folderMappings = [
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

    // Move public files out of root directory.
    if (strpos($uri, 'public://') !== FALSE) {
      $relative_uri = str_replace('public://', NULL, $uri);
      // Now that we have removed he public stream wrapper, files in the root
      // directory should not contain a slash in their URI.
      $source_uri = $properties['uri'] ?: $uri;
      if (strpos($relative_uri, '/') === FALSE) {
        $fileType = isset($properties['filemime'])
          ? $this->resolveFileTypeMime($properties['filemime'])
          : $this->resolveFileTypeArray($source_uri);

        if (isset($fileType)) {
          if (in_array('image', $fileType)) {
            $hash = "img/";
            $hash .= isset($properties['timestamp']) ? date("Y\/", $properties['timestamp']) : "";
            $hash .= strtolower($relative_uri[0]);
          }
          elseif (in_array('document', $fileType)) {
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
        foreach ($folderMappings as $find => $replace) {
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
    if (NULL == $uri) {
      return NULL;
    }
    $type = [];
    $ext = $this->extractExtension($uri);
    foreach (self::$matchFormats as $file_type => $formats) {
      foreach ($formats as $extension) {
        if ($ext == $extension) {
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
   * Extracts bare filename from normal path with a filename.
   *
   * @param string $src
   *   The full pathname with folders delimited by "/"..
   *
   * @return string
   *   The filename extracted from the $src path.
   */
  private function extractFilename(string $src) {
    $parts = explode('/', $src);
    $filename = trim(end($parts));
    // Clean up any parameters/'querystrings'.
    foreach (["#", "?"] as $delim) {
      if (strpos($filename, $delim) !== FALSE) {
        $filename = explode($delim, $filename, 2)[0];
      }
    }
    return trim($filename);
  }

  /**
   * Extracts the extension from a file path or uri.
   *
   * @param string $src
   *   The path or Uri.
   *
   * @return false|mixed|string
   *   A string representing the extension of the file, FALSE if nothing found.
   */
  private function extractExtension($src) {

    $filename = $this->extractFilename($src);

    // Verify that this file has an extension.
    if (strpos($filename, ".") === FALSE) {
      return FALSE;
    }

    // Now extract chars after the last ".".
    $extension = end(explode(".", $filename));
    if (!empty($extension)) {
      return trim($extension);
    }
    // Could try other things ???
    return FALSE;
  }

  /**
   * Extracts bare filename without _1 etc from normal path with a filename.
   *
   * @param string $src
   *   The full pathname with folders delimited by "/"..
   * @param bool $safe
   *   Flag to simply delete numeric chars at the end of a filename.
   *
   * @return string
   *   The filename extracted from the $src path.
   */
  private function extractBaseFilename(string $src, bool $safe = TRUE) {
    $filename = $this->extractFilename($src);
    $parts = explode('.', $filename);
    $extension = ($parts[1] ?: "");
    // Remove extension from filename.
    $base_filename = trim($parts[0]);
    // See if file has a "_0" style versioning.
    $parts = explode("_", $base_filename);
    if (is_numeric(end($parts))) {
      // So remove the filename versioning.
      $search = "_" . end($parts);
      $base_filename = str_replace($search, "", $base_filename) . "." . $extension;
      return $base_filename;
    }
    // Maybe the last char is numeric, if so remove it (dangerous!).
    // Recommend only use for comparision like in $this->remapIconUri().
    if (!$safe) {
      while (is_numeric(substr($base_filename, -1))) {
        $base_filename = substr($base_filename, 0, -1);
      }
    }
    return trim($base_filename);
  }

  /**
   * Determine filetype.
   *
   * @param string $type
   *   The type of file we are looking for.
   * @param string $uri
   *   The URI.
   *
   * @return bool
   *   File type - image, file or link.
   */
  private function permittedFileType(string $type, string $uri) {
    $ext = $this->extractExtension($uri);
    $formats = self::$allowedFormats[$type];
    foreach ($formats as $extension) {
      if ($ext == $extension) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determine filetype.
   *
   * @param string $mime
   *   The MIME for the type.
   *
   * @return array
   *   File type - image, file or link.
   */
  private function resolveFileTypeMime($mime) {
    // White list files based on file_managed table in D7.
    $type = [];
    foreach (self::$allowedFormats as $file_type => $formats) {
      if (in_array($mime, $formats)) {
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

  /**
   * Converts relative URIs to stream wrapper format.
   *
   * @param string $uri
   *   The relative URI.
   *
   * @return string
   *   The converted URI.
   */
  protected function convertToStreamWrapper(string $uri) {
    $new_uri = str_replace('/sites/default/files/private/', 'private://', $uri);
    if ($new_uri === $uri) {
      // The replacement wasn't found, so this must be a public file.
      $new_uri = str_replace('/sites/default/files/', 'public://', $uri);
    }

    return $new_uri;
  }

  /**
   * Determines if uri is relative.
   *
   * @param string $uri
   *   The Uri.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  protected function isRelativeUri(string $uri) {
    if (preg_match('@^/sites/default/files/.*@', $uri)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Retrieves the relative part of an absolute URL.
   *
   * @param string $uri
   *   An uri to inspect.
   *
   * @return string|bool
   *   The request path part of the uri, or FALSE if not an searched absolute
   *   uri.
   */
  protected function getRelativeUrl(string $uri) {
    $from_main_domain = '@^http(s|)://(www.|edit.|)boston.gov[/]+(.*)@';
    if (!preg_match($from_main_domain, $uri, $matches)) {
      // Not a searched absolute uri.
      return FALSE;
    }
    if (substr($matches[3], 1, 1) != "/") {
      $matches[3] = "/" . $matches[3];
    }
    return $matches[3];
  }

  /**
   * Determines if file is external.
   *
   * @param string $src
   *   The file src.
   *
   * @return bool
   *   Yes or no.
   */
  protected function isExternalFile($src) {
    // If its not on the boston.gov domain then its external.
    if (!preg_match(self::$localReferenceREGEX, $src)) {
      return TRUE;
    }
    // If its patterns/assets.boston.gov, then its external.
    if (preg_match(self::$assetsReferenceREGEX, $src)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Replaces domain/uri strings.
   *
   * E.g: edit.boston.gov with www.boston.gov.
   *
   * @param string $uri
   *   The original URI (or source or whatever).
   *
   * @return string
   *   The source with correct destintaion mapped in.
   */
  protected function correctSubDomain(string $uri) {
    $regex_swaps = [
      "~(edit|edit-stg)\.boston.gov~" => "www.boston.gov",
      "~http(s|)://boston\.gov~" => "https://www.boston.gov",
      "~http://.*\.boston\.gov~" => "https://www.boston.gov",
      "~^(?!http)(\w+.*)~" => "https://$1",
    ];
    foreach ($regex_swaps as $find => $replace) {
      $swap = $uri;
      try {
        $uri = preg_replace($find, $replace, $uri);
        if (is_null($uri)) {
          $uri = $swap;
        }
      }
      catch (Exception $e) {
        return $swap;
      }
    }
    return $uri;
  }

  /**
   * True/false if the file uri already exists in DB.
   *
   * @param string $uri
   *   Does the files_managed table have a record with this uri.
   *
   * @return bool
   *   True if record is found, else false.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isFileInDb($uri) {
    return empty($this->getFileEntities($uri));
  }

  /**
   * Retrieves fid(s) for corresponding file entity/ies.
   *
   * Best effort search.
   * Assumes the uri have not changed from d7 to d8 during migration, which is
   * just an heuristic.
   *
   * @param string $uri
   *   File uri.
   *
   * @return \Drupal\file\Entity\File|null
   *   File entity object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getFileEntities($uri) {
    $query = \Drupal::entityQuery("file")
      ->condition("uri", $uri, "=");
    $entities = $query->execute();
    if (empty($entities)) {
      return NULL;
    }
    return $entities;
  }

  /**
   * Retrieves the corresponding file entity.
   *
   * Best effort search.
   * Assumes the uri have not changed from d7 to d8 during migration, which is
   * just an heuristic.
   *
   * @param string $filename
   *   The filename.
   * @param string|null $filesize
   *   The filesize (in bytes).
   *
   * @return mixed
   *   Collection of File entity objects, or null.
   */
  public function getFilesByFilename(string $filename, string $filesize = NULL) {
    $query = \Drupal::entityQuery("file")
      ->condition("filename", $filename, "=");
    if (isset($filesize)) {
      $query->condition("filesize", $filesize, "=");
    }
    $entities = $query->execute();
    if (empty($entities)) {
      return NULL;
    }
    return $entities;
  }

  /**
   * Create a new File object and save (in table file_managed).
   *
   * @param string $uri
   *   The uri to create in the file_managed table.
   * @param int $fid
   *   Force the fid value.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The file object just created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function createFileEntity(string $uri, int $fid = 0) {
    $filename = self::cleanFilename($uri);
    $fields = [
      'uri' => $uri,
      'uid' => '1',
      'filename' => $filename,
      'status' => '1',
    ];
    if ($fid != 0) {
      $fields["fid"] = $fid;
    }
    $entity = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->create($fields);
    $entity->save();
    return $entity;
  }

  /**
   * Attempts to find & set a physical path for this media entity.
   */
  protected function setPath($uri) {
    $filename = end(explode("/", $uri));
    /*$filename = explode(".", $filename)[0];*/
    return "embed/" . $filename[0];
  }

  /**
   * Attempts to build a meaningful filename from a given file path and name.
   *
   * @param string $path
   *   The filename and path.
   *
   * @return string
   *   Reformatted filename.
   */
  public static function cleanFilename($path) {
    $filename = explode("/", $path);
    $extension = end(explode(".", end($filename)));
    $filename = array_pop($filename);
    $filename = str_replace([
      "icons",
      "logo",
      ".svg",
      ".jpg",
      ".gif",
      ".jpeg",
      ".png",
      ".txt",
      ".xlsx",
      ".pdf",
    ], "", $filename);
    $filename = str_replace("icon", "", $filename);
    $filename = str_replace(["-", "_", "."], " ", $filename);
    $filename = preg_replace("~\s+~", " ", $filename);
    if (in_array($extension, ["pdf", "xls", "xlsx", "txt"])) {
      $filename .= " (" . $extension . ")";
    }
    return strtolower($filename);
  }

  /**
   * Remaps the destination uri based upon values in an array.
   *
   * @param string $uri
   *   The original destination uri.
   *
   * @return string
   *   The remapped uri.
   */
  public function remapIconUri(string $uri) {
    $map = MigrationFixes::$svgMapping;
    if (NULL != ($new_uri = $map[$uri])) {
      // Found a matching element, just convert and return.
      return $new_uri;
    }

    // The manifest provides a map of icons to paths.
    $base_filename = $this->extractBaseFilename($uri, FALSE);
    foreach (["experiential_icons_"] as $search) {
      $base_filename = str_replace($search, "", $base_filename);
    }
    $manifest = $this->getIconLibraryManifest();
    $base_filename = $this::cleanFilename($base_filename);
    if (NULL != ($manifest_item = $manifest[$base_filename])) {
      // Found a matching element, just convert and return.
      return $manifest_item;
    }

    // Couldn't map, so simply return the $uri provided.
    return $uri;
  }

  /**
   * Grab a list of available icons from library.
   *
   * @return array
   *   The array of icons which exist in the media library.
   */
  private function getIconLibraryManifest() {
    if (NULL == ($result = \Drupal::state()->get("bos_core.icon_library.manifest"))) {
      $query = \Drupal::database()->select("media_field_data", "m")
        ->fields("m", ["name", "mid"]);
      $query->join("media__image", "mi", "m.mid = mi.entity_id");
      $query->join("file_managed", "f", "mi.image_target_id = f.fid");
      $query->fields("f", ["fid", "uri"]);
      $query->join("media__field_media_in_library", "ml", "m.mid = ml.entity_id");
      $query->condition("ml.field_media_in_library_value", "1");
      $query->condition("m.bundle", "icon");
      $result = $query->fetchAllAssoc["name"];
      \Drupal::state()->get("bos_core.icon_library.manifest", $result);
    }
    return $result;
  }

  /**
   * Creates simple source:dest entry in the migrate_map_xxx table for lookups.
   *
   * @param int $sourceid
   *   The oringinal ID to map.
   * @param string $destid
   *   The new ID to map.
   * @param string $mig_type
   *   The table type to write to.
   * @param string $hash
   *   The hash (optional) - Note row hash is created in-function.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public static function createSimpleMappingEntry($sourceid, $destid, $mig_type, $hash = "") {
    try {
      $fields["sourceid1"] = $sourceid;
      $fields += [
        'source_row_status' => MigrateIdMapInterface::STATUS_IMPORTED,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => $hash,
      ];
      $fields["destid1"] = $destid;
      $fields["last_imported"] = 0;
      $row_hash = hash('sha256', serialize(array_map('strval', [$sourceid])));
      $keys = ["source_ids_hash" => $row_hash];

      $table_name = "migrate_map_" . $mig_type;

      \DRUPAL::database()->delete($table_name)
        ->condition("sourceid1", $fields["sourceid1"])
        ->condition("destid1", $destid)
        ->execute();

      \DRUPAL::database()->merge($table_name)
        ->key($keys)
        ->fields($fields)
        ->execute();
    }
    catch (\Exception $e) {
      // Got an error. SQL-23000 is a duplicate row entry, thats OK so allow
      // it but dont allow anything else.
      if ($e->getCode() != 23000) {
        throw new MigrateException($e->getMessage(), $e->getCode());
      }
    }
  }

}
