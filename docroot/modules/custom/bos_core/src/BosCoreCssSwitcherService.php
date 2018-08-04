<?php

namespace Drupal\bos_core;

/**
 * Class BosCoreCssSwitcherService
 *
 *    Switches the source for the main site css file(s)
 *    The library selection is from: themes/custom/bos_theme/bos_theme.libraries.yml
 *    Drush command to switch is: drush bos:css-source (alias bcss)
 *                                modules/custom/bos_core/src/Commands/BosCoreCommands.php
 *
 * @package Drupal\bos_core
 */
class BosCoreCssSwitcherService {

  static function switchSource($ord = 0) {
    if ($ord == 0) return TRUE;

    $libs = \Drupal::service('library.discovery')->getLibrariesByExtension('bos_theme');
    $opts = array("Cancel");
    foreach ($libs as $libname => $lib) {
      $opts[] = $libname;
    }
    if (!empty($opts[$ord])) {
      \Drupal::logger('bos-core')->info("Switching CSS source to: '@this'", ["@this" => $opts[$ord]]);
      \Drupal::configFactory()->getEditable("bos_theme.settings")->set("asset_source", $opts[$ord])->save();
    }
    else {
      return FALSE;
    }
  }

}