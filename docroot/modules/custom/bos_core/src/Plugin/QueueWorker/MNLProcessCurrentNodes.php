<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

use Drupal;
use Drupal\node\Entity\Node;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes / compares MNL current node queue with delete queue.
 */
class MNLProcessCurrentNodes extends QueueWorkerBase {

  /**
   * Process each record.
   */
  public function processItem($items) {

    // Load node.
    $node = Node::load($items);
    $sam_id = $node->field_sam_id->value;

    // Check if node exists in MNL import queue.
    $database = \Drupal::database();
    $sql = "SELECT data FROM queue WHERE name = 'mnl_delete' ";
    $result = $database->query($sql);
    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $getData = unserialize($row["data"]);
        $importSamId = $getData;
        if ($sam_id == $importSamId) {
          return;
        }
      }
    }

    $node->delete();
  }

}
