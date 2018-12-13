<?php

/**
 * @file
 * These are boston.gov local settings.
 * In private Repo.
 */

$settings['hash_salt'] = 'ivciasdbopasvbdcpasdiv';

// Be sure an environment indicator is set.
// Note: on Acquia servers this will be one of prod / test / dev as per:
/*  @see https://docs.acquia.com/acquia-cloud/develop/env-variable/ */
global $_envvar;
if (empty($_ENV['AH_SITE_ENVIRONMENT'])) {
  if (!empty(getenv('AH_SITE_ENVIRONMENT'))) {
    $_envvar = getenv('AH_SITE_ENVIRONMENT');
  }
}
else {
  $_envvar = $_ENV['AH_SITE_ENVIRONMENT'];
}
if (empty($_envvar)) {
  $_envvar = 'dev';
}

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
  //  You may manually set database here if not using Lando.
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

// Add in the development services config file.
if ($_envvar == "dev") {
  $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/development.services.yml';
  error_reporting(E_ALL);
  ini_set('display_errors', TRUE);
  ini_set('display_startup_errors', TRUE);
}

/*
 * Set the error trapping level.
 *   options: hide | some | all | verbose
 */
$config['system.logging']['error_level'] = ($_envvar == "dev" ? 'verbose' : 'hide');

/*
 * Include the Travis specific settings if building on Travis.
 */
if (file_exists('/home/travis/build')) {
  require DRUPAL_ROOT . '/sites/default/settings/settings.travis.php';
}

/*
 * Include the Acquia specific settings if present.
 */
// If this is on an aquia hosted server, a custom settings file will exist
// and will redefine the sql server parameters $databases['default']['default']
// and other acquia-specific configuration pairs.
if (file_exists('/var/www/site-php')) {
  $site = $_ENV['AH_SITE_NAME'];
  $siteEnv = $_ENV['AH_SITE_ENVIRONMENT'];
  $siteGroup = $_ENV['AH_SITE_GROUP'];
  require '/var/www/site-php/' . $site . '/D8-' . $site . '-common-settings.inc';
  require '/var/www/site-php/' . $site . '/D8-' . $siteEnv . '-' . $siteGroup . '-settings.inc';
}

/*
 * Phing will include the Private Repo settings if present.
 */
