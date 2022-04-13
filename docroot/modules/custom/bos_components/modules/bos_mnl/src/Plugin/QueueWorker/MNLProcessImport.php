<?php

namespace Drupal\bos_mnl\Plugin\QueueWorker;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\node\Entity\Node;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes import of nodes from queue.
 *
 * @QueueWorker(
 *   id = "mnl_import",
 *   title = @Translation("MNL Import records / nodes."),
 * )
 */
class MNLProcessImport extends QueueWorkerBase {

  /**
   * Cache the queue object.
   *
   * @var \Drupal\Core\Queue\DatabaseQueue
   */
  private $queue;

  /**
   * Array of SAM objects from the database. (Note: 400k records)
   *
   * @var array
   */
  private $mnl_cache;

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
    $cleanup = \Drupal::queue('mnl_cleanup');

    // Initialize the satistics array.
    $this->stats = [
      "queue" => $this->queue->numberOfItems(),
      "cache" => 0,
      "pre-entities" => 0,
      "post-entities" => 0,
      "processed" => 0,
      "updated" => 0,
      "inserted" => 0,
      "unchanged" => 0,
      "cleanup-start" => $cleanup->numberOfItems(),
      "cleanup" => 0,
      "duplicateSAM" => 0,
      "duplicateNID" => 0,
      "starttime" => strtotime("now"),
    ];

    \Drupal::logger("bos_mnl")
      ->info("Queue: MNL Import queue worker initialized.");

    if ($this->queue->numberOfItems() > 0) {
      $this->buildSamCache();
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);

  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {

    $output = "";
    $start = microtime(TRUE);

    if ($this->stats["processed"] == 0) {
      $output = "Queue: MNL Import queue worker terminates: no neighborhood_lookup entities were processed.";
    }
    else {
      // Check if queue is empty.
      if ($this->endQueue()) {
        // The import queue is now empty - assume import is complete
        // ToDo: think of a better way to determine import ends
        // ToDo: User bos_SQL to query Civis DB rather than relying on a push
        // Find items that were not present in the import, and queue to be
        // deleted.
        $this->loadStaleNodes();
      }

      $output = "Queue: MNL Import queue worker finished.";
    }

    // Work out how many entities there now are (for reporting).
    $query = \Drupal::database()->select("node", "n")
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("count(n.nid)", "count");
    $result = $query->execute()->fetch();
    $this->stats["post-entities"] = $result->count;

    // Log.
    $cleanup = \Drupal::queue('mnl_cleanup');
    $cache_count = empty($this->mnl_cache) ? 0 : count($this->mnl_cache);
    $output .= "<br>
        Queue Process Results:<br>
        Completed at: " . date("Y-m-d H:i:s", strtotime("now")) . " UTC<br>
        == Start Condition =====================<br>
        Addresses in DB at start:          " . number_format($this->stats["pre-entities"], 0) . "<br>
        Unique SAM ID's at start:          " . number_format($this->stats["cache"], 0) . "<br>
        mnl_import queue length at start:  " . number_format($this->stats["queue"], 0) . " queued records.<br>
        mnl_cleanup queue length at start: " . number_format($this->stats["cleanup-start"], 0) . " queued records.<br>
        == Queue Processing ====================<br>
        New addresses created:             " . number_format($this->stats["inserted"], 0) . "<br>
        Updated addresses:                 " . number_format($this->stats["updated"], 0) . "<br>
        Unchanged addresses:               " . number_format($this->stats["unchanged"], 0) . "<br>
        Duplicate SAM ID's skipped:        " . number_format($this->stats["duplicateSAM"], 0) . "<br>
        Addresses added to mnl_cleanup:    " . number_format($this->stats["cleanup"], 0) . "<br>
        == Result ==============================<br>
        Addresses processed from queue     " . number_format($this->stats["processed"], 0) . "<br>
        Addresses in DB at end:            " . number_format($this->stats["post-entities"], 0) . "<br>
        Unique SAM ID's at end:            " . number_format($cache_count, 0) . "<br>
        mnl_import queue length at end:    " . number_format($this->queue->numberOfItems(), 0) . " queued records.<br>
        mnl_cleanup queue length at end:   " . number_format($cleanup->numberOfItems(), 0) . " queued records.<br>
        == Runtime =============================<br>
        Process duration: " . gmdate("H:i:s", strtotime("now") - $this->stats["starttime"]) . "<br>
    ";
    \Drupal::logger("bos_mnl")->info($output);
    \Drupal::configFactory()->getEditable('bos_mnl.settings')->set('last_mnl_import', $output)->save();

    $elapsed = microtime(TRUE) - $start;
    printf(" [info] " . $this->stats["cleanup"] . " Stale & duplicate records detected/enqueued in " . number_format($elapsed, 2) . " sec");

  }

