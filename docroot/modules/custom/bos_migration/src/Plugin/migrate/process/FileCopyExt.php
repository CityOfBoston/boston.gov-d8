<?php


namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * @code
 * process:
 *   path_to_file:
 *     plugin: file_copy_ext
 *     source:
 *       - /path/to/file.png
 *       - public://new/path/to/file.png
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "file_copy_ext"
 * )
 */

class FileCopyExt extends FileCopy {

  /**
   * Extend the actual copy action to squash file-not-found errors.
   *
   * Replaces the fileCopy->transform funcion.
   *
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    list($source, $destination) = $value;

    // If we don't have an actual file action, then dont do anything.
    if (!empty($this->configuration['move']) || !empty($this->configuration['copy'])) {
      return $destination;
    }

    if (!file_exists($source)) {
      $fid=$row->getSource()['fid'];
      $migrate_executable->saveMessage("File (fid:$fid) '$source' does not exist", MigrationInterface::MESSAGE_NOTICE);
      return $destination;
    }

    return parent::transform($value, $migrate_executable, $row, $destination_property);

  }

  /**
   * Tries to move or copy a file.  Extended to do neither.
   *
   * Replaces the fileCopy->writeFile funcion.
   *
   * {@inheritdoc}
   */
  protected function writeFile($source, $destination, $replace = FILE_EXISTS_REPLACE) {
    // Check if there is a destination available for copying. If there isn't,
    // it already exists at the destination and the replace flag tells us to not
    // replace it. In that case, return the original destination.
    if (!($final_destination = file_destination($destination, $replace))) {
      return $destination;
    }
    if (!empty($this->configuration['move']) || !empty($this->configuration['copy'])) {
      $function = 'file_unmanaged_' . ($this->configuration['move'] ? 'move' : 'copy');
    }
    return $function($source, $destination, $replace);
  }

}
