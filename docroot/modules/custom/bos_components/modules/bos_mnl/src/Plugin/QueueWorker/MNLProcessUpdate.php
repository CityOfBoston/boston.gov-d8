<?php

namespace Drupal\bos_mnl\Plugin\QueueWorker;

use Drupal\bos_mnl\Controller\MnlUtilities;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes incremental update of nodes from queue.
 *
 * @QueueWorker(
 *   id = "mnl_update",
 *   title = @Translation("MNL Updates records / nodes."),
 * )
 */
class MNLProcessUpdate extends QueueWorkerBase {

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

    $this->queue = \Drupal::queue($plugin_id);

    // If the queue is not empty, then prepare to process the queue
    if ($this->queue->numberOfItems() > 0) {
      // Fetch and load the mnl_cache.
      $this->mnl_cache = MnlUtilities::MnlCacheExistingSamIds();

      // Initialize the satistics array.
      $query = \Drupal::database()->select("node", "n")
        ->condition("n.type", "neighborhood_lookup");
      $query->addExpression("count(n.nid)", "count");
      $result = $query->execute()->fetch();

      $settings = \Drupal::configFactory()->getEditable('bos_mnl.settings');
      if (!empty($settings->get('tmp_update'))) {
        $this->stats = json_decode($settings->get('tmp_update'));
      }
      else {
        $this->stats = [
          "queue" => $this->queue->numberOfItems(),
          "cache" => $this->mnl_cache ? count($this->mnl_cache) : 0,
          "pre-entities" => $result ? $result->count : 0,
          "post-entities" => 0,
          "processed" => 0,
          "updated" => 0,
          "inserted" => 0,
          "unchanged" => 0,
          "duplicateSAM" => 0,  // Duplicate record in the REST payload
          "duplicateNID" => 0,
          "starttime" => strtotime("now"),
        ];
      }

      \Drupal::logger("bos_mnl")
        ->info("Queue: MNL Update queue worker initialized.");

    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);

  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {

    if (empty($this->stats["processed"]) || $this->stats["processed"] == 0) {
      \Drupal::logger("bos_mnl")
        ->info("Queue: MNL Update queue worker terminates: no neighborhood_lookup entities were processed.");
    }
    else {
      // We processed some records, and added to the purge queue.
      // Need to process the purge queue otherwise we get issues.
      MnlUtilities::MnlProcessPurgeQueue();
    }

    \Drupal::logger("bos_mnl")
      ->info("Queue: MNL Update queue worker terminates.");

    // Work out how many entities there now are (for reporting).
    $query = \Drupal::database()->select("node", "n")
      ->condition("n.type", "neighborhood_lookup");
    $query->addExpression("count(n.nid)", "count");
    $result = $query->execute()->fetch();
    $this->stats["post-entities"] = $result->count;
    $cache_count = empty($this->mnl_cache) ? 0 : count($this->mnl_cache);

    // Log.
    $output = "
        <b>Completed at: " . date("Y-m-d H:i:s", strtotime("now")) . " UTC</b><br>
        == Start Condition =====================<br>
        <b>Addresses in DB at start</b>:       " . number_format($this->stats["pre-entities"], 0) . "<br>
        <b>Unique SAM ID's at start</b>:       " . number_format($this->stats["cache"], 0) . "<br>
        <b>Queue length at start</b>:          " . number_format($this->stats["queue"], 0) . " queued records.<br>
        == Queue Processing ====================<br>
        <b>New addresses created</b>:          " . number_format($this->stats["inserted"], 0) . "<br>
        <b>Updated addresses</b>:              " . number_format($this->stats["updated"], 0) . "<br>
        <b>Unchanged addresses</b>:            " . number_format($this->stats["unchanged"], 0) . "<br>
        <b>Duplicate SAM ID's skipped</b>:     " . number_format($this->stats["duplicateSAM"], 0) . "<br>
        == Result ==============================<br>
        <b>Addresses processed from queue</b>: " . number_format($this->stats["processed"], 0) . "<br>
        <b>Addresses in DB at end</b>:         " . number_format($this->stats["post-entities"], 0) . "<br>
        <b>Unique SAM ID's at end</b>:         " . number_format($cache_count, 0) . "<br>
        <b>Queue length at end</b>:            " . number_format($this->queue->numberOfItems(), 0) . " queued records.<br>
        == Runtime =============================<br>
        <b>Process duration</b>: " . gmdate("H:i:s", strtotime("now") - $this->stats["starttime"]) . "<br>
        <br><br>
    ";
    \Drupal::logger("bos_mnl")->info($output);

    $settings = \Drupal::configFactory()->getEditable('bos_mnl.settings');
    if ($this->queue->numberOfItems() != 0) {
      // If the queue is not fully processed, then save the temporary stats
      $settings->set('tmp_update', json_encode($this->stats))->save();
    }
    else {
      // Queue is completely processed, update the stats
      $settings->set('tmp_update', "[]")->save();
      $settings->set('last_mnl_update', $output)->save();
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
    $data_sam_record = json_encode($new_record["data"]);
    $data_sam_hash = hash("md5", $data_sam_record);
    $cache_sam_hash = $existing_record->field_checksum_value ?: "";
    $data_sam_address = $new_record["full_address"];
    $cache_sam_address = $existing_record->field_sam_address_value;

    if ($data_sam_hash != $cache_sam_hash || $data_sam_address != $cache_sam_address) {

      if ($data_sam_hash != $cache_sam_hash) {
        // The SAM data has changed - update data and checksum.
        MnlUtilities::MnlUpdateSamData($existing_record->nid, $data_sam_record, $data_sam_hash);
      }

      if ($data_sam_address != $cache_sam_address) {
        // The SAM Address has changed, update the address
        MnlUtilities::MnlUpdateSamAddress($existing_record->nid, $data_sam_address);
      }

      // Something changed, so update the lastupdated record.
      MnlUtilities::MnlUpdateSamDate($existing_record->nid);

      // Force a save to invalidate the Drupal cache for this node.
      MnlUtilities::MnlQueueInvalidation("node", $existing_record->nid);


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
   *   The current node from the mnl_cache.
   * @param array $new_record
   *   The imported data to insert.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createNode(&$existing_record, array $new_record) {

    $json_data = json_encode($new_record['data']);
    $md5 = hash("md5", $json_data);

    $node = MnlUtilities::MnlCreateSamNode($new_record, $json_data, $md5);

    // Add this new record to the mnl_cache.
    $existing_record->field_sam_id_value = $new_record['sam_address_id'];
    $existing_record->field_sam_address_value = $new_record['full_address'];
    $existing_record->field_checksum_value = $md5;
    $existing_record->nid = $node->id();

    $this->stats["inserted"]++;

  }

  /**
   * Process each queue record.
   */
  public function processItem($item) {

    $item = (array) $item;
    $existing_record = $this->mnl_cache[$item['sam_address_id']];

    if (!empty($existing_record->processed)) {
      $this->stats["duplicateSAM"]++;
    }
    else {

      if (!empty($existing_record)) {
        $this->updateNode($existing_record, $item);
      }
      else {
        $existing_record = $this->mnl_cache[$item['sam_address_id']] = new \stdClass();
        $this->createNode($existing_record, $item);
      }

      $existing_record->processed = TRUE;

    }

    $this->stats["processed"]++;

  }

}
