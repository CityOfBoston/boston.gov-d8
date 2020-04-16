<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

use Drupal;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes tasks for example module.
 *
 * @QueueWorker(
 *   id = "mnl_delete",
 *   title = @Translation("MNL list of all current items.")
 * )
 */
class MNLDelete extends QueueWorkerBase {}
