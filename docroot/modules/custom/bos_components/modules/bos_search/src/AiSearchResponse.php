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

class AiSearchResponse {

  /** @var \Drupal\bos_search\AiSearchRequest The most recent search */
  protected AiSearchRequest $search;

  /** @var string The output answer from the AI Model (summary) */
  protected string $ai_answer = "";

  protected int $no_results = 0;

  protected string $violations = "";

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
      "no_results" => $this->no_results,
      "violations" => $this->violations,
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

  public function render(): string {

    $preset = $this->search->get("preset") ?? [];

    $render_array = ['#theme' => 'results__' . $preset["searchform"]["theme"]];

    $response = $this->getAll();

    if ($response["no_results"] == 0 && empty($response["violations"]) && $this->search_results) {
      // A summary and optionally citations and results have been returned
      // from the AI Model.
      $render_array += [
        '#items' => $this->search_results->getResults(),
        '#content' => $this->search->get("search_text"),
        '#id' => $this->search->getId(),
        '#response' => $this->body,
        '#feedback' => [
          "#theme" => "aisearch_feedback",
          "#thumbsup" => TRUE,
          "#thumbsdown" => TRUE,
        ],
        '#citations' => $preset["results"]["citations"] ? ($this->citations ?? NULL) : NULL,
        '#metadata' => $preset["results"]["metadata"] ? ($this->metadata ?? NULL) : NULL,
      ];

      if (!$preset["results"]["summary"] ?? TRUE) {
        // If we are supressing the summary, then also supress the citations.
        $render_array["#content"] = NULL;
        $render_array["#citations"] = NULL;
      }

      if (!$preset["results"]["feedback"] ?? TRUE) {
        // If we are supressing feedback.
        $render_array["#feedback"] = NULL;
      }
    }
    elseif (!empty($response["violations"])) {
      // There were violations.
      $render_array += [
        '#items' => $this->search_results->getResults() ?? [],
        '#content' => $this->search->get("search_text"),
        '#id' => $this->search->getId(),
        '#response' => $preset["results"]["violations_text"] ?? "Non-conforming Query",
        '#metadata' => $preset["results"]["metadata"] ? ($this->metadata ?? NULL) : NULL,
      ];

      if (!$preset["results"]["summary"] ?? TRUE) {
        // If we are supressing the summary, then also supress the citations.
        $render_array["#content"] = NULL;
        $render_array["#citations"] = NULL;
      }

      if (!$preset["results"]["feedback"] ?? TRUE) {
        // If we are supressing feedback.
        $render_array["#feedback"] = NULL;
      }
    }
    else {
      // No results message was returned from the AI.
      $render_array += [
        '#id' => $this->search->getId(),
        '#response' => $preset["results"]["no_result_text"],
        '#no_results' => $this->no_results,
        '#metadata' => $preset["results"]["metadata"] ? ($this->metadata ?? NULL) : NULL,
      ];

    }
    // Allow to override the theme template.
    if (!empty($this->search->get("result_template"))) {
      $render_array['#theme'] = $this->search->get("result_template");
    }
    return \Drupal::service("renderer")->render($render_array);
  }

}
