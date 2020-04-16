<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

/**
 * Processes import of nodes from queue.
 *
 * @QueueWorker(
 *   id = "mnl_import",
 *   title = @Translation("MNL Import records / nodes."),
 *   cron = {"time" = 600}
 * )
 */
class CronProcessorMNLImport extends MNLProcessImport {}
