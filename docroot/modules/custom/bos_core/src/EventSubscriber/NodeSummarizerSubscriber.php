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

  // TODO: Move the cache duration and the prompt into a configuration form
  const prompt = "10w";
  const cache_duration = Cache::PERMANENT;

  private bool $summarizer_presave = FALSE;

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

    if (!$this->summarizer_presave
      && $this->isSummarizeEligible($entity->bundle())) {

      // Only respond to entity|nodes of selected content types.

      // NOTE: The summary can be manually set/changed, and the summary will
      // be retained until the body is changed, at which time the summary will
      // be recalculated.

      $this->summarizer_presave = TRUE;         // To prevent re-running.
      $new_body = $entity->get('body')->value;
      $new_summary = $entity->get('body')->summary;

      if (empty($new_summary)) {
        // No existing summary, or the summary has been erased by the user.
        // Generate a new summary.
        $summarizer = Drupal::service("bos_google_cloud.GcTextSummarizer");
      }

      elseif (isset($entity->original)
        && $new_body != $entity->original->get('body')->value) {
        // The entity is being changed. Specifically, the body has been changed.
        // Invalidate any cached summary, and then generate a new summary.
        $summarizer = Drupal::service("bos_google_cloud.GcTextSummarizer");
        $original_body = $entity->original->get('body')->value;
        $original_summary = $entity->original->get('body')->summary ?? "";
        $ai_summary = $summarizer->getCachedSummary(self::prompt, $original_body);
        if ($ai_summary) {
          $summarizer->invalidateCachedSummary(self::prompt, $original_body);
        }
        // Check the summary is not being manually set by the user.
        if ($original_summary != $new_summary) {
          // Summary is being manually set or changed, don't replace.
          return $event;
        }
        elseif ($ai_summary && ($ai_summary != $new_summary)) {
          // The summary has been generated before, but the provided summary is
          // now different.
          // It must be being manually overwritten, do not replace it.
          return $event;
        }
      }

      if (isset($summarizer)) {

        // If $summarizer is set, need to generate a new summary.
        $result = $this->getSummary($new_body, $summarizer);

        // Check if there were problems, and if not set the summary on the entity
        // in the $event object - it will be saved when the entity is saved.
        if (!$summarizer->error()) {
          $event->getEntity()->body->summary = $result;
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
        // Only generate a summary if no summary is present.

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
        "prompt" => self::prompt,
        "cache" => [
          "enabled" => TRUE,
          "expiry" => self::cache_duration,
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
