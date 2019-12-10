#!/bin/bash

  ###############################################################
  #  These commands need to be run as normal user from lando.yml.
  #
  #  These commands install Drupal, sync down a database from Acquia
  #  and update that Database with local & current repo settings.
  ###############################################################

  printf "\033[1;32m[lando]\033[1;33m Installing Drupal and dependencies in appserver & database containers ...\033[0m\n"

  # Install Phing so we can use it to run balance of build scripts.
  # Note: we want to build this from the home folder so it does not install composer.json/lock just yet.
  printf "\033[1;32m[lando]\033[1;33m Composer is installing phing ...\033[0m\n"
  cd ~ &&
    composer require phing/phing:2.* --no-suggest --no-scripts -q >> ${LANDO_MOUNT}/setup/composer.log

  # Next command hands off to Phing to complete the initial setup.
  printf "\033[1;32m[lando]\033[1;33m Now handing over to phing to build Drupal (\033[0;32mphing tasks annotated green\033[1;33m)...\033[0m\n"
  printf "\033[0;32m[Build Task] setup:docker:drupal-local\033[0m\n"
  cd $LANDO_MOUNT &&
    ~/vendor/phing/phing/bin/phing -f ${LANDO_MOUNT}/build.xml setup:docker:drupal-local
  printf "\033[1;32m[lando]\033[1;33m Build Finished;\033[0m\n"

  # Next command creates a link to enable a cli alias for phing. (Assumes Phing is in the repo's composer.json/lock file)
  if [ ! -e  /usr/local/bin/phing ]; then
    ln -s ${LANDO_MOUNT}/vendor/phing/phing/bin/phing /usr/local/bin/ >> ${LANDO_MOUNT}/setup/lando.log
  fi

  # Phing commands to complete the initial setup.  Output redirect so it can be printed at the end.
  # Capture the build info into a file to be printed at end of build process.
  cd $LANDO_MOUNT &&
    ./scripts/doit/branding.sh > ${LANDO_MOUNT}/setup/uli.log
  cd $LANDO_MOUNT &&
    phing -S -f ${LANDO_MOUNT}/build.xml update:user:setadminpwd >> ${LANDO_MOUNT}/setup/uli.log
  cd $LANDO_MOUNT &&
    phing -S -f ${LANDO_MOUNT}/build.xml update:user:loginadmin >> ${LANDO_MOUNT}/setup/uli.log
  cd $LANDO_MOUNT &&
    drush pmu acquia_connector &&
    drush pmu config_devel &&
    drush en config_devel >> ${LANDO_MOUNT}/setup/lando.log
  # Now setup the node container.
  printf "\033[1;32m[lando]\033[1;33m  Phing has finished building Drupal.\033[0m\n\n"
