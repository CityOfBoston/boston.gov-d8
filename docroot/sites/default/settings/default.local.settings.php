<?php

/**
 * @file
 * Local settings.
 */
  /*
   * BOSTON.GOV NOTE: Database array is set by Phing script in properties.xml
  */
  $config_directories[CONFIG_SYNC_DIRECTORY] = '../config';
  $settings['hash_salt'] = 'ivciasdbopasvbdcpasdiv';
  $_ENV['AH_SITE_ENVIRONMENT'] = 'dev';
  $config['environment_indicator.indicator']['bg_color'] = '#cf0000'; //Red
  $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
  $config['environment_indicator.indicator']['name'] = 'Development';
  $config['shield.settings']['credentials']['shield']['user'] = NULL;
  $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/development.services.yml';
