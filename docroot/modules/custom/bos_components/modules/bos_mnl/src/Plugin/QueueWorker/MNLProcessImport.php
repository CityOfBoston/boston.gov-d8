<?php

namespace Drupal\bos_mnl\Plugin\QueueWorker;

use Drupal;
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
   * Array of SAM objects from the database.
   *
   * @var array
   */
  private $cache;

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
      "cleanup" => 0,
      "duplicateSAM" => 0,
      "duplicateNID" => 0,
      "starttime" => strtotime("now"),
    ];

    \Drupal::logger("mnl import")
      ->info("[1] MNL Import queue worker initialized.");

    if ($this->queue->numberOfItems() > 0) {
      $this->buildCache();
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);

  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {

    if ($this->stats["processed"] == 0) {
      \Drupal::logger("mnl import")
        ->info("[1] MNL Import queue worker terminates: no neighborhood_lookup entities were processed.");
      return;
    }

    // Check if queue is empty.
    if ($this->endQueue()) {
      // The import queue is now empty - so now queue up
      // items that were not present in the import, and need to be deleted.
      $this->loadGarbageNodes();
    }

    \Drupal::logger("mnl import")
      ->info("[1] MNL Import queue worker terminates.");

    // Work out how many entities there now are (for reporting).
    $query = \Drupal::database()->select("node", "n")
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("count(n.nid)", "count");
    $result = $query->execute()->fetch();
    $this->stats["post-entities"] = $result->count;

    // Log.
    $cleanup = \Drupal::queue('mnl_cleanup');
    \Drupal::logger("mnl import")
      ->info("
        [1] Queue Process Results:<br>
        == Start Condition ==================<br>
        Entities in DB at start       " . number_format($this->stats["pre-entities"], 0) . "<br>
        Cache (unique SAM's) at start " . number_format($this->stats["cache"], 0) . "<br>
        mnl_import queue at start     " . number_format($this->stats["queue"], 0) . "<br>
        == Queue Processing =================<br>
        New entities created          " . number_format($this->stats["inserted"], 0) . "<br>
        Updated entities              " . number_format($this->stats["updated"], 0) . "<br>
        Unchanged entities            " . number_format($this->stats["unchanged"], 0) . "<br>
        Entities sent to be cleaned   " . number_format($this->stats["cleanup"], 0) . "<br>
        Duplicate SAM ID's found      " . number_format($this->stats["duplicateSAM"], 0) . "<br>
        == Result ============================<br>
        Entities processed            " . number_format($this->stats["processed"], 0) . "<br>
        Entities in DB at end         " . number_format($this->stats["post-entities"], 0) . "<br>
        Cache (unique SAM's) at end   " . number_format(count($this->cache), 0) . "<br>
        mnl_import queue at end       " . number_format($this->queue->numberOfItems(), 0) . "<br>
        mnl_cleanup queue at end      " . number_format($cleanup->numberOfItems(), 0) . "<br>
        == Runtime ===========================<br>
        processing time: " . gmdate("H:i:s", strtotime("now") - $this->stats["starttime"]) . "<br>
      ");

  }

  /**
   * Create a cache in this class of all neighborhood_lookup entities.
   *
   * The cache is an array of objects, one object for each node/entity.
   */
  private function buildCache() {
    // Work out how many entities there now are (for reporting).
    $query = \Drupal::database()->select("node", "n")
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("count(n.nid)", "count");
    $result = $query->execute()->fetch();
    $this->stats["pre-entities"] = $result->count;

    // Fetch and load the cache.
    $query = \Drupal::database()->select("node", "n")
      ->fields("n", ["nid"])
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("0", "processed");
    $query->join("node__field_sam_id", "id", "n.nid = id.entity_id");
    $query->join("node__field_sam_neighborhood_data", "dat", "n.nid = dat.entity_id");
    $query->join("node__field_sam_address", "addr", "n.nid = addr.entity_id");
    $query->condition("id.deleted", FALSE)
      ->condition("dat.deleted", FALSE)
      ->condition("addr.deleted", FALSE)
      ->fields("id", ["field_sam_id_value"])
      ->fields("dat", ["field_sam_neighborhood_data_value", "revision_id"])
      ->fields("addr", ["field_sam_address_value"]);

    $this->cache = $query->execute()->fetchAllAssoc("field_sam_id_value");

    $this->stats["cache"] = count($this->cache);

  }

  /**
   * Gather nodes not processed by this import and load into cleanup queue.
   */
  private function loadGarbageNodes() {

    $cleanup = \Drupal::queue('mnl_cleanup');

    if ($cleanup->numberOfItems() == 0) {

      // Find mnl entities which are to be deleted and load into queue.
      foreach ($this->cache as $item) {
        if (!$item->processed) {
          $cleanup->createItem($item->nid);
        }
      }

      if ($cleanup->numberOfItems() == 0) {
        \Drupal::logger("mnl import")
          ->info("[1] No neighborhood_lookup entities found for cleanup.");
        $this->stats["cleanup"] = 0;
      }

      else {
        \Drupal::logger("mnl import")
          ->info("[1] Found " . $cleanup->numberOfItems() . " old neighborhood_lookup entities for cleanup (and loaded them into cleanup queue).");
        $this->stats["cleanup"] = $cleanup->numberOfItems();
      }

    }

    else {
      // Nothing to cleanup.
      \Drupal::logger("mnl import")
        ->info("[1] Found " . $cleanup->numberOfItems() . " old neighborhood_lookup entities for cleanup (and loaded them into cleanup queue).");
    }

  }

  /**
   * Update node.
   *
   * @param mixed $cache
   *   The neighborhood lookup node.
   * @param array $data
   *   The import data object.
   */
  private function updateNode($cache, array $data) {
    $data_sam_address = $data["full_address"];
    $cache_sam_address = $cache->field_sam_address_value;
    $data_sam_record = json_encode($data["data"]);
    $cache_sam_record = $cache->field_sam_neighborhood_data_value;
    $nid = $cache->nid;
    $done = FALSE;

    if ($data_sam_record != $cache_sam_record) {
      \Drupal::database()->update("node__field_sam_neighborhood_data")
        ->condition("entity_id", $nid)
        ->fields(["field_sam_neighborhood_data_value" => $data_sam_record])
        ->execute();
      $done = TRUE;
    }
    if ($data_sam_address != $cache_sam_address) {
      \Drupal::database()->update("node__field_sam_address")
        ->condition("entity_id", $nid)
        ->fields(["field_sam_address_value" => $data_sam_address])
        ->execute();
      $done = TRUE;
    }

    if ($done) {
      $this->stats["updated"]++;
    }
    else {
      $this->stats["unchanged"]++;
    }

  }

  /**
   * Create new node.
   *
   * @param mixed $cache
   *   The current node field values from the cache.
   * @param array $data
   *   The imported data to insert.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createNode(&$cache, array $data) {

    $jsonData = json_encode($data['data']);

    $node = Node::create([
      'type'                        => 'neighborhood_lookup',
      'title'                       => $data['sam_address_id'],
      'field_sam_id'                => $data['sam_address_id'],
      'field_sam_address'           => $data['full_address'],
      'field_sam_neighborhood_data' => $jsonData,
    ]);
    $node->save();

    // Ad this new record to the cache.
    $cache->field_sam_id_value = $data['sam_address_id'];
    $cache->field_sam_address_value = $data['full_address'];
    $cache->field_sam_neighborhood_data_value = $jsonData;
    $cache->nid = $node->id();

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
    $cache = $this->cache[$item['sam_address_id']];

    if ($cache->processed) {
      $this->stats["duplicateSAM"]++;
    }
    else {

      if (NULL != $cache) {
        $this->updateNode($cache, $item);
      }
      else {
        $cache = $this->cache[$item['sam_address_id']] = new \stdClass();
        $this->createNode($cache, $item);
      }

      $cache->processed = TRUE;

    }

    $this->stats["processed"]++;

  }

}
