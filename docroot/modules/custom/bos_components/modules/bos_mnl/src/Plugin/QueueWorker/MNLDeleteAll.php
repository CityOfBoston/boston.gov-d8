<?php

namespace Drupal\bos_mnl\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    ini_set('memory_limit', '-1');
    $this->queue = \Drupal::queue($plugin_id);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Process each record.
   *
   * @param mixed $item
   *   The item stored in the qsueue.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($item) {
    // Load and delete node.
    \Drupal::entityTypeManager()
      ->getStorage("node")
      ->load($item)
      ->delete();

  }

}
