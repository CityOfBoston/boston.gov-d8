<?php

namespace Drupal\bos_migration\Plugin\migrate\source;

use Drupal\file\Plugin\migrate\source\d7\File;
use Drupal\migrate\Row;

/**
 * Managed files migration.
 *
 * @MigrateSource(
 *   id = "managed_files"
 * )
 */
class ManagedFiles extends File {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->publicPathMigration = $this->variableGet('file_public_path', 'sites/default/files') . '/migration';
    $this->privatePathMigration = $this->variableGet('file_private_path', NULL) . '/migration';
    $this->temporaryPathMigration = $this->variableGet('file_temporary_path', '/tmp') . '/migration';
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Compute the filepath property, which is a physical representation of
    // the URI relative to the Drupal root.
    $path = str_replace(['public:/', 'private:/', 'temporary:/'], [$this->publicPathMigration, $this->privatePathMigration, $this->temporaryPathMigration], $row->getSourceProperty('uri'));
    $row->setSourceProperty('source_base_path', $path);

    // Move public files out of root directory.
    if (strpos($row->getSourceProperty('uri'), 'public://') !== FALSE) {
      $relative_uri = str_replace('public://', NULL, $row->getSourceProperty('uri'));
      // After stripping the stream wrapper, files in the root directory will
      // nessicarily be eqal to their filenames.
      if ($relative_uri === $row->getSourceProperty('filename')) {
        $hash = md5($row->getSourceProperty('filename'));
        $row->setSourceProperty('uri', "public://{$hash}/{$relative_uri}");
      }
    }
    return parent::prepareRow($row);
  }

}
