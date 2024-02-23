<?php

namespace Drupal\bos_google_cloud;

class GcGenerationURL {

  public const CONVERSATION = 0;
  public const PREDICTION = 1;

  /**
   * Creates the URL for the endpoint base don the specified $type.
   *
   * @param int $type a constant from this class
   * @param array $options
   *
   * @return string|bool
   */
  public static function build(int $type, array $options):string|bool {

    switch ($type) {

      case self::CONVERSATION:
        if (empty($options["endpoint"]) || empty($options["project_id"])
          || empty($options["location_id"]) || empty($options["datastore_id"])) {
          return FALSE;
        }
        return self::buildConversation($options["endpoint"], $options["project_id"], $options["location_id"], $options["datastore_id"]);

      case self::PREDICTION:
        if (empty($options["endpoint"]) || empty($options["project_id"])
          || empty($options["location_id"]) || empty($options["model_id"])) {
          return FALSE;
        }
        return self::buildPrediction($options["endpoint"], $options["project_id"], $options["location_id"], $options["model_id"]);

      default:
        return FALSE;
    }

  }

  /**
   * Produces the standardized URL/endpoint for the conversations:converse
   * endpoint.
   *
   * @param string $endpoint
   * @param string $project_id
   * @param string $location_id
   * @param string $datastore_id
   *
   * @return string
   *
   * @see https://cloud.google.com/generative-ai-app-builder/docs/apis
   * @see https://cloud.google.com/generative-ai-app-builder/docs/reference/rest/v1alpha/projects.locations.collections.dataStores.conversations
   * @see https://cloud.google.com/generative-ai-app-builder/docs/reference/rest/v1/projects.locations.collections.dataStores.conversations/converse
   */
  private static function buildConversation(string $endpoint, string $project_id, string $location_id, string $datastore_id): string {

    $url = $endpoint;
    $url .= "/v1alpha/projects/$project_id";
    $url .= "/locations/$location_id";
    $url .= "/collections/default_collection/dataStores/$datastore_id";
    $url .= "/conversations/-:converse";
    return $url;

  }

  /**
   *  Produces the standardized URL/endpoint for the models:streamGenerateContent
   *  endpoint.
   *
   * @param string $endpoint
   * @param string $project_id
   * @param string $location_id
   * @param string $model_id
   *
   * @return string
   */
  private static function buildPrediction(string $endpoint, string $project_id, string $location_id, string $model_id): string {

    $url = $endpoint;
    $url .= "/v1/projects/$project_id";
    $url .= "/locations/$location_id";
    $url .= "/publishers/google/models/$model_id:streamGenerateContent";
    return $url;

  }

}
