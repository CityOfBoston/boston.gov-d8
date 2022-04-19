<?php

namespace Drupal\bos_mnl\Commands;

use Drush\Commands\DrushCommands;
use Drupal\bos_mnl\Controller\MnlUtilities;


/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class MnlDrushCommands extends DrushCommands {

  /**
   * Boston CSS Source Switcher. Set the source for the main public.css file.
   *
   * @param string $samid
   *   The SAM ID to clear, or blank for all
   *
   * @validate-module-enabled bos_mnl
   *
   * @command bos:mnl-clear-cache
   * @aliases bmnlcc,
   */
  public function cacheClear($samid = NULL) {

    if (empty($samid)) {
      $storage = \Drupal::entityTypeManager()->getStorage("node");
      $count = $storage->getQuery()
        ->condition("type", "neighborhood_lookup")
        ->count()
        ->execute();

      $check = "Are you sure you want to update all " . number_format($count, 0, ".",",") . " SAM Records (Y/N) ?";
      $check = $this->io()->ask($check, NULL);
      if (strtoupper($check) != "Y") {
        $this->output()->writeln("Cancelled.");
        return;
      }

      $samid = [];
    }
    elseif (is_string($samid)) {
      $samid = preg_split("/[, ]/", $samid);
    }
    if (!is_array($samid)) {
      $samid = [trim($samid)];
    }

    $affected = MnlUtilities::MnlCacheClear($samid);

    $this->output()->writeln("Caches for " . $affected . " SAM Data records were refreshed.");


  }
}
