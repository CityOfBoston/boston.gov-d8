<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

use Drupal;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;

/**
 * Processes MNL import queue.
 */
class MNLProcessImport extends QueueWorkerBase {

  /**
   * Get Neighborhood Lookup content type.
   */
  public function getNodesNl() {
    $query = \Drupal::entityQuery('node')->condition('type', 'neighborhood_lookup');
    $nids = $query->execute();
    return $nids;
  }

  /**
   * Build queue for current MNL nodes to compare and delete older records.
   */
  public function currentNodes() {
    $nidsExisting = $this->getNodesNl();
    $queue_nodes = \Drupal::queue('mnl_nodes');
    foreach ($nidsExisting as $nid) {
      $queue_nodes->createItem($nid);
    }
  }

  /**
   * Update node.
   */
  public function updateNode($nid, $dataJSON) {
    $entity = Node::load($nid);
    $entity->set('field_import_date', "1");
    $entity->set('field_sam_id', $dataJSON['sam_address_id']);
    $entity->set('field_sam_address', $dataJSON['full_address']);
    $entity->set('field_sam_neighborhood_data', json_encode($dataJSON['data']));
    $entity->save();
  }

  /**
   * Create new node.
   */
  public function createNode($dataJSON) {
    $node = Node::create([
      'type'                        => 'neighborhood_lookup',
      'title'                       => $dataJSON['sam_address_id'],
      'field_import_date'           => "1",
      'field_sam_id'                => $dataJSON['sam_address_id'],
      'field_sam_address'           => $dataJSON['full_address'],
      'field_sam_neighborhood_data' => json_encode($dataJSON['data']),
    ]);
    $node->save();
  }

  /**
   * Check if end of mnl_import queue.
   */
  public function checkEndQueue() {
    $queue = \Drupal::queue('mnl_import');
    $end_of_queue = ($queue->numberOfItems() == 1) ? $this->currentNodes() : NULL;
    return $end_of_queue;
  }

  /**
   * Process each queue record.
   */
  public function processItem($items) {
    $query = \Drupal::entityQuery('node')->condition('type', 'neighborhood_lookup')->condition('field_sam_id', $items['sam_address_id']);
    $nidsNL = $query->execute();

    if (count($nidsNL) > 0) {
      foreach ($nidsNL as $nid) {
        $node = Node::load($nid);
        $sam_id = $node->field_sam_id->value;
        if ($sam_id == $items['sam_address_id']) {
          $this->updateNode($nid, $items);
          $this->checkEndQueue();
          return;
        }
      }
    }

    $this->createNode($items);
    $this->checkEndQueue();
  }

}
