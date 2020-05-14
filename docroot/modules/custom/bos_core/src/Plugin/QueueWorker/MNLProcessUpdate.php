<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

use Drupal;
use Drupal\node\Entity\Node;
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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    ini_set('memory_limit', '-1');
    ini_set("max_execution_time", "0");
    $this->queue = \Drupal::queue($this->getPluginId());
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Update node.
   */
  private function updateNode($node, $data) {
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
   */
  private function createNode($data) {
    $node = Node::create([
      'type'                        => 'neighborhood_lookup',
      'title'                       => $data['sam_address_id'],
      'field_import_date'           => "",
      'field_sam_id'                => $data['sam_address_id'],
      'field_sam_address'           => $data['full_address'],
      'field_sam_neighborhood_data' => json_encode($data['data']),
    ]);
    $node->save();
  }

  /**
   * Process each queue record.
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
  }

}
