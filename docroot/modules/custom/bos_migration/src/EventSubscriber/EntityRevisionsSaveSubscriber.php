<?php

namespace Drupal\bos_migration\EventSubscriber;

use Drupal\Core\Database\Database;
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
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   Event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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

      // Establish the moderation states from D7.
      try {
        $connection = Database::getConnection("default", "migrate");
        $query = $connection->select("workbench_moderation_node_history", "history")
          ->fields('history', [
            "from_state",
            "state",
            "published",
            "is_current",
          ]);
        $query->condition("vid", $event->getRow()->getSource()['vid']);
        $workbench_d7 = $query->execute()->fetchAssoc();
      }
      catch (Error $e) {
        $workbench_d7 = NULL;
      }
      // Compare D7 & D8 moderation states and change if necessary.
      if (isset($workbench_d7) && $state_d8 != $workbench_d7['state']) {
        $revision_d8->get('moderation_state')
          ->setvalue($workbench_d7['state']);
        $revision_d8->save();
        $params = [
          "@id" => $revision_d8->id(),
          "@rev_id" => $vid,
          "@orig_rev_id" => $event->getRow()->getSource()['vid'],
          "@state" => $workbench_d7['state'],
          "@node_type" => $event->getMigration()->getSourceConfiguration()['node_type'],
        ];
        $msg = \Drupal::translation()->translate("@node_type:#@id set revision @rev_id (orig:@orig_rev_id) to @state.", $params);
        $event->logMessage($msg->render());
      }
    }
  }

}
