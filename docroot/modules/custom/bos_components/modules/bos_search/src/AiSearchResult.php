<?php

namespace Drupal\bos_search;

/**
 * class AiSearchResult.
 * This class defines a search result to be used by any AiSearch plugin.
 *
 * @see \Drupal\bos_search\AiSearchResponse
 * @see \Drupal\bos_search\Plugin\AiSearch\AiSearchPluginManager
 *
 * Example implementation:
 * @see \Drupal\bos_gc_aisearch_plugin\Plugin\AiSearch\GcVertexConversation
 */

class AiSearchResult {

  /** @var string Direct extract of copy from the page */
  protected string $content = "";

  /** @var string ID for the result */
  protected string $id = "";

  /** @var string Full URL link to resource which is the result */
  protected string $link = "";

  /** @var string [optional] Link for the title */
  protected string $link_title = '';

  /** @var string [optional] AI Model reference for result */
  protected string $ref = '';

  /** @var string A summary of the page content */
  protected string $summary = "";

  /** @var string Title for the result (usually result page title) */
  protected string $title = "";

  public function __construct(string $title, string $link, string $summary) {
    $this->title = $title;
    $this->link = $link;
    $this->summary = $summary;
  }

  /**
   * Get a property of this class.
   *
   * @param string $key
   *
   * @return string
   */
  public function get(string $key): string {
    return $this->{$key};
  }

  /**
   * Returns an array with all the properties of this class.
   *
   * @return array
   */
  public function getResult(): array {
    return [
      "content" => $this->content,
      "id" => $this->id,
      "link" => $this->link,
      "link_title" => $this->link_title,
      "ref" => $this->ref,
      "summary" => $this->summary,
      "title" => $this->title
    ];
  }

  /**
   * Set a property in this class.
   *
   * @param string $key
   * @param string $value
   *
   * @return $this
   */
  public function set(string $key, string $value): AiSearchResult {
    $this->{$key} = $value;
    return $this;
  }

}
