<?php

namespace Drupal\bos_search\Model;

/**
 * class AiSearchCitationCollection.
 * This object defines a collection of citations from any AiSearch plugin.
 *
 * @see \Drupal\bos_search\Model\AiSearchCitation
 * @see \Drupal\bos_search\Plugin\AiSearch\AiSearchPluginManager
 *
 * Example implementation:
 * @see \Drupal\bos_gc_aisearch_plugin\Plugin\AiSearch\GcVertexConversation
 */

class AiSearchCitationCollection extends AiSearchObjectsBase {

  /** @var array Array of AiSearchCitation objects */
  protected array $citations;

  /**
   * Add a citation to the collection.
   *
   * @param \Drupal\bos_search\AiSearchResult $result
   *
   * @return $this
   */
  public function addCitation(AiSearchCitation $citation, int $key = NULL): AiSearchCitationCollection {
    if (empty($key)) {
      $key = count($this->citations ?? []);
    }
    $this->citations[$key] = $citation->getCitation();
    return $this;
  }

  public function updateCitation($key, array $citation):AiSearchCitationCollection {
    $this->citations[$key] = $citation;
    return $this;
  }
  /**
   * Get all results as an array of AISearchCitation objects.
   *
   * @return array
   */
  public function getCitations(): array {
    return $this->citations;
  }

  /**
   * Returns the number of AISearchCitation objects in the collection.
   * @return int
   */
  public function count(): int {
    return count($this->citations);
  }

}
