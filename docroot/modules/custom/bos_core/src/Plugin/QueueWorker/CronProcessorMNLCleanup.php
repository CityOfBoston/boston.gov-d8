<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

/**
 * Processes import of nodes from queue.
 *
 * @QueueWorker(
 *   id = "mnl_cleanup",
 *   title = @Translation("MNL remove any nodes not found on import."),
 * )
 */
class CronProcessorMNLCleanup extends MNLProcessCleanup {}
