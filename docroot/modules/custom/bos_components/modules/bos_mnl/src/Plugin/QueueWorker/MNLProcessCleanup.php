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
      "dupes" => 0,
      "starttime" => strtotime("now"),
    ];

    parent::__construct($configuration, $plugin_id, $plugin_definition);

  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {

    $this->removeDupes();

    if ($this->stats["processed"] == 0 && $this->stats["dupes"] !== 0) {
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

    $output = "
        [2] Queue Process Results:<br>
        Completed at: " . date("Y-m-d H:i:s", strtotime("now")) . " UTC<br>
        == Start Condition ==================<br>
        Entities in DB at start:      " . number_format($this->stats["pre-entities"], 0) . "<br>
        mnl_cleanup queue at start:   " . number_format($this->stats["queue"], 0) . "<br>
        == Queue Processing =================<br>
        Removed out of date entities:           " . number_format($this->stats["processed"], 0) . "<br>
        Removed duplicate entities:             " . number_format($this->stats["dupes"], 0) . "<br>
        == Result ===========================<br>
        Entities in DB at end:        " . number_format($this->stats["post-entities"], 0) . "<br>
        mnl_cleanup queue at end:     " . number_format($this->queue->numberOfItems(), 0) . "<br>
        == Runtime ==========================<br>
        processing time: " . gmdate("H:i:s", strtotime("now") - $this->stats["starttime"]) . "<br>
    ";
    \Drupal::logger("mnl import")->info($output);
    \Drupal::configFactory()->getEditable('bos_mnl.settings')->set('last_mnl_cleanup', $output)->save();
  }

  /**
   * Check if end of mnl_import queue.
   */
  private function endQueues() {
    $queue = \Drupal::queue('mnl_import');
    return ($queue->numberOfItems() == 0 && $this->queue->numberOfItems() == 0);
  }

  /**
   * Check and remove dupes.
   */
  private function removeDupes() {
    // Fetch and load the dupes.
    $query = \Drupal::database()->select('node__field_sam_id', 'sid')
      ->fields('sid', ['field_sam_id_value']);
    $query->addExpression('count(*) > 1', 'dupes');
    $query->groupBy("sid.field_sam_id_value");
    $query->having('dupes >= :matches', [':matches' => 1]);
    $results = $query->execute()->fetchAll();

    // Load dupes and find changed date.
    foreach ($results as $result) {
      $sam_id = $result->field_sam_id_value;
      $query = \Drupal::database()->select('node_field_data', 'nfd')
        ->fields('nfd', ['nid', 'title', 'status', 'type', 'changed']);
      $query->condition('nfd.status', '1')
        ->condition('nfd.type', 'neighborhood_lookup')
        ->condition('nfd.title', $sam_id);
      $dupes = $query->execute()->fetchAll();
      $dupesSort = array_column($dupes, 'changed');
      array_multisort($dupesSort, SORT_DESC, $dupes);
      foreach ($dupes as $dupe => $item) {
        // Delete all duplicate SAM IDs EXCEPT for first/newest.
        if ($dupe !== 0) {
          // Remove duplicate items.
          \Drupal::entityTypeManager()
            ->getStorage("node")
            ->load($item->nid)
            ->delete();
        }
        $this->stats["dupes"]++;
      }
    }
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
