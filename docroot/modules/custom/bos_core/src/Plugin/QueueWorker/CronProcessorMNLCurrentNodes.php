<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

/**
 * Processes import of nodes from queue.
 *
 * @QueueWorker(
 *   id = "mnl_nodes",
 *   title = @Translation("MNL remove any nodes not found on import."),
 *   cron = {"time" = 720}
 * )
 */
class CronProcessorMNLCurrentNodes extends MNLProcessCurrentNodes {}
