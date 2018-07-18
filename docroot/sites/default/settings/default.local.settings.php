<?php

/**
 * @file
 * Local settings.
 */
  /*
   * BOSTON.GOV NOTE: Database array is set by Phing script in setup.xml
   *    setup:drupal:local_settings_php:write
  */
  $settings['hash_salt'] = 'ivciasdbopasvbdcpasdiv';

  // set an enviroment variable to denote the environment status.
  if (empty($_ENV['AH_SITE_ENVIRONMENT'])) {
    $_ENV['AH_SITE_ENVIRONMENT'] = 'dev';
  }

  $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/development.services.yml';

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
