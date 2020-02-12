<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

/**
 * A Node Publisher that publishes nodes on CRON run.
 *
 * @QueueWorker(
 *   id = "cron_manifest_processor",
 *   title = @Translation("Cron Icon Manifest Processor"),
 *   cron = {"time" = 20}
 * )
 */
class CronManifestProcessor extends IconManifestProcessBase {}
