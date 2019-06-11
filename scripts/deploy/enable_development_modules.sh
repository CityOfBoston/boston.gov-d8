#!/bin/bash

DRUSH="${1}"

# Enable modules.
${DRUSH} en automated_cron,devel,masquerade,migrate_drupal,migrate_source_csv,migrate_upgrade,migrate_utilities,stage_file_proxy,syslog,twig_xdebug,config_devel  -y
${DRUSH} en migrate_drupal_ui -y
${DRUSH} en bos_migration -y

# Disable modules.
${DRUSH} pmu paranoia,autologout,acquia_connector,acquia_purge -y
