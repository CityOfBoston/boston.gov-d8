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
    $import_date = $node->field_import_date->value;

    if ($import_date == "1") {
      $node->set('field_import_date', NULL);
      $node->save();
      return;
    }

    $node->delete();
  }

}
