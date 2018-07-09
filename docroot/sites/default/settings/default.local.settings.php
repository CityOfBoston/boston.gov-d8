<?php

/**
 * @file
 * Local settings.
 */
  /*
   * BOSTON.GOV NOTE: Database array is set by Phing script in properties.xml
  */

  // make the default config storage for import/export ouside the docroot.
  $config_directories[CONFIG_SYNC_DIRECTORY] = '../config';

  $settings['hash_salt'] = 'ivciasdbopasvbdcpasdiv';

  // set an enviroment variable to denote the environment status.
  $_ENV['AH_SITE_ENVIRONMENT'] = 'loc'; $config['environment_indicator.indicator']['name'] = 'Local';
  $_ENV['AH_SITE_ENVIRONMENT'] = 'dev'; $config['environment_indicator.indicator']['name'] = 'Development';
  $_ENV['AH_SITE_ENVIRONMENT'] = 'stg'; $config['environment_indicator.indicator']['name'] = 'Staging';
  $_ENV['AH_SITE_ENVIRONMENT'] = 'prd'; $config['environment_indicator.indicator']['name'] = 'Production';

  // lock down site if it is tagged as production.
  if (isset($_ENV['AH_SITE_ENVIRONMENT']) && $_ENV['AH_SITE_ENVIRONMENT'] === 'prd') {
    $settings['config_readonly'] = TRUE;
  }

  // set the values for the environment_indicator module.
  $config['environment_indicator.indicator']['bg_color'] = '#cf0000'; //Red
  $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
  $config['shield.settings']['credentials']['shield']['user'] = NULL;

  $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/development.services.yml';
