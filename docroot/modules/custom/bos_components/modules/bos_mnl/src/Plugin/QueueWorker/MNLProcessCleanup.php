<?php

namespace Drupal\bos_mnl\Plugin\QueueWorker;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Component\Datetime;

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
   * Keep the node ID's we wish to clean out of the DB in this array.
   *
   * @var array
   */
  private $ids = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {

    ini_set('memory_limit', '-1');
    ini_set("max_execution_time", "0");

    $this->queue = \Drupal::queue($plugin_id);

    \Drupal::logger("bos_mnl")
      ->info("Queue: MNL cleanup queue worker initialized.");

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

    $output = "";
    $start = microtime(TRUE);

    $this->findDuplicates();
    if (!empty($this->ids)) {
      $this->removeRecords($this->ids);
    }

    if ($this->stats["processed"] == 0 && $this->stats["dupes"] == 0) {
      $output = "Queue: MNL cleanup queue worker finished: no neighborhood_lookup entities were removed.";
    }
    else {
      // Check if import and delete queues are processed.
      if ($this->emptyQueues()) {
        $output = "Queue: MNL cleanup queue worker finished: MNL Cleanup is complete.";
      }
      else {
        $output = "Queue: MNL cleanup queue worker finished: MNL Cleanup NOT complete, some queue items remain.";
      }
    }

    // Work out how many entities there now are (for reporting).
    $query = \Drupal::database()->select("node", "n")
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("count(n.nid)", "count");
    $result = $query->execute()->fetch();
    $this->stats["post-entities"] = $result->count;

    $output .= "<br>
        Queue Process Results:<br>
        Completed at: " . date("Y-m-d H:i:s", strtotime("now")) . " UTC<br>
        == Start Condition =====================<br>
        Addresses in DB at start:           " . number_format($this->stats["pre-entities"], 0) . "<br>
        mnl_cleanup queue length at start:  " . number_format($this->stats["queue"], 0) . " queued records.<br>
        == Queue Processing ====================<br>
        Addressses removed (old or orphan): " . number_format($this->stats["processed"], 0) . "<br>
        Duplicate addresses removed:        " . number_format($this->stats["dupes"], 0) . "<br>
        == Result ===============================<br>
        Addresses in DB at end:             " . number_format($this->stats["post-entities"], 0) . "<br>
        mnl_cleanup queue length at end:    " . number_format($this->queue->numberOfItems(), 0) . " queued records.<br>
        == Runtime ==============================<br>
        processing time: " . gmdate("H:i:s", strtotime("now") - $this->stats["starttime"]) . "<br>
    ";
    \Drupal::logger("bos_mnl")->info($output);
    \Drupal::configFactory()->getEditable('bos_mnl.settings')->set('last_mnl_cleanup', $output)->save();

    $elapsed = microtime(TRUE) - $start;
    printf(" [info] " . count($this->ids) . " Stale & duplicate records deleted in " . number_format($elapsed, 2) . " sec");

  }

  /**
   * Check if the import queue and cleanup queues are both empty.
   */
  private function emptyQueues() {
    $import_queue = \Drupal::queue('mnl_import');
    $cleanup_queue = $this->queue;
    return ($import_queue->numberOfItems() == 0 && $cleanup_queue->numberOfItems() == 0);
  }

  /**
   * Check and remove duplicate SAM ID's.
   * It is possible that duplicate SAM ID's can be generated.  Therefore we
   * need to scan for duplicate SAM ID's in the table.
   */
  private function findDuplicates() {
    // Fetch and load a list of SAMIDs which have multiple records.
    $results = \Drupal::database()->select('node__field_sam_id', 'sid')
      ->fields('sid', ['field_sam_id_value'])
      ->groupBy("sid.field_sam_id_value")
      ->having('count(*) > 1')
      ->execute()->fetchAll();

    // Process each duplicate SAM ID, retaining only the latest record.
    if ($results) {

      foreach ($results as $result) {
        // Grab each dupicate SAM ID in turn, and delete all but the last node.
        $duplicates = \Drupal::database()->select('node__field_sam_id', 'sid')
          ->fields('sid', ['entity_id'])
          ->condition("sid.field_sam_id_value", $result->field_sam_id_value, "=")
          ->orderBy("revision_id", "ASC")
          ->execute()->fetchAll();

        if ($duplicates && count($duplicates) > 1) {

          // Delete all duplicate SAM IDs EXCEPT for last/latest.
          array_pop($duplicates);

          // Add entity id to the id's class array for later deletion.
          foreach ($duplicates as $dupe) {
            $this->ids[] = $dupe->entity_id;
          }

          $this->stats["dupes"] += count($duplicates);

        }

      }

    }
  }

  /**
   * Remove a list of Stale MNL records.
   * This is separated out into this function to take advantage of Drupals
   * bulk processing which is more resource efficient.
   *
   * @param $ids array Array of entity (node) ID's to remove.
   *
   * @return void
   */
  private function removeRecords($ids) {

    $storage = \Drupal::entityTypeManager()->getStorage("node");

    $ids = array_unique($ids);

    // Going to process the list in chunks of 50 records.
    foreach (array_chunk($ids, 50) as $chunk) {
      $nodes = $storage->loadMultiple($chunk);
      $storage->delete($nodes);
    }
  }

  /**
   * Process each record.
   */
  public function processItem($item) {
    // Cache the node id to remove when this class terminates.
    $this->ids[] = $item;

    // Invalidate the Drupal cache for this node.
    Cache::invalidateTags(["node:" . $item]);

    $this->stats["processed"]++;
  }

}
