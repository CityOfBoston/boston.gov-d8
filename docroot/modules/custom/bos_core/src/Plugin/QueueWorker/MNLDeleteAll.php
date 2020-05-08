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
 *   id = "mnl_delete_all",
 *   title = @Translation("MNL deletes all nodes outright with no conditions.")
 * )
 */
class MNLDeleteAll extends QueueWorkerBase {

  /**
   * Process each record.
   */
  public function processItem($items) {

    // Load and delete node.
    $node = Node::load($items);
    $sam_id = $node->field_sam_id->value;
    $node->delete();
  }

}
