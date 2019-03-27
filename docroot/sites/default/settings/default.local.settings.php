<?php

/**
 * @file
 * Local settings.
 *
 * BOSTON.GOV NOTE: Database array is set by Phing script in setup.xml.
 * @see setup:drupal:local_settings_php:write
 */

$settings['hash_salt'] = 'ivciasdbopasvbdcpasdiv';

if (!empty($_SERVER['LANDO']) && $_SERVER['LANDO'] === 'ON') {
  $lando_info = json_decode(getenv('LANDO_INFO'), TRUE);
  $databases['default']['default'] = [
    'driver' => 'mysql',
    'database' => $lando_info['database']['creds']['database'],
    'username' => $lando_info['database']['creds']['user'],
    'password' => $lando_info['database']['creds']['password'],
    'host' => $lando_info['database']['internal_connection']['host'],
    'port' => $lando_info['database']['internal_connection']['port'],
  ];
}
else {
  $databases['default']['default'] = [
    'driver' => 'mysql',
    'database' => 'drupal',
    'username' => 'drupal',
    'password' => 'drupal',
    'host' => 'database',
    'port' => '3306',
  ];
}

$settings['file_private_path'] = 'sites/default/files/private';

/*
 * Add PHP memory limit allocation for this web site.
 * @see  https://www.drupal.org/docs/7/managing-site-performance-and-scalability/changing-php-memory-limits
 */
ini_set('memory_limit', '384M');
