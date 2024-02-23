<?php

namespace Drupal\node_rollcall\EventSubscriber;

use Drupal;
use Drupal\bos_core\BosCoreEntityEventType;
use Drupal\entity_events\Event\EntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * class GcNodeEventSubscriber (Event Subscriber)
 *
 * Listens to Node operations and updates summary as needed.
 *
 * david 02 2024
 * @file docroot/modules/custom/bos_components/modules/node_rollcall/src/EventSubscriber/NodeSummarizerSubscriber.php
 *
 */
class NodeSummarizerSubscriber implements EventSubscriberInterface {

  private const ALLOWED_CONTENT_TYPES = [
    'roll_call_dockets',
  ];

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    return [
      BosCoreEntityEventType::PRESAVE => 'setAiSummary'
    ];
  }

  /**
   * @param EntityEvent $event
   *
   * @return EntityEvent
   */
  public function setAiSummary(EntityEvent $event):EntityEvent {

    if (!in_array($event->getEntity()->bundle(), self::ALLOWED_CONTENT_TYPES)) {
      // Only respond to entity|nodes of selected content types.
      return $event;
    }

    $summarizer = Drupal::service("bos_google_cloud.GcTextSummarizer");
    $body = $event->getEntity()->body->value;
    $summary = $summarizer->execute(["text" => $body, "prompt" => "10w"]);
    $event->getEntity()->body->summary = $summary;
    return $event;
  }

}
