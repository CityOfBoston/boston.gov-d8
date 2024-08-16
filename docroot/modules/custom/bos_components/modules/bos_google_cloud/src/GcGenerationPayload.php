<?php

namespace Drupal\bos_google_cloud;

use Drupal;

class GcGenerationPayload {

  public const CONVERSATION = 0;
  public const PREDICTION = 1;

  /**
   * HARDCODED safety settings for bos_google_cloud gen-ai prediction services.
   */
  public const SAFETY_SETTINGS = [
    [
      "category" => "HARM_CATEGORY_HATE_SPEECH",
      "threshold" => "BLOCK_LOW_AND_ABOVE",
    ],
    [
      "category" => "HARM_CATEGORY_HARASSMENT",
      "threshold" => "BLOCK_LOW_AND_ABOVE",
    ],
    [
      "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
      "threshold" => "BLOCK_LOW_AND_ABOVE",
    ],
    [
      "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
      "threshold" => "BLOCK_LOW_AND_ABOVE",
    ],
  ];

  /**
   * Creates standardized payloads for the type defined.
   *
   * @param int $type a constant from this class
   * @param array $options
   *
   * @return array|bool
   * @throws \Exception
   */
  public static function build(int $type, array $options):array|bool {

    switch ($type) {

      case self::CONVERSATION:
        if (empty($options["text"]) || empty($options["prompt"])) {
          Drupal::logger("bos_google_cloud")
            ->error("Require Text and Prompt in payload (prompt:{$options["prompt"]},text:{$options["text"]}");
          return FALSE;
        }
        return self::buildConversation($options);

      case self::PREDICTION:
        if (empty($options["prediction"]) || empty($options["generation_config"])) {
          Drupal::logger("bos_google_cloud")
            ->error("Require Prediction and Generation Config in payload.",['referer' => __METHOD__]);
          return FALSE;
        }
        return self::buildPrediction($options["prediction"], $options["generation_config"]);

      default:
        return FALSE;
    }

  }

  /**
   * Produces the standardized payload for the conversations:converse endpoint.
   *
   * @param array $options An array of options for the conversation API
   *    string prompt The prompt to use to guide the AI responses.
   *    string text The conversation text to be processed by the AI.
   *    array conversation An ongoing conversation to be passed to the AI.
   *    int num_results Number of search results desired.
   *    bool include_citations If citations should be included in the response.
   *    bool safe_search If the API should conduct a safe search
   *    bool semantic_chunks Improve results using semantic chunking.
   *
   * @return array|bool
   *
   * @throws \Exception
   * @see https://cloud.google.com/generative-ai-app-builder/docs/reference/rest/v1alpha/projects.locations.collections.dataStores.conversations
   * @see https://cloud.google.com/generative-ai-app-builder/docs/reference/rest/v1/projects.locations.collections.dataStores.conversations/converse
   *
   * @see https://cloud.google.com/generative-ai-app-builder/docs/apis
   */
  private static function buildConversation(array $options): array|bool {

    $payload = [
      "query" => [
        "input" => $options["text"],
        // "context" => "",
      ],
      "safeSearch" => $options["safe_search"] ?? FALSE, // control the level of explicit content that the system can display in the results. This is similar to the feature used in Google Search, where you can modify your settings to filter explicit content, such as nudity, violence, and other adult content, from the search results.
      "summarySpec" => [
        "summaryResultCount" => $options["num_results"] ?? 5,
        "includeCitations" => $options["include_citations"] ?? FALSE,
        "ignoreAdversarialQuery" => TRUE, //  No summary is returned if the search query is classified as an adversarial query. For example, a user might ask a question regarding negative comments about the company or submit a query designed to generate unsafe, policy-violating output.
        "ignoreNonSummarySeekingQuery" => TRUE, // No summary is returned if the search query is classified as a non-summary seeking query. For example, why is the sky blue and Who is the best soccer player in the world? are summary-seeking queries, but SFO airport and world cup 2026 are not.
        "ignoreLowRelevantContent" => TRUE, //  If true, only queries with high relevance search results will generate answers.
        "modelPromptSpec" => [
          "preamble" => GcGenerationPrompt::getPromptText("search", $options["prompt"])
        ],
        "modelSpec" => [
          "version" => $options["model"] ?? "stable",
        ],
        "useSemanticChunks" => $options["semantic_chunks"] ?? FALSE, // answer will be generated from most relevant chunks from top search results. This feature will improve summary quality. Note that with this feature enabled, not all top search results will be referenced and included in the reference list, so the citation source index only points to the search results listed in the reference list.
        // "languageCode" => "",
      ],
      // "servingConfig" => "",
      // "conversation" => $conversation,
      // "userLabels" => []
      // "filter" => "",
      // "boostSpec" => [],
    ];

    if (!empty($options["conversation"])) {
      // Pick up the conversation.
      $payload["conversation"] = self::sanitizeConversation($options["conversation"]);
    }

    return $payload;

  }

  /**
   * Checks that the conversation array is propely formatted.
   *
   * @param array $conversation
   *
   * @return array
   */
  private static function sanitizeConversation(array $conversation): array {
    foreach($conversation["messages"] as &$message) {
      if (array_key_exists("reply", $message)) {
        if (empty($message["reply"]["summary"]["summaryWithMetadata"]["citationMetadata"])) {
          $message["reply"]["summary"]["summaryWithMetadata"]["citationMetadata"] = NULL;
        }
      }
    }
    return $conversation;
  }

  /**
   * Produces the standardized payload for the conversations:converse endpoint
   *
   * @param array $prompts Array of prompts/questions/text to make prediction on.
   * @param array $generation_config Configuration for prediction engine (LLM).
   *
   * @return array|bool The properly formatted payload.
   *
   * @see https://cloud.google.com/vertex-ai/docs/generative-ai/model-reference/gemini
   */
  private static function buildPrediction(array $prompts, array $generation_config): array|bool {

    $payload = [
      "contents" => [
        [
          "role" => "USER",
          "parts" => []
        ]
      ],
      "safetySettings" => self::SAFETY_SETTINGS,
      "generationConfig" => $generation_config,
    ];
    foreach($prompts as $prompt) {
      $payload["contents"][0]["parts"][] = ["text" => $prompt];
    }
    return $payload;

  }

}
