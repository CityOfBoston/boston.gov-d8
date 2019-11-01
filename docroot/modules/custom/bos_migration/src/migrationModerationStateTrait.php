<?php

namespace Drupal\bos_migration;

use Drupal\Core\Database\Database;

/**
 * Trait migrationModerationStateTrait.
 *
 * @package Drupal\bos_migration
 */
trait migrationModerationStateTrait {

  /**
   * Fetches all published and current revisions of this node from D7 DB.
   *
   * @param int $nid
   *   The node id.
   * @param int|null $limit
   *   If provided will select the latest $limit records from the table.
   *
   * @return mixed
   *   An array indexed by vid.
   */
  public static function getModerationAll(int $nid, int $limit = NULL) {
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
    // If we are trimming, then just get last $limit records.
    if (isset($limit)) {
      $query->range(0, $limit);
      $query->orderBy("stamp", "DESC");
    }
    return $query->execute()->fetchAllAssoc("vid");
  }

  /**
   * Fetches all published revisions of this node from D7 Database source.
   *
   * @param int $nid
   *   The node id.
   *
   * @return mixed
   *   An array indexed by vid.
   */
  public static function getModerationPublished(int $nid) {
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

    return (array) $query->execute()->fetchAssoc();
  }

  /**
   * Fetches all the published revisions of this node from D7 database source.
   *
   * @param int $nid
   *   The node id to get history for.
   *
   * @return array
   *   An assoc array with the moderation history indexed by vid.
   */
  public static function getModerationPublishedHistory(int $nid) {
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
    return (array) $query->execute()->fetchAssoc();
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
                        dat.published_at = rev.published_at
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
      $qstring = "UPDATE content_moderation_state_field_data dat
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
