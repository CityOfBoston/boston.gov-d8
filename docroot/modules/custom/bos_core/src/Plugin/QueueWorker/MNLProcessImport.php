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
   * Build queue for existing MNL nodes.
   */
  public function existingNodes() {
    $nidsExisting = $this->getNodesNl();
    $queue_nodes = \Drupal::queue('mnl_nodes');
    foreach ($nidsExisting as $nid) {
      $queue_nodes->createItem($nid);
    }
  }

  /**
   * Add queue items (for use in deletion check later) of successfully imported MNL nodes.
   */
  public function addDeleteQueueItem($data) {
    $queue_delete = \Drupal::queue('mnl_delete');
    $queue_delete->createItem($data);
  }

  /**
   * Update node.
   */
  public function updateNode($nid, $dataJSON) {
    $entity = Node::load($nid);
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
      'field_sam_id'                => $dataJSON['sam_address_id'],
      'field_sam_address'           => $dataJSON['full_address'],
      'field_sam_neighborhood_data' => json_encode($dataJSON['data']),
    ]);
    $node->save();
  }

  /**
   * Process each record.
   */
  public function processItem($items) {
    // Check for end of import queue.
    // If so, trigger existing MNL queue creation.
    $queue = \Drupal::queue('mnl_import');
    if ($queue->numberOfItems() == 1) {
      $this->existingNodes();
    }

    $nidsNL = $this->getNodesNl();
    if (count($nidsNL) > 0) {
      foreach ($nidsNL as $nid) {
        $node = Node::load($nid);
        $sam_id = $node->field_sam_id->value;
        if ($sam_id == $items['sam_address_id']) {
          $this->updateNode($nid, $items);
          $this->addDeleteQueueItem($items['sam_address_id']);
          return;
        }
      }
    }

    $this->createNode($items);
    $this->addDeleteQueueItem($items['sam_address_id']);
  }

}
