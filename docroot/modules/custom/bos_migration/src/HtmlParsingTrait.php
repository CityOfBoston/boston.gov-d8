<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Define HTML parsing trait.
 */
trait HtmlParsingTrait {

  /**
   * Gets a DOMDocument logging warnings on migrate id map.
   *
   * @param string $html
   *   The html to parse.
   * @param \Drupal\migrate\MigrateExecutableInterface\MigrateExecutableInterface $migrate_executable
   *   Related migrate executable object, used to store any message if needed.
   *
   * @return \DOMDocument
   *   The instantiated document.
   */
  protected function getDocument($html, MigrateExecutableInterface $migrate_executable) {
    $document = new \DOMDocument('1.0', 'UTF-8');
    // Log WYSIWYG user-entered html parsing warnings on migrate id map.
    // Skipping them is safe, because Drupal will anyway filter the string on
    // database during render.
    set_error_handler(function (int $errno, string $errstr) use ($migrate_executable) {
      $migrate_executable->saveMessage($errstr, MigrationInterface::MESSAGE_NOTICE);
    });
    // Prepend the html with a header to use UTF-8 as source enconding.
    // By default loadHTML() assumes ISO-8859-1.
    $html = '<?xml encoding="UTF-8">' . $html;
    $document->loadHTML($html);
    restore_error_handler();
    return $document;
  }

}
