<?php

namespace Drupal\bos_migration;

/**
 * Logic relevant to filesystem reorganization.
 */
trait FilesystemReorganizationTrait {

  /**
   * Moves images in root public files directory into subdirectory.
   *
   * @param string $uri
   *   URI to rewrite. Must be in stream wrapper format.
   *
   * @return string
   *   The uri.
   */
  public function rewriteUri(string $uri) {

    // Move public files out of root directory.
    if (strpos($uri, 'public://') !== FALSE) {
      $relative_uri = str_replace('public://', NULL, $uri);
      // Now that we have removed he public stream wrapper, files in the root
      // directory should not contain a slash in their URI.
      if (strpos($relative_uri, '/') === FALSE) {
        $hash = md5($relative_uri);
        $uri = "public://{$hash}/{$relative_uri}";
      }
    }

    return $uri;
  }

}
