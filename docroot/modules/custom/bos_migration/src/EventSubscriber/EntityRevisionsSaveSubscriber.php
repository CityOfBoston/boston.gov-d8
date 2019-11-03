<?php

namespace Drupal\bos_migration\EventSubscriber;

use Drupal\bos_migration\MemoryManagementTrait;
use Drupal\bos_migration\FilesystemReorganizationTrait;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Revisions Migration save listener/subscriber.
 */
class EntityRevisionsSaveSubscriber implements EventSubscriberInterface {

  use MemoryManagementTrait;
  use FilesystemReorganizationTrait;

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
    // Try to manage memory ...
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
      // Try to manage memory ...
      $this->checkStatus();
    }
    elseif ($event->getMigration()->getBaseId() == "d7_file") {
      // If there is a duplicate, then skip.
      $isDuplicate = FALSE;
      if ($isDuplicate) {
        throw new MigrateException("File entry already exists.", 0, NULL, MigrationInterface::MESSAGE_NOTICE, MigrateIdMapInterface::STATUS_IGNORED);
      }
      // Cleanup the filename and ensure its written to the file object and
      // to files_managed.
      $row = $event->getRow();
      $filename = $row->getDestinationProperty("filename");
      $filename = FilesystemReorganizationTrait::cleanFilename($filename);
      $row->setDestinationProperty("filename", $filename);
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
    $row = $event->getRow();

    if ($event->getMigration()->getBaseId() == "d7_node") {
      if (NULL == ($vid = $row->getDestinationProperty("vid"))) {
        return;
      }
      // Fetch the content moderation which will have been created as this node
      // was saved (if there is an associated workflow).
      $cmid = \Drupal::entityQuery("content_moderation_state")
        ->condition("content_entity_revision_id", $vid)
        ->execute();
      if (isset($cmid)) {
        $cmid = reset($cmid);
        if (!empty($cmid)) {
          // Will access the DB directly to avoid new revisions creeping in.
          // Now set the moderation state correctly.
          \Drupal::database()
            ->update("content_moderation_state_field_data")
            ->fields([
              "moderation_state" => $row->getSourceProperty("wb_state"),
              "uid" => $row->getSourceProperty("wb_uid"),
            ])
            ->condition("id", $cmid)
            ->execute();
          \Drupal::database()
            ->update("content_moderation_state_field_revision")
            ->fields([
              "moderation_state" => $row->getSourceProperty("wb_state"),
              "uid" => $row->getSourceProperty("wb_uid"),
            ])
            ->condition("id", $cmid)
            ->execute();
          // Now update the node.
          \Drupal::database()
            ->update("node_field_data")
            ->fields([
              "status" => $row->getSourceProperty("wb_published"),
            ])
            ->condition("vid", $vid)
            ->execute();
          \Drupal::database()
            ->update("node_revision")
            ->fields([
              "revision_default" => 1,
            ])
            ->condition("vid", $vid)
            ->execute();
        }
      }
    }

    elseif ($event->getMigration()->getBaseId() == "d7_node_revision") {
      if (NULL == ($vid = $row->getDestinationProperty("vid"))) {
        return;
      }
      // Don't actually need to do anything.
    }

    elseif ($event->getMigration()->getBaseId() == "d7_file") {
      // Check if we need to create a media entity.

      // Rename the incoming filenames using cleanfilename.
      $filename = $row->getDestinationProperty("filename");
      $filename = FilesystemReorganizationTrait::cleanFilename($filename);
      \Drupal::database()->update("file_managed")
        ->fields(["filename" => $filename])
        ->condition("fid", $row->getDestinationProperty("fid"))
        ->execute();
    }

  }

}
