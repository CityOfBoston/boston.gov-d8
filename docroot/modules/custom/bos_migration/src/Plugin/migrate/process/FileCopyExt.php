<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\bos_migration\FilesystemReorganizationTrait;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
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

  use FilesystemReorganizationTrait;

  protected $migrate_executable;
  protected $row;

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

    $this->migrate_executable = $migrate_executable;
    $this->row = $row;

    if ($row->isStub()) {
      return NULL;
    }

    list($source, $destination) = $value;
    $source = $source ?? $row->getSourceProperty('source_base_path');
    $fid = $row->getSource()['fid'];

    // If the migration is globally disabled, then skip file operations.
    if (\Drupal::state()->get("bos_migration.active", 0) == 0) {
      throw new MigrateSkipRowException("State bos_migration.active is missing or false.", FALSE);
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

    // Check for dups of this file in managed_files using different stategies.
    // First try to load by fid.
    $files = File::load($fid);
    if (!isset($files)) {
      // Check for dups of this file in managed_files by original name and size.
      $files = $this->getFilesByFilename($row->getSource()['filename'], $row->getSource()['filesize']);
      if (!isset($files)) {
        // Check for dups created with a (standard) modified filename from the
        // actual file name.  (Check filesize again to be sure e.g. thumbnails
        // may be created with same name as parent).
        $clean_filename = $this->cleanFilename($destination);
        $files = $this->getFilesByFilename( $clean_filename, $row->getSource()['filesize']);
      }
    }
    if (isset($files)) {
      // So there is a file with this name in file_managed.  We don't want
      // to create duplicates, so create a mapping entry and step over this row.
      // To be sure the duplicate is properly removed, we need to ensure the
      // image migrate fields use a lookup for the correct fid rather than
      // directly copying the fid from the d7 tables.
      $file_id = reset($files);
      if ($file_id != $fid) {
        $this->createMappingEntry($fid, $file_id, $source);
        // Skip any copying/moving of files for this row, and dont save a
        // mapping entry (we already made on and the default map would be
        // incorrect).
        throw new MigrateSkipRowException("", FALSE);
      }
    }
    // If the file already exists on the destination, then skip.
    if (file_exists($destination) && $this->configuration["file_exists_ext"] == "skip") {
      $fileOps = $this->fileOps();
      $migrate_executable->saveMessage("Skip file $fileOps on (fid:" . $fid . ") '" . $source . "' - it already exists.", MigrationInterface::MESSAGE_INFORMATIONAL);
      return $destination;
    }

    // Now move the file.
    $isDoc = strpos($source, ".pdf")
            || strpos($source, ".doc")
            || strpos($source, ".xl");
    if (!file_exists('/var/www/site-php')) {
      // Stops copy of docs: (!file_exists('/var/www/site-php') && $isDoc)
      $fileOps = $this->fileOps();
      $migrate_executable->saveMessage("Skip file $fileOps on (fid:" . $fid . ") '" . $source . "' - docs not copied.", MigrationInterface::MESSAGE_INFORMATIONAL);
      $result = $destination;
    }
    else {
      $this->downloadPlugin->configuration['guzzle_options']["read_timeout"] = 120000;
      $result = parent::transform($value, $migrate_executable, $row, $destination_property);
    }

    return $result;

  }

  /**
   * Creates an entry in the migrate_map_d7_file table for lookups.
   *
   * @param $sourceid
   *   The fid from the original D7 file.
   * @param $destid
   *   The existing fid in d8 for the file with this name/uri.
   * @param $source
   *   The file uri for reporting/output only.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function createMappingEntry($sourceid, $destid, $source) {
    try {
      $this->migrate_executable->saveMessage("Remapping fid:" . $sourceid . " => " . $destid . " (" . $source . ")", MigrationInterface::MESSAGE_INFORMATIONAL);
      $sourceIdValues = $this->row->getSourceIdValues();
      $fields["sourceid1"] = $sourceIdValues["fid"];
      $fields += [
        'source_row_status' => MigrateIdMapInterface::STATUS_IMPORTED,
        'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
        'hash' => $this->row->getHash(),
      ];
      $fields["destid1"] = $destid;
      $fields["last_imported"] = 0;
      $hash = hash('sha256', serialize(array_map('strval', [$sourceIdValues["fid"]])));
      $keys = ["source_ids_hash" => $hash];

      \DRUPAL::database()->delete("migrate_map_d7_file")
        ->condition("sourceid1", $fields["sourceid1"])
        ->condition("destid1", $destid)
        ->execute();

      \DRUPAL::database()->merge("migrate_map_d7_file")
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

  /**
   * Tries to move or copy a file.  Extended to do neither.
   *
   * Replaces the fileCopy->writeFile funcion.
   *
   * {@inheritdoc}
   */
  protected function writeFile($source, $destination, $replace = FileSystemInterface::EXISTS_REPLACE) {
    // Check if there is a destination available for copying. If there isn't,
    // it already exists at the destination and the replace flag tells us to not
    // replace it. In that case, return the original destination.
    if (!($final_destination = $this->fileSystem->getDestinationFilename($destination, $replace))) {
      return $destination;
    }
    if (!empty($this->configuration['move']) || !empty($this->configuration['copy'])) {
      $function = 'file_unmanaged_' . ($this->configuration['move'] ? 'move' : 'copy');
    }
    return $function($source, $destination, $replace);
  }

  /**
   * Returns the file operation based on the configuration provided.
   *
   * @return string
   *   Returns "copy" | "move" | "none".
   *
   */
  protected function fileOps() {
    if ($this->configuration["copy"]) {
      return "copy";
    }
    elseif ($this->configuration["move"]) {
      return "move";
    }
    else {
      return "none";
    }
  }

}
