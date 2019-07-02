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
    if (empty($this->source->key())) {
      throw new MigrateSkipRowException("vid is null.", FALSE);
    }
    $key = unserialize($this->source->key());

    if (empty($key["vid"])) {
      throw new MigrateSkipRowException("vid is null.", FALSE);
    }
    $vid = $key["vid"];

    $workbench = self::findWorkbench($vid);
    if (empty($workbench)) {
      throw new MigrateSkipRowException("Workbench moderation not found.", FALSE);
    }
    $nid = $workbench["nid"];

    try {
      return self::shouldProcessRow($nid, $vid, $workbench, []);
    }
    catch (MigrateSkipRowException $e) {
      throw new MigrateSkipRowException($e->getMessage(), $e->getSaveToMap());
    }
    catch (\Exception $e) {
      throw new MigrateException($e->getMessage(), $e->getCode(), $e->getPrevious(), MigrationInterface::MESSAGE_ERROR, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
    }

  }

  /**
   * Fetch moderation info for all moderated revisions of a node revision.
   *
   * @param int $vid
   *   The node_revisionID (vid) to retrieve info on.
   *
   * @return array
   *   Associative array of moderation info for node.
   */
  public static function findWorkbench($vid) {
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
    $query->condition("vid", $vid);
    $query->orderBy("hid", "DESC");

    return $query->execute()->fetchAssoc();
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
    $query->condition("is_current", "1");

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
    $query->condition("published", "1");

    return $query->execute()->fetchAssoc();
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
    if (empty($workbench)) {
      $workbench = self::findWorkbench($vid);
    }

    $map = ((empty($properties["save_to_map"]) || $properties["save_to_map"] == "false") ? FALSE : TRUE);

    if ($workbench['state'] != "published" && !$workbench['is_current']) {
      $msg = $properties["message"] ?: NULL;
      if (!empty($msg)) {
        $msg = \Drupal::translation()->translate("@msg (nid:@nid / vid:@vid).", [
          "@msg" => $msg,
          "@nid" => $nid,
          "@vid" => $vid,
        ]);
      }
      throw new MigrateSkipRowException($msg, $map);
    }

    return TRUE;

  }

  /**
   * Sets the correct status for a node revision.
   *
   * @param int $vid
   *   The node revsion id to be set.
   * @param int $status
   *   The drupal-status (1 or 0)
   */
  public static function setNodeStatus(int $vid, int $status) {
    // node_field_revision.
    \Drupal::database()->update("node_field_data")
      ->fields([
        "status" => $status,
      ])
      ->condition('vid', $vid)
      ->execute();

    // node_field_revision.
    \Drupal::database()->update("node_field_revision")
      ->fields([
        "status" => $status,
      ])
      ->condition('vid', $vid)
      ->execute();
  }

  /**
   * Sets the correct moderation_state for a node revision.
   *
   * @param int $vid
   *   The node revsion id to be set.
   * @param string $state
   *   The content_moderation status (published/draft/need_review)
   */
  public static function setModerationState($vid, $state) {
    \Drupal::database()->update("content_moderation_state_field_data")
      ->fields([
        "moderation_state" => $state,
      ])
      ->condition('content_entity_revision_id', $vid)
      ->execute();

    \Drupal::database()->update("content_moderation_state_field_revision")
      ->fields([
        "moderation_state" => $state,
      ])
      ->condition('content_entity_revision_id', $vid)
      ->execute();
  }

  /**
   * Sets the correct node revision.
   *
   * @param int $nid
   *   Node to be set.
   * @param int $vid
   *   Node revision to be set.
   * @param bool $isPublished
   *   Is this revision's status = 1 (drupal-published)
   */
  public static function setCurrentRevision(int $nid, int $vid, bool $isPublished) {
    // Set the node vid to be the current revision.
    // Table: node.
    \Drupal::database()->update("node")
      ->fields([
        "vid" => $vid,
      ])
      ->condition('nid', $nid)
      ->execute();

    // Copy current rev of node_field_revision into node_field_data.
    // Table: node_field_data.
    $qstring = "UPDATE node_field_data dat
                      INNER JOIN node_field_revision rev ON dat.nid = rev.nid  
                      SET
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
                        WHERE rev.nid = " . $nid . " 
                            AND rev.langcode = 'und' 
                            AND rev.vid = " . $vid . ";";
    \Drupal::database()->query($qstring)->execute();

    // Only set the revision default to be true if this revision is pub.
    // Table: node_revision.
    \Drupal::database()->update("node_revision")
      ->fields([
        "revision_default" => $isPublished,
      ])
      ->condition('vid', $vid)
      ->execute();
  }

  /**
   * Sets the correct moderation revision.
   *
   * @param int $nid
   *   Node to be set.
   * @param int $vid
   *   Node revision to be set.
   * @param bool $isPublished
   *   Is this revision's status = 1 (drupal-published)
   */
  public static function setCurrentModerationRevision(int $nid, int $vid, bool $isPublished) {
    // Get the id and rev_id for the moderation state.
    $query = \Drupal::database()->select("content_moderation_state_field_revision", "rev")
      ->fields("rev", ["id", "revision_id"]);
    $query->condition('content_entity_id', $nid);
    $query->condition('content_entity_revision_id', $vid);

    if ($rev = $query->execute()->fetchAll()[0]) {

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
                      WHERE rev.id = " . $rev->id . " 
                            AND rev.langcode = 'und' 
                            and rev.revision_id = " . $rev->revision_id . ";";
      \Drupal::database()->query($qstring)->execute();

      $qstring = "UPDATE drupal.content_moderation_state dat
                      INNER JOIN content_moderation_state_field_revision rev ON dat.id = rev.id AND dat.revision_id = rev.revision_id 
                      SET
                        dat.id = rev.id,
                        dat.revision_id = rev.revision_id,
                        dat.langcode = rev.langcode
                      WHERE rev.id = " . $rev->id . "
                            AND rev.langcode = 'und'
                            and rev.revision_id = " . $rev->revision_id . ";";
      \Drupal::database()->query($qstring)->execute();

      // Table: node_revision.
      \Drupal::database()->update("content_moderation_state_revision")
        ->fields([
          "revision_default" => $isPublished,
        ])
        ->condition('id', $rev->id)
        ->condition('revision_id', $rev->revision_id)
        ->execute();
    }

  }

}
