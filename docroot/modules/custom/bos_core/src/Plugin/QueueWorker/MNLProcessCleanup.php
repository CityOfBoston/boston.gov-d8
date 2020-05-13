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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    ini_set('memory_limit', '-1');
    $this->queue = \Drupal::queue($this->getPluginId());
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {
    // Check if import and delete queues are processed.
    if ($this->endQueues()) {
      // Reset the import flag field on all current neighborood lookup nodes.
      \Drupal::database()->update("node__field_import_date")
        ->fields(["field_import_date_value" => "0"])
        ->execute();
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
  }

}
