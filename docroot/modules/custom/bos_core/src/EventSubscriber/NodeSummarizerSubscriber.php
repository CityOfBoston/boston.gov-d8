<?php

namespace Drupal\bos_core\EventSubscriber;

use Drupal;
use Drupal\bos_core\BosCoreEntityEventType;
use Drupal\bos_core\Controllers\Settings\CobSettings;
use Drupal\entity_events\Event\EntityEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * class GcNodeEventSubscriber (Event Subscriber)
 *
 * Listens to Node operations and updates summary as needed.
 *
 * david 02 2024
 * @file docroot/modules/custom/bos_components/modules/bos_core/src/EventSubscriber/NodeSummarizerSubscriber.php
 *
 */
class NodeSummarizerSubscriber implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      BosCoreEntityEventType::LOAD => 'AiLoad',
      BosCoreEntityEventType::PRESAVE => 'AiPresave',
    ];
  }

  /**
   * @param EntityEvent $event
   *
   * @return EntityEvent
   */
  public function AiPresave(EntityEvent $event):EntityEvent {

    if (in_array($event->getEntity()->bundle(), self::allowedContentTypes())) {
      // Only respond to entity|nodes of selected content types.
      if (empty($event->getEntity()->body->summary)) {
        $summarizer = Drupal::service("bos_google_cloud.GcTextSummarizer");
        $body = $event->getEntity()->body->value;
        $summary = $summarizer->execute(["text" => $body, "prompt" => "10w"]);
        // This is pre-save so changing now will cause the change to ultimately be
        // saved in the database.
        if (!$summarizer->error()) {
          $event->getEntity()->body->summary = $summary;
        }
      }
    }
    return $event;

  }

  /**
   * @param EntityEvent $event
   *
   * @return EntityEvent
   */
  public function AiLoad(EntityEvent $event): EntityEvent {

    if (in_array($event->getEntity()->bundle(), self::allowedContentTypes())) {
      // Only respond to entity|nodes of selected content types.
      if (empty($event->getEntity()->body->summary)) {
        // Only recalculate summary if no summary is present.
        $summarizer = Drupal::service("bos_google_cloud.GcTextSummarizer");
        $body = $event->getEntity()->body->value;
        $summary = $summarizer->execute(["text" => $body, "prompt" => "10w"]);
        // Save the summary for future load events.
        $entity = $event->getEntity()->toArray();
        try {
          // Have to do this directly against the DB or else will call iterative
          // load/save events....
          Drupal::database()
            ->update("node__body")
            ->fields(["body_summary" => $summary])
            ->condition("entity_id", $entity["nid"][0]["value"])
            ->execute();
          Drupal::database()
            ->update("node_revision__body")
            ->fields(["body_summary" => $summary])
            ->condition("entity_id", $entity["nid"][0]["value"])
            ->condition("revision_id", $entity["vid"][0]["value"])
            ->execute();
        }
        catch (Exception) {
          // do nothing.
        }
        // Return the summary for this load event.
        $event->getEntity()->body->summary = $summary;
      }

    }

    return $event;

  }

  private static function allowedContentTypes() {
    return CobSettings::getSettings("","bos_core","summarizer")["content_types"] ?? [];
  }

}
