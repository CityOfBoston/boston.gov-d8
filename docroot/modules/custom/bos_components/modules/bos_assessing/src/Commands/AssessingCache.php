<?php

namespace Drupal\bos_assessing\Commands;

use Drupal\bos_pdfmanager\Controller\PdfManager;
use Drush\Commands\DrushCommands;

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
class AssessingCache extends DrushCommands {

  /**
   * Assessing PDF Generator: cache clear.
   *
   * @param string $parcelid
   *    A single ParcelID to clear cached PDF's for, or blank for all
   * @options arr
   *    Options
   * @options dry-run
   *    Do not delete anything, just report findings
   *
   * @validate-module-enabled bos_assessing
   *
   * @usage drush bos:assessing-clear-cache
   *   Clears all cached PDF's.
   * @usage drush bos:assessing-clear-cache <parcelID>
   *   Clear all cached PDF's for this parcel ID.
   * @usage drush bos:assessing-clear-cache ... --dry-run
   *   Do not delete anything, just report findings.
   *
   * @command bos:assessing-clear-cache
   * @aliases basscc,
   */
  public function cacheClear($parcelid = NULL, $options = ['dry-run' => FALSE]) {

    $cache = \Drupal::cache("assessing_pdf");
    $path = \Drupal::service('file_system')
      ->realpath(PdfManager::tempRoute());
    $del_all = empty($parcelid);

    if ($del_all) {
      $this->output()->writeln("Finding all cached PDF's for Assessing.");
      $parcelid = "(_[0-9]{10}\.(pdf|fdf|json))|(metadata_.*\.dat)";
    }
    else {
      $this->output()->writeln("Checking for cached PDF's for parcel {$parcelid}");
    }

    $delfiles = 0;
    $delcache = 0;

    $d = dir($path);
    while ($file = $d->read()) {
      if (preg_match("~{$parcelid}~", $file)) {
        // This is a physical file for this parcelid.
        if (substr($file, -3, 3) == "pdf") {
          // This is a pdf, so see if it's been cached.
          $cache_id = substr($file, 0, -4);
          if ($cache->get($cache_id)) {
            if ($this->output()->isVerbose() || $options["dry-run"]) {
              $this->output()->writeln("delete cache: {$cache_id}");
            }
            !$options["dry-run"] && $cache->delete($cache_id);
            $delcache++;
          }
        }
        if ($this->output()->isVerbose() || $options["dry-run"]) {
          $this->output()->writeln("deletes file: {$file}");
        }
        !$options["dry-run"] && unlink("{$path}/{$file}");
        $delfiles++;
      }
    }
    $d->close();

    if ($delfiles == 0) {
      $this->output()
        ->writeln("Nothing found in caches.");
    }
    else {
      $this->output()
        ->writeln(" - Removed {$delfiles} physical files (pdf's and working files)");
      $this->output()
        ->writeln(" - Removed {$delcache} drupal cache entries");
    }
  }
}
