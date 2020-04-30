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
 * Processes MNL update queue.
 */
class MNLProcessUpdate extends QueueWorkerBase {

  /**
   * Update node.
   */
  public function updateNode($nid, $dataJSON) {
    $entity = Node::load($nid);
    $entity->set('field_import_date', "");
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
      'field_import_date'           => "",
      'field_sam_id'                => $dataJSON['sam_address_id'],
      'field_sam_address'           => $dataJSON['full_address'],
      'field_sam_neighborhood_data' => json_encode($dataJSON['data']),
    ]);
    $node->save();
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
          return;
        }
      }
    }

    $this->createNode($items);
  }

}
