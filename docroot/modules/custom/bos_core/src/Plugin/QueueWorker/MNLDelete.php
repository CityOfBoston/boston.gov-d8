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
 *   id = "mnl_delete",
 *   title = @Translation("MNL deletes any nodes not found in import queue.")
 * )
 */
class MNLDelete extends QueueWorkerBase {

  /**
   * Process each record.
   */
  public function processItem($items) {

    // Load node.
    $node = Node::load($items);
    $sam_id = $node->field_sam_id->value;

    // Check if node exists in MNL import queue.
    $database = \Drupal::database();
    $sql = "SELECT data FROM queue WHERE name = 'mnl_import' ";
    $result = $database->query($sql);
    if ($result) {
      while ($row = $result->fetchAssoc()) {
        $getData = unserialize($row["data"]);
        $importSamId = $getData["sam_address_id"];
        if ($sam_id == $importSamId) {
          return;
        }
      }
    }

    $node->delete();
  }

}
