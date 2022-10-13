<?php

/**
 * @file
 * These are boston.gov travis-specific settings.
 * In private Repo.
 */

// Travis settings for errors.
error_reporting(0);
ini_set('display_errors', FALSE);
ini_set('display_startup_errors', FALSE);

/*
 *  Set the error trapping level.
 *   options: hide | some | all | verbose
 */
$config['system.logging']['error_level'] = 'hide';

/*
 * Probably not needed, but just in case we incorporate some automated testing
 * into Travis.
 */
$config['stage_file_proxy.settings']["origin"] = "https://d8-dev.boston.gov";

/*
 * Import the configurations for a limited set of modules.
 */
$config['config_split.config_split.travis']['status'] = TRUE;

/*
 * Configure databases for travis environments.
 */
$databases['default']['default'] = [
  'driver' => 'mysql',
  'database' => 'drupal',
  'username' => 'drupal',
  'password' => 'drupal',
  'host' => '127.0.0.1',
  'port' => '3306',
];
