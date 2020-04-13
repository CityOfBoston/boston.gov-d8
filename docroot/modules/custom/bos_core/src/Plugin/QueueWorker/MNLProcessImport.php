<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

use Drupal;
use Drupal\node\Entity\Node;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes tasks for example module.
 *
 * @QueueWorker(
 *   id = "mnl_import",
 *   title = @Translation("MNL Import records / nodes")
 * )
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
    $nidsNL = $this->getNodesNl();
    $exists = NULL;
    foreach ($nidsNL as $nid) {
      $node = Node::load($nid);
      $sam_id = $node->field_sam_id->value;
      if ($sam_id == $items['sam_address_id']) {
        $this->updateNode($nid, $items);
        return;
      }
    }

    $this->createNode($items);
  }

}
