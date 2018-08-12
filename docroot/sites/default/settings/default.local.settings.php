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

  // Be sure an environment indicator is set.
  // Note: on Acquia servers this will be one of prod / test / dev as per
  /*  @see https://docs.acquia.com/acquia-cloud/develop/env-variable/ */
  global $envvar;
  if (empty($_ENV['AH_SITE_ENVIRONMENT'])) {
    $envvar = getenv('AH_SITE_ENVIRONMENT');
  }
  else {
    $envvar = $_ENV['AH_SITE_ENVIRONMENT'];
  }
  if (empty($envvar)) {
    $envvar = 'dev';
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

  $settings['file_private_path'] = 'sites/default/files/private';

  // toggle config_split status
  if ( $env == "dev") {
    $config['config_split.config_split.development']['status'] = TRUE;
  }
  else {
    $config['config_split.config_split.development']['status'] = FALSE;
  }