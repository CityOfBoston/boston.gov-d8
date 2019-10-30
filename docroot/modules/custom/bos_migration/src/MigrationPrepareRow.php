<?php

namespace Drupal\bos_migration;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Core\Database\Database;

/**
 * Class MigrationPrepareRow.
 *
 * General purpose class for managing node_revision processes.
 *
 * @package Drupal\bos_migration
 */
class MigrationPrepareRow {

  use migrationModerationStateTrait;

  protected $row;
  protected $source;
  protected $migration;
  protected $use_cache;
  protected $cache = NULL;

  /**
   * MigrationProcessRow constructor.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row being processed.
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   The source object.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration object.
   */
  public function __construct(Row $row, MigrateSourceInterface $source, MigrationInterface $migration, $use_cache = FALSE) {
    $this->row = $row;
    $this->source = $source;
    $this->migration = $migration;
    $this->use_cache = $use_cache;
  }

  /**
   * Returns the current cache.
   *
   * @param string $wb_element
   *   Cache element to fetch.
   *
   * @return array
   *   The cache.
   */
  public function getCache(string $wb_element = NULL) {
    if (!$this->use_cache) {
      return NULL;
    }
    elseif (NULL == $wb_element) {
      return $this->cache;
    }
    elseif (isset($this->cache[$wb_element])) {
      return $this->cache[$wb_element];
    }
    else {
      return NULL;
    }
  }

  /**
   * Set a cache array element.
   *
   * @param string $wb_element
   *  The element to set.
   * @param mixed $value
   *  The value to set (usually an array).
   *
   * @return mixed
   */
  private function setCache(string $wb_element, $value) {
    if ($this->use_cache) {
      $this->cache[$wb_element] = $value;
      return $this->cache[$wb_element];
    }
    return NULL;
  }

  private function loadCache($nid) {
    if ($this->use_cache) {
      $this->cache = $GLOBALS["workbench_cache"][$nid] ?: [];
    }
    else {
      unset($GLOBALS["workbench_cache"][$nid]);
    }
  }

  private function saveCache($nid) {
    if ($this->use_cache) {
      $GLOBALS["workbench_cache"][$nid] = $this->cache ?: [];
    }
    else {
      unset($GLOBALS["workbench_cache"][$nid]);
    }
  }

  /**
   * Raises a skipMigration event if d7 revision is a draft.
   *
   * Called from bos_migration.module.
   *
   * @return bool
   *   True to continue processing.
   *
   * @throws \Drupal\migrate\MigrateException
   *   If error found.
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   Thrown if row is to be skipped.
   */
  public function prepareNodeRevisionRow() {
    // Doing this here, misses out a couple of later steps rather than using
    // the SkipDraftRevision plugin.
    $vid = $this->row->getSource()["vid"];
    $nid = $this->row->getSource()["nid"];
    if (empty($nid)) {
      throw new MigrateSkipRowException("ERROR: nid is null.", FALSE);
    }
    if (empty($vid)) {
      throw new MigrateSkipRowException("ERROR: vid is null.", FALSE);
    }

    $this->loadCache($nid);
    $this->findWorkbench($nid);
    $this->findWorkbenchCurrent($nid);
    // $this->findWorkbenchPublished($nid);
    try {
      $result = $this->shouldProcessRow($nid, $vid, []);
      $this->row->workbench = $this->getCache();
      $this->saveCache($nid);
      return $result;
    }
    catch (MigrateSkipRowException $e) {
      // Save to map or else this will be re-processed each migratin.
      $this->saveCache($nid);
      throw new MigrateSkipRowException($e->getMessage(), TRUE);
    }
    catch (\Exception $e) {
      $this->saveCache($nid);
      throw new MigrateException($e->getMessage(), $e->getCode(), $e->getPrevious(), MigrationInterface::MESSAGE_ERROR, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
    }

  }

  /**
   * Fetch moderation info for all moderated revisions of a node.
   *
   * Only return interesting rows which have a moderation state of published,
   * where the revision is the current revision, or where the status of the
   * revision is 1 (drupal-published).
   *
   * @param int $nid
   *   The nodeID (nid) to retrieve info on.
   *
   * @return array
   *   Associative array of moderation info for node.
   */
  private function findWorkbench($nid) {
    // Check cache first.
    if (empty($this->cache[$nid])) {
      $this->loadCache($nid);
    }
    if (NULL == $this->getCache("all")) {
      $mod = migrationModerationStateTrait::getModerationAll($nid);
      $this->setCache("all", $mod);
    }
    return $this->getCache("all");
  }

  /**
   * Fetch the current revision of the node.
   *
   * @param int $nid
   *   The nodeID to retrieve info on.
   *
   * @return array
   *   Associative array of moderation info for node.
   */
  private function findWorkbenchCurrent($nid) {
    $cache = $this->getCache("current");
    if (NULL != $cache) {
      return $cache;
    }
    $cache = $this->getCache("all");
    if (NULL != $cache) {
      foreach ($cache as $vid) {
        if ($vid->is_current == "1") {
          $this->setCache("current", $vid);
          return $this->getCache("current");
        }
      }
    }

    $mod = migrationModerationStateTrait::getModerationPublished($nid);

    $this->setCache("current", (object) $mod);
    return $this->getCache("current");
  }

  /**
   * Fetch the published revision of the node.
   *
   * @param int $nid
   *   The nodeID to retrieve info on.
   *
   * @return array
   *   Associative array of moderation info for node.
   */
  private function findWorkbenchPublished($nid) {
    $cache = $this->getCache("published") ?: NULL;
    if (NULL != $cache) {
      return $cache;
    }
    $cache = $this->getCache("all");
    if (NULL != $cache) {
      foreach ($cache as $vid) {
        if ($vid->published == "1") {
          $this->setCache("published", $vid);
        }
      }
      return $this->getCache("published");
    }

    $mod = migrationModerationStateTrait::getModerationPublishedHistory($nid);

    $this->setCache("published", (object) $mod);
    return $this->getCache("published");
  }

  /**
   * Tests to see if the row should be skipped.
   *
   * The row should be skipped if the d7 moderation state is draft, and the
   * revision is not published and is not the current revision.
   *
   * @param int $nid
   *   The nid for this node.
   * @param int $vid
   *   The revision of the node to be "tested".
   * @param array $workbench
   *   Moderation info for each moderation-revision of this node-revision.
   * @param array $properties
   *   Control info for logging and process-mapping.
   *
   * @return bool
   *   True to process row.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   Thrown if the revisions latest moderation state is "draft".
   */
  private function shouldProcessRow(int $nid, int $vid, array $properties = []) {
    // Ensure we have the D7 workbench info for this node revision.
    $workbench = $this->getCache("all");

    // If the D7 revision has anything interesting about it, then include -
    // otherwise skip it.
    if (isset($workbench[$vid])) {
      return TRUE;
    }

    // If message is empty or null, then the skip will not be written to the
    // migrate_message table.
    $msg = $properties["message"] ?: NULL;
    if (!empty($msg)) {
      $msg = \Drupal::translation()->translate("@msg (nid:@nid / vid:@vid).", [
        "@msg" => $msg,
        "@nid" => $nid,
        "@vid" => $vid,
      ]);
    }
    // If save_to_map is missing or false, then any skipped record will not be
    // written to the migrate_map table.  Note this means the record will be
    // rescanned next time a migration is run.
    $map = ((empty($properties["save_to_map"]) || $properties["save_to_map"] == "false") ? FALSE : TRUE);

    // Tell the processor to skip processing of this entire row (revision).
    throw new MigrateSkipRowException($msg, $map);
  }

}
