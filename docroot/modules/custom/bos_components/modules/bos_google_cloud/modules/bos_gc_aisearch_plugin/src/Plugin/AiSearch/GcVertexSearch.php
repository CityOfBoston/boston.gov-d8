<?php

namespace Drupal\bos_gc_aisearch_plugin\Plugin\AiSearch;

use Drupal\bos_search\AiSearch;
use Drupal\bos_search\AiSearchBase;
use Drupal\bos_search\Model\AiSearchCitation;
use Drupal\bos_search\AiSearchInterface;
use Drupal\bos_search\Model\AiSearchReference;
use Drupal\bos_search\Model\AiSearchRequest;;
use Drupal\bos_search\Model\AiSearchResponse;
use Drupal\bos_search\Model\AiSearchResult;
use Drupal\bos_search\Annotation\AiSearchAnnotation;

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

  private const NO_RESULTS = "No Results";

  /** @injectDoc */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @inheritDoc
   */
  public function search(AiSearchRequest $request, bool $fake = FALSE): AiSearchResponse {
    try {
      // Ask the search question to Vertex.
      $preset = $request->get("preset") ?? [];
      if ($fake) {
        $response = $this->fakeResponse();
        if (empty($request->get("session_id"))) {
          $response["session_id"] = rand(10000000,99999999);
        }
        else {
          $response["session_id"] = $request->get("session_id");
        }
      }
      else {
        $parameters = [
          "text" => $request->get("search_text") ?? "",
          "allow_conversation" => $preset["searchform"]["searchbar"]["allow_conversation"] ?? FALSE,
          "session_id" => $request->get("session_id") ?? "",
          "prompt" => $preset["prompt"] ?? 'default',
          "extra_prompt" => 'If you cannot understand the question or the question cannot be answered, respond with the text "' . self::NO_RESULTS . '"',
          "metadata" => $preset["results"]["metadata"] ?? 0,
          "num_results" => $preset["results"]["result_count"] ?? 0,
          "include_citations" => $preset["results"]["citations"] ?? 0,
          "safe_search" => $preset["model_tuning"]['search']["safe_search"] ?? 0,
          "ignoreAdversarialQuery" => $preset["model_tuning"]['summary']["ignoreAdversarialQuery"] ?? 0,
          "ignoreNonSummarySeekingQuery" => $preset["model_tuning"]['summary']["ignoreNonSummarySeekingQuery"] ?? 0,
          "ignoreLowRelevantContent" => $preset["model_tuning"]['summary']["ignoreLowRelevantContent"] ?? 0,
          "ignoreJailBreakingQuery" => $preset["model_tuning"]['summary']["ignoreJailBreakingQuery"] ?? 0,
          "semantic_chunks" => $preset["model_tuning"]['summary']["semantic_chunks"] ?? 0,
        ];

        // Apply any service overrides.
        if (!empty($preset["model_tuning"]["overrides"]["service_account"]) && $preset["model_tuning"]["overrides"]["service_account"] != "default") {
          $parameters["service_account"] = $preset["model_tuning"]["overrides"]["service_account"];
          $this->service->setServiceAccount($parameters["service_account"]);
        }
        if (!empty($preset["model_tuning"]["overrides"]["project_id"]) && $preset["model_tuning"]["overrides"]["project_id"] != "default") {
          $parameters["project_id"] = $preset["model_tuning"]["overrides"]["project_id"];
        }
        if (!empty($preset["model_tuning"]["overrides"]["datastore_id"]) && $preset["model_tuning"]["overrides"]["datastore_id"] != "default") {
          $parameters["datastore_id"] = $preset["model_tuning"]["overrides"]["datastore_id"];
          // TODO fix the engine/datastore issue
          $parameters["datastore_id"] = "oeoi-search-pilot_1726266124376";
        }

        // Query the Agent Builder.
        $response = $this->service->execute($parameters);

      }
    }
    catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    // Load the SearchResponse object into the AiSearchResponse object.
    if ($response) {
      $output = $this->loadSearchResponse($this->getService()->response(), $preset, $request);
    }

    return $output;
  }

  /**
   * @inheritDoc
   */
  public function hasFollowUp(): bool {
    return $this->service->hasFollowup();
  }

  /**
   * @inheritDoc
   */
  public function availablePrompts(): array {
    return $this->service->availablePrompts();
  }

  /**
   * Loads a GoogleCloud SearchResponse object into this standardized
   * AiSearchResponse object.
   *
   * @param array $fullResponse
   * @param $preset
   * @param \Drupal\bos_search\AiSearchRequest $request
   *
   * @return AiSearchResponse
   *
   * @see bos_google_cloud/src/Apis/v1alpha/SearchResponse.php
   * @see bos_search/src/AiSearchResponse.php
   *
   */
  private function loadSearchResponse(array $fullResponse, $preset, AiSearchRequest $request): AiSearchResponse {

    $searchResponse = $fullResponse["object"]->toArray();

    $aiSearchResponse = new AiSearchResponse($request, $searchResponse["summary"]["summaryText"], $this->service->getSessionInfo()["session_id"] ?? "");

    // Load the metadata from the SearchResponse and extend with the preset info.
    if ($preset["results"]["metadata"]) {
      $metadata = array_merge($fullResponse["metadata"], ["Search Presets" => $preset]); // add presets
      $metadata = $this->flatten_metadata($metadata);
      $aiSearchResponse->set("metadata", $metadata);
    }

    // Load any citations.
    if ($preset["results"]["citations"] && !empty($searchResponse["summary"]["summaryWithMetadata"]["citationMetadata"]["citations"])) {
      $this->loadCitations($aiSearchResponse, $searchResponse);
    }

    // Load any results.
    if ($preset["results"]["searchresults"] && !empty($searchResponse["results"])) {
      $this->loadSearchResults($aiSearchResponse, $searchResponse, $preset);
    }

    // [optional] Resolve Search Results into a node and check.
    if ($preset["results"]["searchresults"] && !empty($searchResponse["results"])) {
      $this->postProcessResults($aiSearchResponse);
    }

    if (trim($searchResponse["summary"]["summaryText"]) == self::NO_RESULTS) {
      $aiSearchResponse->set("no_results", TRUE);
    }
    else {
      $aiSearchResponse->set("body", $searchResponse["summary"]["summaryText"]);
    }

    return $aiSearchResponse;

  }

  /**
   * Load the GCSearchResults into the AiSearchResponse format for Search Results.
   *
   * Also mark where results are duplicated in the list of references.
   *
   * @param \Drupal\bos_search\Model\AiSearchResponse $aiSearchResponse
   * @param array $searchResponse
   * @param bool $hasCitations Flag if preset allows citations
   * @param bool $notCitation Flag if results should only be loaded if they
   *                              are not a citation
   *
   * @return void
   */
  private function loadSearchResults(AiSearchResponse &$aiSearchResponse, array $searchResponse, array $preset):void {

    $references = $aiSearchResponse->getReferences();
    $hasCitations = $preset["results"]["citations"];
    $noDupCitation = $preset["results"]["no_dup_citations"];

    foreach($searchResponse["results"] as $search_result) {
      $ds = $search_result["document"]["derivedStructData"];
      $title = explode("|", $ds["htmlTitle"], 2)[0];
      $res = new AiSearchResult($title, $ds["link"], $ds["snippets"][0]["snippet"] ?: "");
      $docid = $search_result["id"];
      $res->set("id", $docid)
        ->set("link_title", explode("|", $ds["title"], 2)[0])
        ->set("ref", $search_result["document"]["name"])
        ->set("content", $ds["extractive_answers"][0]["content"] ?: "") // This may contain non-english content.
        ->set("description", "")
        ->set("is_citation", FALSE);

      // Check if this result is also in the citations (references) list.
      if ($hasCitations) {
        foreach($references as $reference) {
          $refdoc = explode("/", $reference["ref"]);
          $refdocid = array_pop($refdoc);
          if ($docid == $refdocid) {
            $res->set("is_citation", TRUE);
            break;
          }
        }
      }

      // Actually load this Result
      if ($noDupCitation) {
        if (!$res->get("is_citation")) {
          // If not loading results which are also citations
          // and this result is not also a citation, then load
          $aiSearchResponse->addResult($res);
        }
      }
      else {
        // If we are allowing results which are also citations
        // then load.
        $aiSearchResponse->addResult($res);
      }
    }
  }

  /**
   * Load the GCSearchResults into the AiSearchResponse format for Citation
   * and References.
   *
   * Also mark where references are duplicated in the list of search results.
   *
   * @param \Drupal\bos_search\Model\AiSearchResponse $aiSearchResponse
   * @param array $searchResponse
   *
   * @return void
   */
  private function loadCitations(AiSearchResponse &$aiSearchResponse, array $searchResponse):void {

    $citations = $searchResponse['summary']['summaryWithMetadata']['citationMetadata']['citations'];

    foreach($citations as $key => $citation) {

      $searchCitation = new AiSearchCitation($citation['startIndex'], $citation['endIndex']);
      foreach ($citation['sources'] as $source) {
        $searchCitation->addSource($source);
      }
      $aiSearchResponse->addCitation($searchCitation);

    }

    $references = $searchResponse["summary"]["summaryWithMetadata"]["references"];

    foreach($references as $key => $reference) {

      $title = explode("|", $reference["title"], 2)[0];
      $searchReference = new AiSearchReference($title, $reference["uri"], $reference["document"]);
      $searchReference->addChunkContent($reference["chunkContents"]["content"], $reference["chunkContents"]["pageIdentifier"] ?? "");
      $doc = explode("/", $reference["document"]);
      $searchReference->set("id", array_pop($doc));
      $searchReference->set("original_seq", $key);
      $searchReference->set("seq", $key + 1);
      $searchReference->set("is_result", FALSE);

      // Find citations which use this reference and add in the information.
      foreach($citations as $citation) {
        $locations = [];
        foreach ($citation["sources"] as $source) {
          if ($source == $key) {
            $locations[] = [
              "startIndex" => $citation["startIndex"] ?? 0,
              "endIndex" => $citation["endIndex"] ?? strlen($aiSearchResponse["body"]),
            ];
            break;
          }
        }
        $searchReference->set("locations", $locations);

      }

      // See if this citation is used in any search results
      foreach($searchResponse["results"] as $result) {
        if ($result["id"] == $searchReference->get("id")) {
          $searchReference->set("is_result", TRUE);
          break;
        }
      }

      // Load this reference if it is a citation.
      if (count($searchReference->get("locations"))) {
        $aiSearchResponse->addReference($searchReference);
      }

    }

  }

  /**
   * Post Process the AiSearchResponse Results array:
   * - Finding the nid for the node.
   * - Checking language of page.
   * - Loading the Drupal summary for content.
   *
   * @return void
   */
  private function postProcessResults(AiSearchResponse $aiSearchResponse):void {

    $results = $aiSearchResponse->getResultsCollection();

    $alias_manager = \Drupal::service('path_alias.manager');
    $redirect_manager = \Drupal::service('redirect.repository');

    foreach($results->getResults() as $key => $result) {

      $path_alias = explode(".gov", $result->get("link"), 2)[1];

      if (!empty($path_alias)) {
        // Strip out the alias from any other querystings etc
        $path_alias = explode('?', $path_alias, 2);
        $path_alias = explode('#', $path_alias[0], 2)[0];

        // get the nid for this page alias (to prevent duplicates)
        $path = $alias_manager->getPathByAlias($path_alias);
        $path_parts = explode('/', $path);
        $nid = array_pop($path_parts);

        if (!is_numeric($nid)) {
          // If we can't get the node ID then it is possibly a redirect to
          // another page, so try to track that down...
          $nid = NULL;
          $redirects = $redirect_manager->findBySourcePath(trim($path_alias, "/"));
          if (!empty($redirects)) {
            $redirect = reset($redirects);
            $original_alias = explode(":", $redirect->getRedirect()['uri'], 2)[1] ?? $redirect->getRedirect()['uri'];
            $path = $alias_manager->getPathByAlias($original_alias);
            $path_parts = explode('/', $path);
            $nid = array_pop($path_parts);
          }
        }

        if ($nid) {
          $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
          $description = "";
          // Build up a description if we can.
          if ($node && $node->hasField("field_intro_text")) {
            $description .= $node->get("field_intro_text")->value;
          }
          if ($node && $node->hasField("body")) {
            $description .= ($node->get("body")->summary ?: $node->get("body")->value);
          }
          if ($node && $node->hasField("field_need_to_know")) {
            $description .= $node->get("field_need_to_know")->value;
          }

          // Update the result
          $result->set("nid", $nid);
          $result->set("description", AiSearch::sanitize(strip_tags($description)));
          $results->updateResult($key, $result);
        }
      }


    }



  }

  /**
   * @param array &$elements
   * @param array $map
   * @param array $exclude_elem
   * @param string $prefix *
   *
* @inheritDoc
   */
  protected function flatten_metadata(array &$metadata, array $map = [], array $exclude_elem = []): array {
    $map = [];
    $exclude_elem = [];
    return [];
    return parent::flatten_metadata($metadata, $map, $exclude_elem);
  }

}
