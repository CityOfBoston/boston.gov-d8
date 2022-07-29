<?php

namespace Drupal\bos_mnl\Controller;

use Exception;

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
  public static function MnlCacheClear(array $samid = []): int {

    $count = 0;
    $storage = \Drupal::entityTypeManager()->getStorage("node");

    // Remove duplicate SAMID's in list to be processed.
    $samid = array_unique($samid);

    // Convert SAMID's into node ID's
    if ($samid == []) {

      // Clear the cache for everything.
      $storage->resetCache();

      return $storage->getQuery()
        ->condition("type", "neighborhood_lookup")
        ->count()
        ->execute();
    }
    else {
      $nodes = $storage->getQuery()
        ->condition("type", "neighborhood_lookup")
        ->condition("field_sam_id", $samid, "IN")
        ->execute();
    }

    // Process in chunks of 1,000.
    foreach (array_chunk($nodes, 1000) as $chunk) {

      if (!empty($chunk) && count($chunk) >= 1) {

        try {
          $storage->resetCache($chunk);
          $count = $count + count($chunk);
        }
        catch (Exception $e) {
          $error++;
        }

      }
    }

    return $count;

  }

  /**
   * Purges (deletes) all SAM Records which have not been updated for more than
   *   the number of days provided.
   *
   * @param string $cutdate Description of period to use as cutoff.
   *   e.g. "2 days ago" will purge records with last updated date less (earlier)
   *   than 2 days ago.
   *
   * @return int|mixed|void Number of records purged.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public static function MnlCleanUp(string $cutdate = "2 days ago") {

    $count = 0;
    $storage = \Drupal::entityTypeManager()->getStorage("node");
    try {
      $unixdate = strtotime($cutdate);
    }
    catch (Exception $e) {
      \Drupal::logger("mnl_utility", "Could not evaluate date ${cutdate}");
      \Drupal::messenger()->addError("MNLUtilities: Could not evaluate date ${cutdate}");
      throw new Exception("MNLUtilities: Could not evaluate date ${cutdate} (strtotime)");
    }

    $nodes = $storage->getQuery()
      ->condition("type", "neighborhood_lookup")
      ->condition("field_updated_date", $unixdate, "<")
      ->execute();

    // Process in chunks of 1000.
    foreach (array_chunk($nodes, 1000) as $chunk) {
      if (!empty($chunk) && count($chunk) >= 1) {
        $entities = $storage->loadMultiple($chunk);
        $storage->delete($entities);
        $count = $count + count($chunk);
      }
    }

    return $count;
  }
}
