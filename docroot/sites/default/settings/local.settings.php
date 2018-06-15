<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Local development override configuration feature.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/default/settings.local.php'. Then, go to the bottom of
 * 'sites/default/settings.php' and uncomment the commented lines that mention
 * 'settings.local.php'.
 *
 * If you are using a site name in the path, such as 'sites/example.com', copy
 * this file to 'sites/example.com/settings.local.php', and uncomment the lines
 * at the bottom of 'sites/example.com/settings.php'.
 */

///**
// * Assertions.
// *
// * The Drupal project primarily uses runtime assertions to enforce the
// * expectations of the API by failing when incorrect calls are made by code
// * under development.
// *
// * @see http://php.net/assert
// * @see https://www.drupal.org/node/2492225
// *
// * If you are using PHP 7.0 it is strongly recommended that you set
// * zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
// * or runtime) on development machines and to 0 in production.
// *
// * @see https://wiki.php.net/rfc/expectations
// */
//assert_options(ASSERT_ACTIVE, TRUE);
//\Drupal\Component\Assertion\Handle::register();
//
///**
// * Enable local development services.
// */
//$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';
//
///**
// * Show all error messages, with backtrace information.
// *
// * In case the error level could not be fetched from the database, as for
// * example the database connection failed, we rely only on this value.
// */
//$config['system.logging']['error_level'] = 'verbose';
//
///**
// * Disable CSS and JS aggregation.
// */
//$config['system.performance']['css']['preprocess'] = FALSE;
//$config['system.performance']['js']['preprocess'] = FALSE;
//
///**
// * Disable the render cache.
// *
// * Note: you should test with the render cache enabled, to ensure the correct
// * cacheability metadata is present. However, in the early stages of
// * development, you may want to disable it.
// *
// * This setting disables the render cache by using the Null cache back-end
// * defined by the development.services.yml file above.
// *
// * Only use this setting once the site has been installed.
// */
//# $settings['cache']['bins']['render'] = 'cache.backend.null';
//
///**
// * Disable caching for migrations.
// *
// * Uncomment the code below to only store migrations in memory and not in the
// * database. This makes it easier to develop custom migrations.
// */
//# $settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';
//
///**
// * Disable Internal Page Cache.
// *
// * Note: you should test with Internal Page Cache enabled, to ensure the correct
// * cacheability metadata is present. However, in the early stages of
// * development, you may want to disable it.
// *
// * This setting disables the page cache by using the Null cache back-end
// * defined by the development.services.yml file above.
// *
// * Only use this setting once the site has been installed.
// */
//# $settings['cache']['bins']['page'] = 'cache.backend.null';
//
///**
// * Disable Dynamic Page Cache.
// *
// * Note: you should test with Dynamic Page Cache enabled, to ensure the correct
// * cacheability metadata is present (and hence the expected behavior). However,
// * in the early stages of development, you may want to disable it.
// */
//# $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
//
///**
// * Allow test modules and themes to be installed.
// *
// * Drupal ignores test modules and themes by default for performance reasons.
// * During development it can be useful to install test extensions for debugging
// * purposes.
// */
//# $settings['extension_discovery_scan_tests'] = TRUE;
//
///**
// * Enable access to rebuild.php.
// *
// * This setting can be enabled to allow Drupal's php and database cached
// * storage to be cleared via the rebuild.php page. Access to this page can also
// * be gained by generating a query string from rebuild_token_calculator.sh and
// * using these parameters in a request to rebuild.php.
// */
//$settings['rebuild_access'] = TRUE;
//
///**
// * Skip file system permissions hardening.
// *
// * The system module will periodically check the permissions of your site's
// * site directory to ensure that it is not writable by the website user. For
// * sites that are managed with a version control system, this can cause problems
// * when files in that directory such as settings.php are updated, because the
// * user pulling in the changes won't have permissions to modify files in the
// * directory.
// */
//$settings['skip_permissions_hardening'] = TRUE;

  /**
   * Database settings:
   *
   * The $databases array specifies the database connection or
   * connections that Drupal may use.  Drupal is able to connect
   * to multiple databases, including multiple types of databases,
   * during the same request.
   *
   * One example of the simplest connection array is shown below. To use the
   * sample settings, copy and uncomment the code below between the @code and
   * @endcode lines and paste it after the $databases declaration. You will need
   * to replace the database username and password and possibly the host and port
   * with the appropriate credentials for your database system.
   *
   * The next section describes how to customize the $databases array for more
   * specific needs.
   *
   * @code
   * $databases['default']['default'] = array (
   *   'database' => 'databasename',
   *   'username' => 'sqlusername',
   *   'password' => 'sqlpassword',
   *   'host' => 'localhost',
   *   'port' => '3306',
   *   'driver' => 'mysql',
   *   'prefix' => '',
   *   'collation' => 'utf8mb4_general_ci',
   * );
   * @endcode
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

/*if ($_SERVER['LANDO'] === 'ON') {
  $lando_info = json_decode(getenv('LANDO_INFO'), TRUE);
  $databases['default']['default'] = [
    'driver' => 'mysql',
    'database' => $lando_info['database']['creds']['database'],
    'username' => $lando_info['database']['creds']['user'],
    'password' => $lando_info['database']['creds']['password'],
    'host' => $lando_info['database']['internal_connection']['host'],
    'port' => $lando_info['database']['internal_connection']['port'],
  ];
}*/
