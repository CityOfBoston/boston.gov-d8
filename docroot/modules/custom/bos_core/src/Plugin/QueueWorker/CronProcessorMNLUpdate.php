<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

/**
 * Processes update of nodes from queue.
 *
 * @QueueWorker(
 *   id = "mnl_update",
 *   title = @Translation("MNL Updates records / nodes."),
 *   cron = {"time" = 720}
 * )
 */
class CronProcessorMNLUpdate extends MNLProcessUpdate {}
