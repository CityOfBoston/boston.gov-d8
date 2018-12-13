<?php

/**
 * @file
 * Defines the default settings for the website.
 * In public Repo.
 */

$settings['update_free_access'] = FALSE;

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$settings['entity_update_batch_size'] = 50;

if (file_exists(DRUPAL_ROOT . '/default/settings/settings.local.php')) {
  include DRUPAL_ROOT . '/default/settings/settings.local.php';
}