  /**
   * Create a cache in this class of all neighborhood_lookup entities.
   *
   * The cache is an array of objects, one object for each node/entity.
   */
  private function buildSamCache() {
    // Work out how many entities there now are (for reporting).
    $query = \Drupal::database()->select("node", "n")
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("count(n.nid)", "count");
    $result = $query->execute()->fetch();
    $this->stats["pre-entities"] = $result->count;

    // Fetch and load the cache.
    $query = \Drupal::database()->select("node", "n")
      ->fields("n", ["nid", "vid"])
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("0", "processed");
    $query->join("node__field_sam_id", "id", "n.nid = id.entity_id");
    $query->leftjoin("node__field_sam_neighborhood_data", "dat", "n.nid = dat.entity_id");
    $query->join("node__field_sam_address", "addr", "n.nid = addr.entity_id");
    $query->condition("id.deleted", FALSE)
      ->condition("addr.deleted", FALSE)
      ->fields("id", ["field_sam_id_value"])
      ->fields("dat", ["field_sam_neighborhood_data_value"])
      ->fields("addr", ["field_sam_address_value"]);
    $or = $query->orConditionGroup()
      ->condition("dat.deleted", FALSE)
      ->isNull("dat.deleted");
    $query->condition($or);

    $this->mnl_cache = $query->execute()->fetchAllAssoc("field_sam_id_value");

    $this->stats["cache"] = count($this->mnl_cache);

  }

  /**
   * Gather nodes not processed by this import and load into cleanup queue.
   */
  private function loadStaleNodes() {

    $cleanup = \Drupal::queue('mnl_cleanup');

    // Check the cleanup queue, if its not empty, then we should abort.
    // (previous cleanup process failed, we dont want to add new cleanup records
    // while in an unknown state.
    if ($cleanup->numberOfItems() == 0) {

      // Find mnl entities which are to be deleted and load into queue.
      $tmp = 0;
      foreach ($this->mnl_cache as $item) {
        if ($item->processed == 0) {
          $cleanup->createItem($item->nid);
          $tmp++;
          if ($tmp >= 50) {break;}
        }
      }
      $this->stats["cleanup"] = $cleanup->numberOfItems() ;

      if ($this->stats["cleanup"] == 0) {
        $output = "Queue: No neighborhood_lookup entities found for cleanup.";
      }
      else {
        $output = "Queue: Found " . $cleanup->numberOfItems() . " old neighborhood_lookup entities for cleanup (and loaded them into cleanup queue).";
      }
      \Drupal::logger("bos_mnl")->info($output);
    }

    else {
      // Will not cleanup, publish warning.
      $output = "Queue: Found " . $cleanup->numberOfItems() . " old neighborhood_lookup entities for cleanup (and loaded them into cleanup queue).";
      \Drupal::logger("bos_mnl")->warning($output);
    }

  }

