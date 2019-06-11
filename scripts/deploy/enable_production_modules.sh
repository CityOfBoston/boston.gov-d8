#!/bin/bash

DRUSH="${1}"

# Enable modules.
${DRUSH} en paranoia,autologout,acquia_connector,acquia_purge -y
${DRUSH} en migrate_utilities -y

# Disable modules.
${DRUSH} pmu automated_cron,devel,masquerade,stage_file_proxy,twig_xdebug,config_devel,bos_migration,syslog,dblog,migrate_utilities -y