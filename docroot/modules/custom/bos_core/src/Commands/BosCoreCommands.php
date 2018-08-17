<?php

namespace Drupal\bos_core\Commands;

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
class BosCoreCommands extends DrushCommands {

  /**
   * Boston CSS Source Switcher. Set the source for the main public.css file.
   *
   * @param $ord The ordinal for the server (use 'drush bcss' for list)
   * @validate-module-enabled bos_core
   *
   * @command bos:css-source
   * @aliases bcss,bos-css-source
   */
  public function cssSource($ord = NULL) {
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details on what to change when porting a
    // legacy command.
    $libs = \Drupal::service('library.discovery')->getLibrariesByExtension('bos_theme');

    if (!isset($ord)) {
      $count = 0;
      $opts = "Boston CSS Source Switcher:\n Select server to switch to:\n\n";
      $opts .= " [" . $count++ . "]: Cancel\n";
      foreach ($libs as $libname => $lib) {
        if (!empty($lib['data']['name'])) {
          $opts .= " [" . $count++ . "]: " . $lib['data']['name'] . "\n";
        }
      }
      $ord = $this->io()->ask($opts, NULL);
    }

    $libArray = ["Cancel"];
    foreach ($libs as $libname => $lib) {
      $libArray[] = [
        $lib['data']['name'],
        $lib['remote'],
      ];
    }

    if ($ord == 0) {
      $this->output()->writeln("Cancelled.");
    }
    elseif (\Drupal\bos_core\BosCoreCssSwitcherService::switchSource($ord)) {
      \Drupal::service('asset.css.collection_optimizer')
        ->deleteAll();
      $this->output()->writeln("Success: Changed source to '" . $libArray[$ord][0]  . "' (" . $libArray[$ord][1] . ").");
    }
    else {
      $this->output()->writeln(t("FAILED: Counld not change source to '" . $libArray[$ord][0] . "' (" . $libArray[$ord][1] . ")."));
    }
  }

}
