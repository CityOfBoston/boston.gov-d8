<?php

namespace Drupal\bos_migration\EventSubscriber;

use Drupal\bos_migration\MigrationPrepareRow;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Revisions Migration save listener/subscriber.
 */
class EntityRevisionsSaveSubscriber implements EventSubscriberInterface {

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
    ];
  }

  /**
   * React to a entity_revision pre-save event.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   Event.
   */
  public function migrateRowPreSave(MigratePreRowSaveEvent $event) {
  }

  /**
   * React to a entity_revision post-save event.
   *
   * This function compares the moderation state copied during the migration to
   * the moderation state for this revision in d8 database.  It updates the d8
   * moderation state if it does not match the d7 value for this revision.
   *
   * This is necessary because the migration appears to set all moderation
   * states to "draft", possibly because the d7 workbench moderation creates
   * duplicate vids with different states and the migration only takes the
   * first when multiple moderated revsiions exist?
   *
   * We use SQL statements because this is post_save so the database is not
   * locked, and changing the entity object creates new revisions which we
   * do not want.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   Event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function migrateRowPostSave(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->get("migration_group") != "d7_node_revision"
      || NULL == $vid = $event->getDestinationIdValues()[0]) {
      return;
    }

    // Load the revision that has been imported.
    if (NULL != $revision_d8 = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadRevision($vid)) {

      $state_d8 = $revision_d8->get('moderation_state')->getString();
      $row = $event->getRow();

      // Establish the moderation states from D7.
      if (NULL == ($workbench_d7 = $row->workbench)) {
        try {
          $vid = $row->getSource()['vid'];
          $workbench_d7 = MigrationPrepareRow::findWorkbench($vid);
        }
        catch (Error $e) {
          $workbench_d7 = NULL;
        }
      }

      if (isset($workbench_d7)) {
        $nid = $revision_d8->id();

        if ($state_d8 != $workbench_d7['state']) {
          MigrationPrepareRow::setModerationState($vid, $workbench_d7['state']);

          $params = [
            "@id" => $nid,
            "@rev_id" => $vid,
            "@orig_rev_id" => $row->getSource()['vid'],
            "@state" => $workbench_d7['state'],
            "@old_state" => $state_d8,
            "@node_type" => $event->getMigration()
              ->getSourceConfiguration()['node_type'],
          ];
          $msg = \Drupal::translation()
            ->translate("@node_type:#@id set revision @rev_id (orig:@orig_rev_id) moderation from @old_state to @state.", $params);
          $event->logMessage($msg->render());
        }

        if ($workbench_d7['published'] == 1) {
          // This revision is the published one, so place in the node table
          // and set its status to 1.
          MigrationPrepareRow::setNodeStatus($vid, $workbench_d7['published']);

          $params = [
            "@id" => $revision_d8->id(),
            "@rev_id" => $vid,
            "@node_type" => $event->getMigration()
              ->getSourceConfiguration()['node_type'],
          ];
          $msg = \Drupal::translation()
            ->translate("@node_type:#@id set revision @rev_id status to TRUE.", $params);
          $event->logMessage($msg->render());
        }

        if ($workbench_d7['is_current'] == 1) {
          MigrationPrepareRow::setCurrentRevision($workbench_d7["nid"], $vid, $workbench_d7['published']);
          MigrationPrepareRow::setCurrentModerationRevision($workbench_d7["nid"], $vid, $workbench_d7['published']);
        }
        else {
          $current = MigrationPrepareRow::findWorkbenchCurrent($workbench_d7["nid"]);
          MigrationPrepareRow::setCurrentRevision($current["nid"], $current["vid"], $current['published']);
          MigrationPrepareRow::setCurrentModerationRevision($current["nid"], $current["vid"], $current['published']);
        }
        // Find the pulished node and make sure its published.
        if ($pubset = MigrationPrepareRow::findWorkbenchPublished($nid)) {
          MigrationPrepareRow::setModerationState($pubset["vid"], "published");
        }
      }

    }
  }

}
