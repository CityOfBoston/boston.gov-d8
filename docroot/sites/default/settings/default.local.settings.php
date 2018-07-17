<?php

/**
 * @file
 * Local settings.
 */
  /*
   * BOSTON.GOV NOTE: Database array is set by Phing script in setup.xml
   *    setup:drupal:local_settings_php:write
  */
  echo "aaa";
  $settings['hash_salt'] = 'ivciasdbopasvbdcpasdiv';

  // set an enviroment variable to denote the environment status.
  if (empty($_ENV['AH_SITE_ENVIRONMENT'])) {
    $_ENV['AH_SITE_ENVIRONMENT'] = 'loc';
  }

  switch ($_ENV['AH_SITE_ENVIRONMENT']) {
    case "loc":
      $env = [
        'name' => 'Local',
        'bg_color' => '#023e0a', //Green
        'fg_color' => '#ffffff',
      ];
      break;

    case 'dev':
      $env = [
        'name' => 'Development',
        'bg_color' => '#3e0202', //Red
        'fg_color' => '#ffffff',
      ];
      break;

    case 'stg':
      $env = [
        'name' => 'Staging',
        'bg_color' => '#505500', //Yellow
        'fg_color' => '#ffffff',
      ];
      // lock down site if it is tagged as staging.
      $settings['config_readonly'] = TRUE;
      break;

    case 'prd':
    default:
      $env = [
        'name' => 'Production',
        'bg_color' => '#303655', //blue
        'fg_color' => '#ffffff',
      ];
      // lock down site if it is tagged as production.
      $settings['config_readonly'] = TRUE;
      break;
  }
  echo "bbb";
  //   update the config if the environment variable has changed.
  $config = \Drupal::service('config.factory')->getEditable('environment_indicator.indicator');
  echo "*";
  if ($config->get('name') != $env['name']) {
    $config->set('name', $env['name']);
    $config->set('bg_color', $env['bg_color']);
    $config->set('fg_color', $env['fg_color']);
    $config->save();
  }
  echo "c";

  $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/development.services.yml';
  echo "d";

  if ($_SERVER['LANDO'] === 'ON') {
    echo "e";
    $lando_info = json_decode(getenv('LANDO_INFO'), TRUE);
//    $databases['default']['default'] = [
//      'driver' => 'mysql',
//      'database' => $lando_info['database']['creds']['database'],
//      'username' => $lando_info['database']['creds']['user'],
//      'password' => $lando_info['database']['creds']['password'],
//      'host' => $lando_info['database']['internal_connection']['host'],
//      'port' => $lando_info['database']['internal_connection']['port'],
//    ];
  }