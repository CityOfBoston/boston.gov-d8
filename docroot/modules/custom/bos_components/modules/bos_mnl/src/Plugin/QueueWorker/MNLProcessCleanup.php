<?php

namespace Drupal\bos_mnl\Plugin\QueueWorker;

use Drupal;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes cleanup of orphaned nodes from queue.
 *
 * @QueueWorker(
 *   id = "mnl_cleanup",
 *   title = @Translation("MNL remove any nodes not found on import."),
 * )
 */
class MNLProcessCleanup extends QueueWorkerBase {

  /**
   * Cache the queue object.
   *
   * @var \Drupal\Core\Queue\DatabaseQueue
   */
  private $queue;

  /**
   * Keeps queue statistics.
   *
   * @var array
   */
  private $stats = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {

    ini_set('memory_limit', '-1');
    ini_set("max_execution_time", "0");

    $this->queue = \Drupal::queue($plugin_id);

    \Drupal::logger("mnl import")
      ->info("[2] MNL cleanup queue worker initialized.");

    // Work out how many entities there now are (for reporting).
    $query = \Drupal::database()->select("node", "n")
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("count(n.nid)", "count");
    $result = $query->execute()->fetch();

    // Initialize the satistics array.
    $this->stats = [
      "queue" => $this->queue->numberOfItems(),
      "pre-entities" => $result->count,
      "post-entities" => 0,
      "processed" => 0,
      "starttime" => strtotime("now"),
    ];

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {

    if ($this->stats["processed"] == 0) {
      \Drupal::logger("mnl import")
        ->info("[2] MNL cleanup queue worker destroyed but no neighborhood_lookup entities were removed.");
      return;
    }

    // Check if import and delete queues are processed.
    if ($this->endQueues()) {
      \Drupal::logger("mnl import")
        ->info("[2] MNL cleanup queue worker terminates and MNL Cleanup IS complete.");
    }
    else {
      \Drupal::logger("mnl import")
        ->info("[2] MNL cleanup queue worker terminates but MNL Cleanup NOT complete, some queue items remain.");
    }

    // Work out how many entities there now are (for reporting).
    $query = \Drupal::database()->select("node", "n")
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("count(n.nid)", "count");
    $result = $query->execute()->fetch();
    $this->stats["post-entities"] = $result->count;

    \Drupal::logger("mnl import")
      ->info("
        [2] Queue Process Results:<br>
        == Start Condition ==================<br>
        Entities in DB at start      " . number_format($this->stats["pre-entities"], 0) . "<br>
        mnl_cleanup queue at start   " . number_format($this->stats["queue"], 0) . "<br>
        == Queue Processing =================<br>
        Removed entities             " . number_format($this->stats["processed"], 0) . "<br>
        == Result ============================<br>
        Entities in DB at end        " . number_format($this->stats["post-entities"], 0) . "<br>
        mnl_cleanup queue at end     " . number_format($this->queue->numberOfItems(), 0) . "<br>
        == Runtime ===========================<br>
        processing time: " . gmdate("H:i:s", strtotime("now") - $this->stats["starttime"]) . "<br>
      ");

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

    $this->stats["processed"]++;
  }

}
