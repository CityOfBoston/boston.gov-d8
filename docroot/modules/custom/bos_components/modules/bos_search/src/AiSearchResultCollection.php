<?php

namespace Drupal\bos_search;

/**
 * class AiSearchResultCollection.
 * This object defines a collection of search results from any AiSearch plugin.
 *
 * @see \Drupal\bos_search\AiSearchResult
 * @see \Drupal\bos_search\AiSearchResponse
 * @see \Drupal\bos_search\Plugin\AiSearch\AiSearchPluginManager
 *
 * Example implementation:
 * @see \Drupal\bos_gc_aisearch_plugin\Plugin\AiSearch\GcVertexConversation
 */
class AiSearchResultCollection {

  /** @var array Array of AiSearchResult objects */
  protected array $results;

  public function __construct() {
    $this->results = [];
  }

  public function addResult(AiSearchResult $result): AiSearchResultCollection {
    $this->results[] = $result->getResult();
    return $this;
  }

  public function getResults(): array {
    return $this->results;
  }

}
