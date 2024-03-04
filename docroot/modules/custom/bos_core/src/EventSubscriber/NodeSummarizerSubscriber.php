<?php

namespace Drupal\bos_core\EventSubscriber;

use Drupal;
use Drupal\bos_core\BosCoreEntityEventType;
use Drupal\bos_core\Controllers\Settings\CobSettings;
use Drupal\bos_google_cloud\Services\GcTextSummarizer;
use Drupal\Core\Cache\Cache;
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
    /**
     * @var \Drupal\node\Entity\Node $entity
     */
    $entity = $event->getEntity();

    if ($this->isSummarizeEligible($entity->bundle())) {
      // Only respond to entity|nodes of selected content types.
      if (empty($entity->body->summary)) {
        // Only calculate summary if no summary is present.

        $summarizer = Drupal::service("bos_google_cloud.GcTextSummarizer");
        $body = $entity->body->value;
        $summary = $this->getSummary($body, $summarizer);

        // This is pre-save so changing now will cause the change to ultimately
        // be saved in the database. (We don't need to force a save here).
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
    /**
     * @var \Drupal\node\Entity\Node $entity
     */
    $entity = $event->getEntity();

    if ($this->isSummarizeEligible($entity->bundle())) {
      // Only respond to entity|nodes of selected content types.
      if (empty($entity->body->summary)) {
        // Only recalculate summary if no summary is present.

        $summarizer = Drupal::service("bos_google_cloud.GcTextSummarizer");
        $body = $entity->body->value;
        $summary = $this->getSummary($body, $summarizer);

        // Save the summary for future load events.
        $this->saveSummary($event, $summary);

        // Return the summary for this load event.
        if (!$summarizer->error()) {
          $event->getEntity()->body->summary = $summary;
        }

      }

    }

    return $event;

  }

  /**
   * Helper to request summary from Google Cloud AI.
   *
   * @param string $text
   * @param GcTextSummarizer $summarizer
   *
   * @return string
   */
  private function getSummary(string $text, GcTextSummarizer $summarizer): string {
      // Get the summary
      return $summarizer->execute([
        "text" => $text,
        "prompt" => "10w",
        "cache" => [
          "enabled" => TRUE,
          "expiry" => Cache::PERMANENT,
        ],
      ]);
  }

  /**
   * Save the summary to the Database.
   *
   * @param EntityEvent $event
   * @param string $summary
   *
   * @return void
   */
  private function saveSummary(EntityEvent $event, string $summary):void {

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

  }

  /**
   * Return True/False if provided content_type is required to be summarized.
   * Eligibility set at /admin/config/system/boston
   *
   * @param string $content_type
   *
   * @return bool
   */
  private function isSummarizeEligible(string $content_type):bool {
    $content_types = CobSettings::getSettings("","bos_core","summarizer")["content_types"] ?? [];
    return in_array($content_type, $content_types);
  }

}
