<?php

/**
 * @file
 * Defines the default settings for the website.
 * In public Repo.
 */

/** Copied from default.settings.php Drupal v8.6.4 */

$settings['update_free_access'] = FALSE;

$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/services.yml';

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$settings['entity_update_batch_size'] = 50;

/* End of default.settings.php copy. */

/* Define and set an environment variable for prod/dev mode. */
global $_envvar;
$_envvar = "prod";

/* Include the default local settings file. */
if (file_exists(DRUPAL_ROOT . '/' . $site_path . '/settings/settings.local.php')) {
  include DRUPAL_ROOT . '/' . $site_path . '/settings/settings.local.php';
}

/* End of settings.php in main repo. */
$settings['install_profile'] = 'bos_profile';
