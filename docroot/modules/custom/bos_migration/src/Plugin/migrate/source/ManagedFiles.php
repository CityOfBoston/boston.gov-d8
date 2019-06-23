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
  use \Drupal\bos_migration\FilesystemReorganizationTrait;

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->publicPathMigration = $this->variableGet('file_public_path', 'sites/default/files');
    $this->privatePathMigration = $this->variableGet('file_private_path', NULL);
    $this->temporaryPathMigration = $this->variableGet('file_temporary_path', '/tmp');
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Compute the filepath property, which is a physical representation of
    // the URI relative to the Drupal root.
    $path = str_replace(
      ['public:/', 'private:/', 'temporary:/'],
      [
        $this->publicPathMigration,
        $this->privatePathMigration,
        $this->temporaryPathMigration,
      ],
      $row->getSourceProperty('uri')
    );

    $row->setSourceProperty('source_base_path', $path);

    $rewritten_uri = $this->rewriteUri($row->getSourceProperty('uri'), $row->getSource());

    if ($rewritten_uri !== $row->getSourceProperty('uri')) {
      $row->setSourceProperty('uri', $rewritten_uri);
    }

    return parent::prepareRow($row);
  }

}
