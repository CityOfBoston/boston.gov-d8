<?php

namespace Drupal\bos_gc_aisearch_plugin\Plugin\AiSearch;

use Drupal\bos_google_cloud\Services\GcConversation;
use Drupal\bos_search\AiSearchBase;
use Drupal\bos_search\AiSearchInterface;
use Drupal\bos_search\AiSearchRequest;
use Drupal\bos_search\AiSearchResponse;
use Drupal\bos_search\AiSearchResult;
use Drupal\bos_search\Annotation\AiSearchAnnotation;

/**
 * Provides an 'AiSearch' plugin for bos_google_cloud.
 *
 * @AiSearchAnnotation (
 *   id = "Vertex Conversation",
 *   service = "bos_google_cloud.GcConversation",
 *   description = "Plugin for Google Cloud Vertex Conversation Service"
 * )
 */
class GcVertexConversation extends AiSearchBase implements AiSearchInterface {

  /** @var \Drupal\bos_google_cloud\Services\GcConversation Holds the injected Vertex service. */
  protected GcConversation $vertex;

  /** @injectDoc */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Inject the GcSearch service.
    $this->vertex = \Drupal::getContainer()->get("bos_google_cloud.GcConversation");
  }

  /**
   * @inheritDoc
   */
  public function search(AiSearchRequest $request): AiSearchResponse {
    try {

      $this->vertex->execute([
        "text" => $request->get("search_text"),
        "conversation_id" => $request->get("conversation_id") ?? "",
      ]);
      $result = $this->vertex->getResults();
    }
    catch (\Exception $e) {
      $result = FALSE;
    }

    // Load the GcSearchConversationResponse into the AiSearchResponse fmt.
    if ($result) {
      $response = new AiSearchResponse($request, $result['ai_answer'], $result['conversation_id']);
      $response->set("body", $result['body'])
        ->set("citations", $result['citations'])
        ->set("metadata", $result['metadata'])
        ->set("references", $result['references']);
      foreach($result['search_results'] as $search_result) {
        // Load each search result into the AiSearchResult format.
        $res = new AiSearchResult($search_result["title"], $search_result["link"], $search_result["summary"]);
        $res->set("id", $search_result["id"])
          ->set("link_title", $search_result["link_title"])
          ->set("ref", $search_result["ref"]);
        $response->addResult($res);
      }
      $request->addHistory($response);
      $response->set("search", $request);
    }

    return $response;
  }

}
