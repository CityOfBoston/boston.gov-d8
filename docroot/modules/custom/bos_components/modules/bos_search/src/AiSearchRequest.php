<?php

namespace Drupal\bos_search;

/**
 * class AiSearchRequest.
 * This object defines a search which can be interpreted by any AiSearch plugin.
 *
 * @see \Drupal\bos_search\AiSearchInterface
 * @see \Drupal\bos_search\Plugin\AiSearch\AiSearchPluginManager
 *
 * Example implementation:
 * @see \Drupal\bos_gc_aisearch_plugin\Plugin\AiSearch\GcVertexConversation
 */
class AiSearchRequest {

  /** @var array An array of AiSearchResult objects. */
  protected array $history;

  /** @var string The search question. */
  protected string $search_text;

  /** @var string The unique ID for this conversation. */
  protected string $conversation_id = "";

  protected int $result_count = 0;
  protected string $result_template = "";

  public function __construct(string $search_text = "", int $result_count = 0, string $result_template = "") {
    if (!empty($search_text)) {
      $search_text = AiSearch::sanitize($search_text);
      $this->search_text = trim($search_text);
      $this->result_count = $result_count;
      $this->result_template = $result_template;
    }
  }

  public function addHistory(AiSearchResponse $search): AiSearchRequest {
    $this->history[] = $search;
    return $this;
  }
  public function getHistory(): array {
    return $this->history;
  }

  public function get(string $key): array|string|int {
    return $this->{$key};
  }
  public function set(string $key, array|string|int $value): AiSearchRequest {
    $this->{$key} = $value;
    return $this;
  }

  public function getId(): string {
    return $this->conversation_id;
  }

}
