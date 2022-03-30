<?php

namespace Drupal\bos_mnl\Controller;

use Drupal\Core\Cache\Cache;

/**
 * Class to provide various utilities used elsewhere in the module.
 */
class MnlUtilities {

  /**
   * This loads one or more neighborhood lookup nodes which have the provided
   * samids.  It then saves these nodes.
   * The action of loading and then saving the node clears the various caches,
   * including the low-level agressive json::api cache.
   *
   * @param $samid array An array of one or more SAM ID's.
   *                      If empty, then works on all SAMIDs in the DB.
   *
   * @return int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function MnlCacheClear(array $samid = []) {

    $count = 0;
    $storage = \Drupal::entityTypeManager()->getStorage("node");

    // Remove duplicate SAMID's.
    $samid = array_unique($samid);

    // Convert SAMID's into node ID's
    if ($samid == []) {
      $nodes = $storage->getQuery()
        ->condition("type", "neighborhood_lookup")
        ->execute();
    }
    else {
      $nodes = $storage->getQuery()
        ->condition("type", "neighborhood_lookup")
        ->condition("field_sam_id", $samid, "IN")
        ->execute();
    }

    // Process in chunks of 1,000.
    // The loadMultiple() fn is more efficient than loading a single node.
    foreach (array_chunk($nodes, 1000) as $chunk) {

      if (!empty($chunk) && count($chunk) >= 1) {

        // Load all the nodes in one swift movement ...
        $loaded = $storage->loadMultiple($chunk);

        // ... then save them one at a time ... :(
        foreach ($loaded as $node) {
          try {
            // Invalidate the cache for good measure (should cause varnish to clear
            // except the actual MNL entities (nodes) wont usually be cached).
            Cache::invalidateTags(["node:" . $node->id()]);
            $node->save();
            $count++;
          }
          catch (Exception $e) {
            $error++;
          }
        }
      }
    }

    return $count;

  }
}
