<?php

namespace Drupal\bos_core\Commands;

use Drush\Commands\DrushCommands;
use Drupal\bos_core\BosCoreCssSwitcherService;
use Symfony\Component\Yaml\Yaml;

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
   * @param string $ord
   *   The ordinal for the server (use 'drush bcss' for list)
   *
   * @validate-module-enabled bos_core
   *
   * @command bos:css-source
   * @aliases bcss,bos-css-source
   */
  public function cssSource($ord = NULL) {
    // See bottom of https://weitzman.github.io/blog/port-to-drush9 for details
    // on what to change when porting a legacy command.
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
    elseif (BosCoreCssSwitcherService::switchSource($ord)) {
      \Drupal::service('asset.css.collection_optimizer')
        ->deleteAll();
      $this->output()->writeln(t("Success: Changed source to '@source' (@sourcePath)."), [
        '@source' => $libArray[$ord][0],
        '@sourcePath' => $libArray[$ord][1],
      ]);
    }
    else {
      $this->output()->writeln(t("FAILED: Could not change source to '@source' (@sourcePath)."), [
        '@source' => $libArray[$ord][0],
        '@sourcePath' => $libArray[$ord][1],
      ]);
    }
  }

  /**
   * Automate common tasks related to bos componetization plan.
   *
   * @param string $module_name
   *   Machine name for output component module.
   * @param array $options
   *   Additional options for the command.
   *
   * @return string
   *   Stdout to console.
   *
   * @validate-module-enabled bos_core
   *
   * @command bos:componetize
   * @option components Comma seperated list of component machine names
   * @aliases componetize
   */
  public function componetize($module_name = NULL, array $options = ['components' => NULL]) {

    $module_path = \Drupal::service('file_system')->realpath(drupal_get_path('module', 'bos_components')) . '/modules/' . $module_name;
    if (file_exists($module_path)) {
      return 'This module directory already exists.';
    }
    else {
      mkdir($module_path);
    }

    $template_path = \Drupal::service('file_system')->realpath(drupal_get_path('module', 'bos_core')) . '/src/componentizer_templates';
    $template_files = array_diff(scandir($template_path), ['..', '.']);

    foreach ($template_files as $file) {
      $filename_parts = explode('.', $file);
      $filename_parts['0'] = $module_name;
      $new_filepath = $module_path . "/" . implode('.', $filename_parts);
      if (!copy($template_path . "/{$file}", $new_filepath)) {
        return 'File copy failed';
      }
      $file_contents = file_get_contents($new_filepath);
      $file_contents = str_replace("componentizer_template", $module_name, $file_contents);
      file_put_contents($new_filepath, $file_contents);
    }

    $module_config_dir = $module_path . '/config/install';
    mkdir($module_config_dir, 0777, TRUE);
    $site_install_directory = '/';
    foreach (explode('/', realpath(__FILE__)) as $part) {
      if ($part != 'docroot') {
        $site_install_directory .= $part;
      }
      else {
        break;
      }
    }

    $config_store_directory = $site_install_directory . '/config/default';
    // Adding to $this so we can access values in array_filter() callback.
    $this->components = explode(',', $options['components']);
    $component_configs = array_filter(scandir($config_store_directory), function ($file_name) {
      foreach ($this->components as $component) {
        if (preg_match("/\.($component)\./", $file_name)) {
          return TRUE;
        }
      }
      return FALSE;
    });

    $info_file_path = $module_path . "/{$module_name}.info.yml";
    $install_file = Yaml::parse(file_get_contents($info_file_path));
    $install_file['config_devel'] = [];

    foreach ($component_configs as $config_name) {
      copy($config_store_directory . "/{$config_name}", $module_config_dir . "/{$config_name}");
      $install_file['config_devel'][] = basename($config_name, '.yml');
    }

    file_put_contents($info_file_path, Yaml::dump($install_file, 2, 2));

    return 'Componentization complete!';
  }

  /**
   * Admin function to set the GA Measurement endpoint for REST tracking.
   *
   * @param string $endpoint
   *   The Google endpoint.
   *
   * @return string
   *   Message to stdout in console.
   *
   * @validate-module-enabled bos_core
   *
   * @command bos:google-measurement-endpoint
   * @aliases bgme
   */
  public function gaEndpoint($endpoint = NULL) {
    if ($endpoint == NULL) {
      return "PROBLEM: Please supply a new endpoint.";
    }
    $config = \Drupal::configFactory()
      ->getEditable("bos_core.settings");
    $settings = $config->get("ga_settings");
    $settings["ga_endpoint"] = $endpoint;
    $config->set("ga_settings", $settings)
      ->save();
    return 'SUCCESS: Google Analytics/Measurement endpoint changed to ' . $endpoint;
  }

  /**
   * Admin function to disable REST GA Measurement/Tracking.
   *
   * @param string $enabled
   *   TRUE/FALSE to set REST hit tracking state.
   *
   * @return string
   *   Message to stdout in console.
   *
   * @validate-module-enabled bos_core
   *
   * @command bos:google-measurement
   * @aliases bgm
   */
  public function gaEnable(string $enabled = NULL) {
    if ($enabled == NULL) {
      return "PROBLEM: Please supply the enabled/disabled state.";
    }
    $config = \Drupal::configFactory()
      ->getEditable("bos_core.settings");
    $settings = $config->get("ga_settings");
    $settings["ga_enabled"] = ($enabled == TRUE);
    $config->set("ga_settings", $settings)
      ->save();
    return 'SUCCESS: Google Analytics tracking for REST calls is now ' . ($enabled == TRUE ? "ON" : "OFF");
  }

}
