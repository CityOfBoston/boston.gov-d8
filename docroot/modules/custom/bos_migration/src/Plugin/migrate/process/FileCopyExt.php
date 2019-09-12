<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Extends the file_copy plugin (class fileCopy).
 *
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
    // If we're stubbing a file entity, return a uri of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }

    list($source, $destination) = $value;
    $source = $source ?? $row->getSourceProperty('source_base_path');
    $fid = $row->getSource()['fid'];

    // If the migration is disabled, then skip file operations.
    if (\Drupal::state()->get("bos_migration.active", 0) == 0) {
      throw new MigrateSkipRowException("State bos_migration.active is missing or false.", FALSE);
    }

    // Overide the supplied config with state settings.
    $fileOps = \Drupal::state()->get("bos_migration.fileOps", "none");
    if ($fileOps == "none") {
      $this->configuration['copy'] = $this->configuration['move'] = FALSE;
    }
    else {
      $this->configuration['copy'] = ($fileOps == "copy" ? "true" : "false");
      $this->configuration['move'] = ($fileOps == "move" ? "true" : "false");
      $this->configuration['file_exists_ext'] = \Drupal::state()->get("bos_migration.file_exists_ext", "skip");
    }

    // If we don't have an actual file action, then don't do file operations.
    if (empty($this->configuration['move']) && empty($this->configuration['copy'])) {
      return $destination;
    }

    // Map our remote_source path prefix onto the source so we can download it.
    if (isset($this->configuration["remote_source"]) && strpos($source, $this->configuration["remote_source"]) === FALSE) {
      $source = $this->configuration["remote_source"] . $source;
      $source = preg_replace("~([A-Za-z0-9])//~", "$1/", $source);
    }

    // If this is a local file and the source does not exist, then report issue
    // and skip.
    if (parent::isLocalUri($source) && !file_exists($source)) {
      $migrate_executable->saveMessage("File (fid:$fid) '$source' does not exist", MigrationInterface::MESSAGE_NOTICE);
      throw new MigrateSkipRowException("Local source file ($source) does not exist.", TRUE);
    }

    // Save the newly created source.
    $value[0] = $source;

    // Check for duplicates of this file in managed_files (by name and size).
    $dup = \Drupal::entityQuery("file")
      ->condition("filename", $row->getSource()['filename'], "=")
      ->condition("filesize", $row->getSource()['filesize'], "=")
      ->execute();

    if (!empty($dup)) {
      // There is already a file with this name, so create a mapping entry and
      // step over it.
      $destid = reset($dup);
      try {
        $migrate_executable->saveMessage("Remapping fid:" . $fid . " => " . $destid . " (" . $source . ")", MigrationInterface::MESSAGE_INFORMATIONAL);
        \DRUPAL::database()
          ->insert("migrate_map_d7_file")
          ->fields([
            "source_ids_hash" => hash("sha256", $row->getSource()["uri"] . $fid),
            "sourceid1" => $fid,
            "destid1" => $destid,
            "source_row_status" => 0,
            "rollback_action" => 0,
            "last_imported" => 0,
            "hash" => "",
          ])
          ->execute();
      }
      catch (\Exception $e) {
        if ($e->getCode() != 23000) {
          throw new MigrateException($e->getMessage(), $e->getCode());
        }
      }
      // Jump out and don't save the mapping entry (it's incorrect).
      throw new MigrateSkipRowException("", FALSE);
    }

     // If the file already exists on the destination, then skip.
    if (file_exists($destination) && $this->configuration["file_exists_ext"] == "skip") {
      $migrate_executable->saveMessage("Skip file $fileOps on (fid:" . $fid . ") '" . $source . "' - it already exists.", MigrationInterface::MESSAGE_INFORMATIONAL);
      return $destination;
    }

    // Now move the file.
    $this->downloadPlugin->configuration['guzzle_options']["read_timeout"] = 120000;
    $result = parent::transform($value, $migrate_executable, $row, $destination_property);

    return $result;

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
