<?php

namespace Drupal\bos_migration;

use Drupal\Component\Utility\Bytes;

/**
 * Utility functions for memory management - useful in migrations & cron tasks.
 *
 * @package Drupal\bos_migration
 */
trait MemoryManagementTrait {

  /**
   * The ratio of the memory limit at which an operation will be interrupted.
   *
   * @var float
   */
  protected $memoryThreshold = 0.85;

  /**
   * The PHP memory_limit expressed in bytes.
   *
   * @var int
   */
  protected $memoryLimit = NULL;

  /**
   * Checks for exceptional conditions, and display feedback.
   */
  protected function checkStatus() {
    // Record the memory limit in bytes.
    if ($this->memoryLimit == NULL) {
      $limit = trim(ini_get('memory_limit'));
      if (empty($limit)) {
        // Set limit to 512 because we have nothing.
        $this->memoryLimit = 512 * Bytes::KILOBYTE * Bytes::KILOBYTE;
      }
      elseif ($limit == '-1') {
        // Set limit to 512 even though we have unlimited.
        $this->memoryLimit = 512 * Bytes::KILOBYTE * Bytes::KILOBYTE;
      }
      else {
        $this->memoryLimit = Bytes::toInt($limit);
      }
      printf("[info] Memory Maximum usage set to %d MB\n", Bytes::toInt(($this->memoryLimit / Bytes::KILOBYTE) / Bytes::KILOBYTE));
    }

    if ($this->memoryExceeded()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Quickly clear the entity cache for a specific entity.
   *
   * @param string $id
   *   The entity type id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function clearEntityCache(string $id) {
    \Drupal::entityTypeManager()->getStorage($id)->resetCache();
  }

  /**
   * Returns the memory usage so far.
   *
   * @return int
   *   The memory usage.
   */
  protected function getMemoryUsage() {
    return memory_get_usage();
  }

  /**
   * Tests whether we've exceeded the desired memory threshold.
   *
   * If so, output a message.
   *
   * @return bool
   *   TRUE if the threshold is exceeded, otherwise FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function memoryExceeded() {
    $usage = $this->getMemoryUsage();
    $pct_memory = $usage / $this->memoryLimit;
    if (!$threshold = $this->memoryThreshold) {
      return FALSE;
    }
    if ($pct_memory > $threshold) {
      \Drupal::messenger()->addWarning(
        t('Memory usage is @usage (@pct% of limit @limit), reclaiming memory.',
          [
            '@pct' => round($pct_memory * 100),
            '@usage' => $this->formatSize($usage),
            '@limit' => $this->formatSize($this->memoryLimit),
          ]
        )
      );
      $usage = $this->attemptMemoryReclaim();
      $pct_memory = $usage / $this->memoryLimit;
      // Use a lower threshold -we don't want to be in a situation where we keep
      // coming back here and trimming a tiny amount.
      if ($pct_memory > (0.90 * $threshold)) {
        \Drupal::messenger()->addWarning(
          t(
            "Memory usage is now @usage (@pct% of limit @limit), not enough reclaimed, starting new batch",
            [
              '@pct' => round($pct_memory * 100),
              '@usage' => $this->formatSize($usage),
              '@limit' => $this->formatSize($this->memoryLimit),
            ]
          )
        );
        return TRUE;
      }
      else {
        \Drupal::messenger()->addStatus(
          t(
            'Memory usage is now @usage (@pct% of limit @limit), reclaimed enough, continuing',
            [
              '@pct' => round($pct_memory * 100),
              '@usage' => $this->formatSize($usage),
              '@limit' => $this->formatSize($this->memoryLimit),
            ]
          )
        );
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * Tries to reclaim memory.
   *
   * @return int
   *   The memory usage after reclaim.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function attemptMemoryReclaim() {
    // First, try resetting Drupal's static storage - this frequently releases
    // plenty of memory to continue.
    drupal_static_reset();

    // Entity storage can blow up with caches so clear them out.
    $manager = \Drupal::entityTypeManager();
    foreach ($manager->getDefinitions() as $id => $definition) {
      $manager->getStorage($id)->resetCache();
    }

    // @TODO: explore resetting the container.

    // Run garbage collector to further reduce memory.
    gc_collect_cycles();

    return memory_get_usage();
  }

  /**
   * Generates a string representation for the given byte count.
   *
   * @param int $size
   *   A size in bytes.
   *
   * @return string
   *   A translated string representation of the size.
   */
  protected function formatSize($size) {
    return format_size($size);
  }

}
