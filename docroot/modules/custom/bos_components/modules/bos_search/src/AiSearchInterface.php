<?php

namespace Drupal\bos_search;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
* Defines an interface for Gift plugins.
*/
interface AiSearchInterface extends PluginInspectionInterface {

/**
* Return the AIModel Service.
*
* @return string
*   The AI Model service
*/
  public function getService();

  /**
   * Flag whether the service supports an ongoing conversation.
   *
   * @return bool TRUE is conversation supported.
   */
  public function hasConversation(): bool;

  /**
   * Perform a search using the selected AI model.
   *
   * @param \Drupal\bos_search\AiSearchRequest $search Standardized input.
   *
   * @return \Drupal\bos_search\AiSearchResponse Standardized output.
   */
  public function search(AiSearchRequest $request, bool $fake = FALSE): AiSearchResponse ;

}
