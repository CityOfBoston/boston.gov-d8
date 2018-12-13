<?php

/**
 * @file
 * Local PRIVATE settings.
 * In Private repo.
 */

/*
 * BOSTON.GOV NOTE: This file is intended to be stored in a private repo.
 *    It should be used to store confidential, secure or sensitive data
 *    such as passwords or protected URLs.
 */

/*
  Used by Migrate module to connect to D7 database.
  NOTE:
    - Requires an SSH tunnel to the legacy MySQL server which can usually
      be achieved with a a command similar to:
        ssh -4 -L 3336:127.0.0.1:3306 boston.dev@xxx.prod.hosting.acquia.com -N
      Once established, a test connection can be established with the DB thus:
        mysql -us16784 -p -P 3336 -h 127.0.0.1
 */

$databases['migrate']['default'] = array(
  'database' => 'bostondev',
  'username' => 's16784',
  'password' => 'piPhfQef9PZ5WDu',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '3336',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
