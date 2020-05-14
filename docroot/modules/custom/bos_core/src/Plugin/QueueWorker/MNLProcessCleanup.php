<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

use Drupal;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes / compares MNL current node queue with delete queue.
 */
class MNLProcessCleanup extends QueueWorkerBase {

  /**
   * Cache the queue object.
   *
   * @var \Drupal\Core\Queue\DatabaseQueue
   */
  private $queue;

  /**
   * Keep track of how many rows processed during the workers lifetime.
   *
   * @var int
   */
  private $count;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    ini_set('memory_limit', '-1');
    $this->queue = \Drupal::queue($this->getPluginId());
    \Drupal::logger("mnl import")
      ->info("[2] MNL Cleanup Worker initialized.");
    $this->count = 0;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {
    // Check if import and delete queues are processed.
    if ($this->endQueues()) {
      \Drupal::logger("mnl import")
        ->info("[2] Worker destroyed and MNL Cleanup IS complete. Removed " . $this->count . " neighborhood_lookup entities.");
      // Reset the import flag field on all current neighborood lookup nodes.
      $result = \Drupal::database()->update("node__field_import_date")
        ->fields(["field_import_date_value" => "0"])
        ->execute();
      \Drupal::logger("mnl import")->info("[2] Import flag reset on $result neighborhood_lookup entities.");
    }
    else {
      \Drupal::logger("mnl import")
        ->info("[2] Worker destroyed but MNL Cleanup NOT complete. Removed " . $this->count . " neighborhood_lookup entities.");
    }
  }

  /**
   * Check if end of mnl_import queue.
   */
  private function endQueues() {
    $queue = \Drupal::queue('mnl_import');
    return ($queue->numberOfItems() == 0 && $this->queue->numberOfItems() == 0);
  }

  /**
   * Process each record.
   */
  public function processItem($item) {
    // Remove this node as it wasn't present in the import.
    \Drupal::entityTypeManager()
      ->getStorage("node")
      ->load($item)
      ->delete();
    $this->count++;
  }

}
