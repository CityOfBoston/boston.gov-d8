<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

use Drupal;
use Drupal\node\Entity\Node;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes MNL import queue.
 */
class MNLProcessImport extends QueueWorkerBase {

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
      ->info("[1] MNL Import Worker initialized.");
    $this->count = 0;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function __destruct() {
    // Check if queue is empty.
    if ($this->endQueue()) {
      // The import queue is now empty - so now queue up
      // items that were not present in the import, and need to be deleted.
      if ($this->loadGarbageNodes()) {
        \Drupal::logger("mnl import")
          ->info("[1] Worker destroyed and MNL Import IS complete. Processed " . $this->count . " neighborhood_lookup entities.");
      }
      else {
        \Drupal::logger("mnl import")
          ->info("[1] Worker destroyed but MNL Import NOT complete. Processed " . $this->count . " neighborhood_lookup entities.");
      }
    }
  }

  /**
   * Get Neighborhood Lookup content type.
   */
  private function getUnwantedNodes() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'neighborhood_lookup')
      ->condition('field_import_date', "0", "=");
    $nids = $query->execute();
    return $nids;
  }

  /**
   * Build queue for current MNL nodes to compare and delete older records.
   */
  private function loadGarbageNodes() {
    $queue_nodes = \Drupal::queue('mnl_cleanup');
    if ($queue_nodes->numberOfItems() == 0) {
      \Drupal::logger("mnl import")->info("[1] MNL Import complete.");
      $nidsUnwanted = $this->getUnwantedNodes();
      if (empty($nidsUnwanted)) {
        // Reset the import flag field on all current neighborood lookup nodes.
        \Drupal::logger("mnl import")
          ->info("[1] No neighborhood_lookup entities found for cleanup - Resetting import flag.");
        $result = \Drupal::database()->update("node__field_import_date")
          ->fields(["field_import_date_value" => "0"])
          ->execute();
        \Drupal::logger("mnl import")
          ->info("[1] Import flag reset on $result neighborhood_lookup entities.");
      }
      else {
        foreach ($nidsUnwanted as $nid) {
          $queue_nodes->createItem($nid);
        }
        \Drupal::logger("mnl import")
          ->info("[1] Found " . count($nidsUnwanted) . " old neighborhood_lookup entities for cleanup (and loaded them into cleanup queue).");
      }
    }
    else {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Update node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The neighborhood lookup node.
   * @param array $data
   *   The import data object.
   */
  private function updateNode(Node $node, array $data) {
    $data_sam_address = $data["full_address"];
    $node_sam_address = $node->get("field_sam_address")->value;
    $data_sam_record = json_encode($data["data"]);
    $node_sam_record = $node->get("field_sam_neighborhood_data")->value;
    $nid = $node->id();

    if ($data_sam_record != $node_sam_record) {
      \Drupal::database()->update("node__field_sam_neighborhood_data")
        ->condition("entity_id", $nid)
        ->fields(["field_sam_neighborhood_data_value" => $data_sam_record])
        ->execute();
    }
    if ($data_sam_address != $node_sam_address) {
      \Drupal::database()->update("node__field_sam_address")
        ->condition("entity_id", $nid)
        ->fields(["field_sam_address_value" => $data_sam_address])
        ->execute();
    }

    $result = \Drupal::database()->merge("node__field_import_date")
      ->key("entity_id", $nid)
      ->fields([
        "bundle" => "neighborhood_lookup",
        "deleted" => 0,
        "revision_id" => $node->getRevisionId(),
        "delta" => 0,
        "field_import_date_value" => "1",
        "langcode" => "en",
      ])
      ->execute();
  }

  /**
   * Create new node.
   *
   * @param array $data
   *   The imported data to insert.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createNode(array $data) {
    $node = Node::create([
      'type'                        => 'neighborhood_lookup',
      'title'                       => $data['sam_address_id'],
      'field_import_date'           => "1",
      'field_sam_id'                => $data['sam_address_id'],
      'field_sam_address'           => $data['full_address'],
      'field_sam_neighborhood_data' => json_encode($data['data']),
    ]);
    $node->save();
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
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'neighborhood_lookup')
      ->condition('field_sam_id', $item['sam_address_id'])
      ->execute();

    // Create variable for duplicates.
    $handled = FALSE;

    if (count($nids) > 0) {
      foreach ($nids as $nid) {
        if (NULL != ($node = Node::load($nid))) {
          if (!$handled) {
            $sam_id = $node->get("field_sam_id")->value;
            if ($sam_id == $item['sam_address_id']) {
              $this->updateNode($node, $item);
              $handled = TRUE;
            }
          }
          else {
            // This is a duplicate sam_id.  Thats not possible, so remove it.
            $node->delete();
          }
        }
      }
    }
    else {
      $this->createNode($item);
    }

    $this->count++;

  }

}
