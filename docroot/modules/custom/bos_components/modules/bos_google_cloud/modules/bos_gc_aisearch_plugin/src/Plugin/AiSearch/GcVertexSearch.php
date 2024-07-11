<?php

namespace Drupal\bos_gc_aisearch_plugin\Plugin\AiSearch;

use Drupal\bos_google_cloud\Services\GcSearch;
use Drupal\bos_search\AiSearchBase;
use Drupal\bos_search\AiSearchInterface;
use Drupal\bos_search\AiSearchRequest;
use Drupal\bos_search\AiSearchResponse;
use Drupal\bos_search\Annotation\AiSearchAnnotation;
use Drupal\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'AiSearch' plugin for bos_google_cloud.
 *
 * @AiSearchAnnotation (
 *   id = "Vertex Search",
 *   service = "bos_google_cloud.GcSearch",
 *   description = "Plugin for Google Cloud Vertex Search Service."
 * )
 */
class GcVertexSearch extends AiSearchBase implements AiSearchInterface {

  /** @var \Drupal\bos_google_cloud\Services\GcSearch Holds the injected Vertex service. */
  protected GcSearch $vertex;

  /** @injectDoc */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Inject the GcSearch service.
    $this->vertex = \Drupal::getContainer()->get("bos_google_cloud.GcSearch");
  }

  /**
   * @inheritDoc
   */
  public function search(AiSearchRequest $request): AiSearchResponse {
    return new AiSearchResponse();
  }

  /**
   * @inheritDoc
   */
  public function hasConversation(): bool {
    // Search does not support conversations.
    //  Use GcVertexConversation for conversation type interactions.
    return FALSE;
  }

}
