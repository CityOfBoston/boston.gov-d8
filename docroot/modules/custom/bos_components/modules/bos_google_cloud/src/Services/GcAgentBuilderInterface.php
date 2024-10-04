<?php

/**
 * Interface to handle search&conversation specific methods.
 * "Extends" the GcServiceInterface.
 */

namespace Drupal\bos_google_cloud\Services;

interface GcAgentBuilderInterface {

  /**
   * Returns the current session info (if any).
   * @return array
   */
  public function getSessionInfo(): array;

  /**
   * Loads metadata into the response object
   *
   * @param array $parameters *
   *
   * @return void
   */
  public function loadMetadata(array $parameters): void;

}
