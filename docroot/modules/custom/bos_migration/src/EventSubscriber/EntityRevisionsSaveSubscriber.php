<?php

namespace Drupal\bos_migration\EventSubscriber;

use Drupal\bos_migration\migrationModerationStateTrait;
use Drupal\bos_migration\MemoryManagementTrait;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Revisions Migration save listener/subscriber.
 */
class EntityRevisionsSaveSubscriber implements EventSubscriberInterface {

  use migrationModerationStateTrait;
  use MemoryManagementTrait;

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PRE_ROW_SAVE => 'migrateRowPreSave',
      MigrateEvents::POST_ROW_SAVE => 'migrateRowPostSave',
      MigrateEvents::POST_IMPORT => 'migratePostImport',
    ];
  }

  /**
   * Reacts to import event.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   Event.
   */
  public function migratePostImport(MigrateImportEvent $event) {
    if (in_array($event->getMigration()->getBaseId(), [
      "d7_paragraph",
      "d7_node",
      "d7_node_revision",
    ])) {
      $this->checkStatus();
    }
  }

  /**
   * React to a entity_revision pre-save event.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   Event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\migrate\MigrateException
   */
  public function migrateRowPreSave(MigratePreRowSaveEvent $event) {
    // If this is an entity revision, then check if the revision exists.
    if ($event->getMigration()->getBaseId() == "d7_node_revision") {
      $row = $event->getRow();
      $migrate_vid = $row->getDestination()["vid"];
      $id_map = $event->getMigration()->getIdMap();
      $destination_ids = $id_map->lookupDestinationIds(["vid" => $migrate_vid]);
      if (!empty($destination_ids)) {
        // There is already a map for this revision.  This possibly means a
        // previous import updated another node and gave it this vid or else
        // an import failed leaving a map to null.
        // Assuming this is null, fetch and save the node with a new revision
        // and then save this revision with that ID.
        $destination_ids = reset($destination_ids);
        if (NULL == $destination_ids[0]) {
          // See if this revision actually exists.
          $revision = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadRevision($migrate_vid);
          if (empty($revision)) {
            // It does, so ....
            // Create a new revision.
            $nid = $row->getSource()["nid"];
            $node = Node::load($nid);
            $node->setNewRevision(TRUE);
            $node->save();
            $destination_ids[0] = $node->getRevisionId();
          }
          else {
            // It does not ....
            // Set the destinationid to be the revsision id.
            $destination_ids[0] = $migrate_vid;
          }
          $id_map->saveIdMapping($row, $destination_ids, $id_map::STATUS_IMPORTED, $id_map::ROLLBACK_DELETE);
          // Update everything.
          $event->getRow()
            ->setDestinationProperty("vid", $destination_ids[0]);
          $idmap = $row->getIdMap();
          $idmap["destid1"] = $destination_ids[0];
          $event->getRow()->setIdMap($idmap);
        }
      }
    }
  }

  /**
   * React to a entity_revision migration post-save event.
   *
   * This function compares the moderation state copied during the migration to
   * the moderation state for this revision in d8 database.  It updates the d8
   * moderation state if it does not match the d7 value for this revision.
   *
   * This is necessary because the migration appears to set all moderation
   * states to "draft", possibly because the d7 workbench moderation creates
   * duplicate vids with different states and the migration only takes the
   * first when multiple moderated revsiions exist.
   *
   * We use SQL statements rather than creating and updating the entity object
   * because [a] we can, this is post_save so the database is not locked, and
   * [b] changing the entity object creates new revisions which we do not want
   * to do.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   Event.
   */
  public function migrateRowPostSave(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->getBaseId() != "d7_node_revision"
      || NULL == $vid = $event->getRow()->getSourceIdValues()["vid"]) {
      return;
    }

    $row = $event->getRow();
    $workbench = $row->workbench;
    // Establish the moderation states from D7.
    if (NULL == $workbench) {
      $workbench_all = NULL;
    }

    // Get the d7 workbench moderation info for this revision.
    if (isset($workbench) && isset($workbench["all"][$vid])) {
      if (empty($workbench["all"][$vid]->published) || !is_numeric($workbench["all"][$vid]->published)) {
        $workbench["all"][$vid]->published = "0";
      }

      // Set the status for this revision and the current revision.
      if ($vid == end($workbench["all"])->vid) {
        self::setNodeStatus($workbench["all"][$vid]);
      }
      if ($vid == $workbench["current"]->vid) {
        self::setNodeStatus($workbench["current"]);
      }

      // Sets the node back to the correct current revision.
      if ($vid == $workbench["current"]->vid) {
        self::setCurrentRevision($workbench["current"]);
      }

      // The `d7_node:xxx` migration will have imported the latest node.
      //
      // The d7 workbench_moderation maintains its own versioning
      // allowing a node_revision to have multiple moderation_states over
      // time - whereas d8 content moderation links its status with the
      // node_revision, making a new revision when the moderation state
      // changes.
      // The effect of this is that for any node revision, only the first
      // workbench_moderation state is migrated, and this state is usually
      // "draft".
      // So, the revision ond node need their moderation state to be updated.
      // Set the status for this revision and the current revision.
      // Sets the moderation state for this revision and the current revision.
      if ($vid == end($workbench["all"])->vid) {
        self::setModerationState($workbench["all"][$vid]);
      }
      if ($vid == $workbench["current"]->vid) {
        self::setModerationState($workbench["current"]);
      }

      // Set the moderation_state revision back to current revision.
      if ($vid == $workbench["current"]->vid) {
        self::setCurrentModerationRevision($workbench["current"]);
      }
    }
  }

}
