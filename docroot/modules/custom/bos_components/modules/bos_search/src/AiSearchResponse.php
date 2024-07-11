<?php

namespace Drupal\bos_search;

/**
 * class AiSearchResponse.
 * This object defines a search response from any AiSearch plugin.
 *
 * @see \Drupal\bos_search\AiSearchInterface
 * @see \Drupal\bos_search\Plugin\AiSearch\AiSearchPluginManager
 *
 * Example implementation:
 * @see \Drupal\bos_gc_aisearch_plugin\Plugin\AiSearch\GcVertexConversation
 */
use Drupal\bos_search\AiSearchResultCollection;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AiSearchResponse {

  /** @var \Drupal\bos_search\AiSearchRequest The most recent search */
  protected AiSearchRequest $search;

  /** @var string The output answer from the AI Model (summary) */
  protected string $ai_answer = "";

  /** @var string The output answer from the AI Model (full) */
  protected string $body = "";

  /** @var array Citations related to the body */
  protected array $citations = [];

  /** @var string The unique id for this conversation in the Model */
  protected string $conversation_id;

  /** @var array Any additional metadata */
  protected array $metadata = [];

  /** @var array a list of references or links */
  protected array $references = [];

  /** @var AiSearchResultCollection The array of results - AiSearchResult objects */
  protected AiSearchResultCollection $search_results;

  public function __construct(AiSearchRequest $search, string $ai_answer, string $conversation_id = "") {
    $this->ai_answer = $ai_answer;
    $this->conversation_id = $conversation_id;
    $this->search = $search;
    $this->search_results = new AiSearchResultCollection();
    $this->search_results->setMaxResults($search->get("result_count"));
  }

  /**
   * Set an object property.
   *
   * @param string $key
   * @param string|array|\Drupal\bos_search\AiSearchResultCollection|\Drupal\bos_search\AiSearchRequest $value
   *
   * @return $this
   */
  public function set(string $key, string|array|AiSearchResultCollection|AiSearchRequest $value): AiSearchResponse {
    $this->{$key} = $value;
    return $this;
  }

  /**
   *
   *
   * @param \Drupal\bos_search\AiSearchResult $result
   *
   * @return $this
   */
  public function addResult(AiSearchResult $result): AiSearchResponse {
    $this->search_results->addResult($result);
    return $this;
  }

  public function getAll(): array {
    return [
      "ai_answer" => $this->ai_answer,
      "conversation_id" => $this->conversation_id,
      "results" => $this->search_results->getResults()
    ];
  }

  public function getMetaData():array {
    return $this->metadata;
  }
  public function getResults():array {
    return $this->search_results->getResults();
  }
  public function getReferences():array {
    return $this->references;
  }
  public function getCitations():array {
    return $this->citations;
  }

  public function render(bool $citations = FALSE, bool $references = FALSE, bool $metadata = FALSE): string {
    $template = $this->search->get("result_template");
    $render_array = [
      '#theme' => $template,
      '#response' => $this->ai_answer,
      '#items' => $this->search_results->getResults(),
      '#content' => $this->search->get("search_text"),
      '#id' => $this->search->getId()
    ];

    // Only pass in additional information if asked to.
    if ($citations) {
      $render_array['#citations'] = $this->citations;
    }
    else {
      $render_array["#response"] = preg_replace('~\[[\d\s\,]*\]~', '', $this->ai_answer);
    }
    if ($references) {
      $render_array['#references'] = $this->references;
    }
    if ($metadata) {
      $render_array['#metadata'] = $this->metadata;
    }

    return \Drupal::service("renderer")->render($render_array);
  }

}
