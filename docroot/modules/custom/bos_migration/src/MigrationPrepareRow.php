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

  protected $row;
  protected $source;
  protected $migration;

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
  public function __construct(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {
    $this->row = $row;
    $this->source = $source;
    $this->migration = $migration;
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

    $workbench_all = self::findWorkbench($nid);
    if (empty($workbench_all)) {
      throw new MigrateSkipRowException("Workbench moderation not found.", FALSE);
    }
    $workbench_current = self::findWorkbenchCurrent($nid);
    if (empty($workbench_current)) {
      throw new MigrateSkipRowException("Workbench moderation current not found.", FALSE);
    }
    $workbench_published = self::findWorkbenchPublished($nid);

    try {
      $result = self::shouldProcessRow($nid, $vid, $workbench_all, []);
      $this->row->workbench = [
        "all" => $workbench_all,
        "current" => $workbench_current,
        "published" => $workbench_published ?: NULL,
      ];
      return $result;
    }
    catch (MigrateSkipRowException $e) {
      // Save to map or else this will be re-processed each migratin.
      throw new MigrateSkipRowException("", TRUE);
    }
    catch (\Exception $e) {
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
  public static function findWorkbench($nid) {
    $connection = Database::getConnection("default", "migrate");

    $query = $connection->select("workbench_moderation_node_history", "history")
      ->fields('history', [
        "nid",
        "vid",
        "from_state",
        "state",
        "published",
        "is_current",
      ]);
    $query->condition("nid", $nid);
    $or = $query->orConditionGroup()
      ->condition("state", "published")
      ->condition("is_current", 1)
      ->condition("published", 1);
    $query->condition($or);

    return $query->execute()->fetchAllAssoc("vid");
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
  public static function findWorkbenchCurrent($nid) {
    $connection = Database::getConnection("default", "migrate");

    $query = $connection->select("workbench_moderation_node_history", "history")
      ->fields('history', [
        "nid",
        "vid",
        "from_state",
        "state",
        "published",
        "is_current",
      ]);
    $query->condition("nid", $nid);
    $query->condition("published", 1);

    return $query->execute()->fetchAssoc();
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
  public static function findWorkbenchPublished($nid) {
    $connection = Database::getConnection("default", "migrate");

    $query = $connection->select("workbench_moderation_node_history", "history")
      ->fields('history', [
        "nid",
        "vid",
        "from_state",
        "state",
        "published",
        "is_current",
      ]);
    $query->condition("nid", $nid);
    $query->condition("published", 1);

    $result = $query->execute()->fetchAssoc();

    return $result ?: NULL;

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
  public static function shouldProcessRow(int $nid, int $vid, array $workbench = [], array $properties = []) {
    // Ensure we have the D7 workbench info for this node revision.
    if (empty($workbench)) {
      $workbench = self::findWorkbench($nid);
    }

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

  /**
   * Sets the correct status for a node revision.
   *
   * @param object $workbench
   *   StdClass with the moderation setting from D7.
   */
  public static function setNodeStatus($workbench) {
    // node_field_revision.
    \Drupal::database()->update("node_field_revision")
      ->fields([
        "status" => $workbench->published,
      ])
      ->condition('nid', $workbench->nid)
      ->condition('vid', $workbench->vid)
      ->condition('langcode', "und")
      ->execute();
  }

  /**
   * Sets the correct node revision.
   *
   * @param object $workbench
   *   StdClass with the moderation setting from D7.
   */
  public static function setCurrentRevision($workbench) {
    // Set the node vid to be the current revision.
    // Table: node.
    \Drupal::database()->update("node")
      ->fields([
        "vid" => $workbench->vid,
      ])
      ->condition('nid', $workbench->nid)
      ->execute();

    // Copy current rev of node_field_revision into node_field_data.
    // Table: node_field_data.
    $qstring = "UPDATE node_field_data dat
                      INNER JOIN node_field_revision rev ON dat.nid = rev.nid  
                      SET
                        dat.nid = rev.nid,
                        dat.vid = rev.vid,
                        dat.status = rev.status,
                        dat.title = rev.title,
                        dat.uid = rev.uid,
                        dat.created = rev.created,
                        dat.changed = rev.changed,
                        dat.promote = rev.promote,
                        dat.sticky = rev.sticky,
                        dat.default_langcode = rev.default_langcode,
                        dat.revision_translation_affected = rev.revision_translation_affected,
                        dat.published_at = rev.published_at,
                        dat.content_translation_source = rev.content_translation_source,
                        dat.content_translation_outdated = rev.content_translation_outdated
                        WHERE rev.langcode = 'und' 
                            AND rev.vid = " . $workbench->vid . ";";
    \Drupal::database()->query($qstring)->execute();
  }

  /**
   * Sets the correct moderation_state for a node revision.
   *
   * @param object $workbench
   *   StdClass with the moderation setting from D7.
   */
  public static function setModerationState($workbench) {
    \Drupal::database()->update("content_moderation_state_field_revision")
      ->fields([
        "uid" => $workbench->uid ?: 1,
        "moderation_state" => $workbench->state,
      ])
      ->condition('content_entity_revision_id', $workbench->vid)
      ->condition('langcode', "und")
      ->execute();
  }

  /**
   * Sets the correct moderation revision.
   *
   * @param object $workbench
   *   StdClass with the moderation setting from D7.
   */
  public static function setCurrentModerationRevision($workbench) {

    // Get the id and rev_id for the moderation state.
    $query = \Drupal::database()->select("content_moderation_state_field_revision", "rev")
      ->fields("rev", ["id", "revision_id"]);
    $query->condition('content_entity_id', $workbench->nid);
    $query->condition('content_entity_revision_id', $workbench->vid);

    if ($rev = $query->execute()->fetchAll()[0]) {
      \Drupal::database()->update("content_moderation_state")
        ->fields([
          "revision_id" => $rev->revision_id,
        ])
        ->condition('id', $rev->id)
        ->execute();

      // Table: content_moderation_state_field_data.
      $qstring = "UPDATE drupal.content_moderation_state_field_data dat
                    INNER JOIN content_moderation_state_field_revision rev ON dat.id = rev.id  
                    SET
                      dat.id = rev.id,
                      dat.revision_id = rev.revision_id,
                      dat.langcode = rev.langcode,
                      dat.uid = rev.uid,
                      dat.workflow = rev.workflow,
                      dat.moderation_state = rev.moderation_state,
                      dat.content_entity_type_id = rev.content_entity_type_id,
                      dat.content_entity_id = rev.content_entity_id,
                      dat.content_entity_revision_id = rev.content_entity_revision_id,
                      dat.default_langcode = rev.default_langcode,
                      dat.revision_translation_affected = rev.revision_translation_affected
                    WHERE rev.langcode = 'und' 
                          and rev.revision_id = " . $rev->revision_id . ";";
      \Drupal::database()->query($qstring)->execute();
    }
  }

}