  /**
   * Update node.
   *
   * @param mixed $existing_record
   *   The neighborhood lookup node.
   * @param array $new_record
   *   The import data object.
   */
  private function updateNode($existing_record, array $new_record) {
    $data_sam_address = $new_record["full_address"];
    $cache_sam_address = $existing_record->field_sam_address_value;
    $data_sam_record = json_encode($new_record["data"]);
    $cache_sam_record = $existing_record->field_sam_neighborhood_data_value;
    $nid = $existing_record->nid;

    if ($data_sam_record != $cache_sam_record || $data_sam_address != $cache_sam_address) {

      if ($data_sam_record != $cache_sam_record) {

        if (empty($cache_sam_record)) {
          // Edge-case where the node exists but the data field has been removed
          // to manage database space. This should only occur on non-prod sites.
          \Drupal::database()->insert("node__field_sam_neighborhood_data")
            ->fields([
              "bundle" => "neighborhood_lookup",
              "deleted" => 0,
              "entity_id" => $existing_record->nid,
              "revision_id" => $existing_record->vid,
              "langcode" => "en",
              "delta" => 0,
              "field_sam_neighborhood_data_value" => $data_sam_record
            ])
            ->execute();
        }
        else {
          \Drupal::database()->update("node__field_sam_neighborhood_data")
            ->condition("entity_id", $nid)
            ->fields(["field_sam_neighborhood_data_value" => $data_sam_record])
            ->execute();
        }
      }
      if ($data_sam_address != $cache_sam_address) {
        \Drupal::database()->update("node__field_sam_address")
          ->condition("entity_id", $nid)
          ->fields(["field_sam_address_value" => $data_sam_address])
          ->execute();
      }

      // Force a save to invalidate the Drupal cache for this node.
      \Drupal::entityTypeManager()
        ->getStorage("node")
        ->load($existing_record->nid)
        ->addCacheableDependency((new CacheableMetadata())
          ->setCacheMaxAge(0))
        ->save();

      $this->stats["updated"]++;

    }
    else {
      $this->stats["unchanged"]++;
    }

  }

  /**
   * Create new node.
   *
   * @param mixed $existing_record
   *   The current node field values from the cache.
   * @param array $new_record
   *   The imported data to insert.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createNode(&$existing_record, array $new_record) {

    $jsonData = json_encode($new_record['data']);

    $node = Node::create([
      'type'                        => 'neighborhood_lookup',
      'title'                       => $new_record['sam_address_id'],
      'field_sam_id'                => $new_record['sam_address_id'],
      'field_sam_address'           => $new_record['full_address'],
      'field_sam_neighborhood_data' => $jsonData,
    ]);
    $node->addCacheableDependency((new CacheableMetadata())->setCacheMaxAge(0));
    $node->save();

    // Ad this new record to the cache.
    $existing_record->field_sam_id_value = $new_record['sam_address_id'];
    $existing_record->field_sam_address_value = $new_record['full_address'];
    $existing_record->field_sam_neighborhood_data_value = $jsonData;
    $existing_record->nid = $node->id();

    $this->stats["inserted"]++;

  }

  /**
   * Check if end of mnl_import queue.
   */
  private function endQueue() {
    return $this->queue->numberOfItems() == 0;
  }

  /**
   * Process each queue record.
   *
   * @param mixed $item
   *   The item stored in the queue.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($item) {

    $item = (array) $item;
    $cache = isset($this->mnl_cache[$item['sam_address_id']]) ? $this->mnl_cache[$item['sam_address_id']] : FALSE;

    if ($cache) {
      if ($cache->processed) {
        $this->stats["duplicateSAM"]++;
      }
      else {
        $this->updateNode($cache, $item);
        $cache->processed = 1;
      }
    }

    else {
      $cache = new \stdClass();
      $this->createNode($cache, $item);
      $this->mnl_cache[$item['sam_address_id']] = $cache;
      $cache->processed = 1;
    }

    $this->stats["processed"]++;

  }

}
