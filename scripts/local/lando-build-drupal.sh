#!/bin/bash

  ###############################################################
  #  These commands need to be run as normal user from lando.yml.
  #
  #  These commands install Drupal, sync down a database from Acquia
  #  and update that Database with local & current repo settings.
  ###############################################################

  printout "INFO" "Installing Drupal and dependencies in appserver & database containers."

  # Include the utilities file/library.
  . "/app/scripts/local/lando_utilities.sh"

  # Read in config and variables.
  eval $(parse_yaml "${LANDO_MOUNT}/scripts/local/.config.yml" "")
  eval $(parse_yaml "${LANDO_MOUNT}/.lando.yml" "lando_")

  SETUP_LOGS="${LANDO_MOUNT}/setup/"

  # Next command hands off to Phing to complete the initial setup.
  cd ${LANDO_MOUNT} &&
    ~/vendor/phing/phing/bin/phing -f ${LANDO_MOUNT}/build.xml setup:docker:drupal-local

  if [ -z ${LANDO_MOUNT}/setup ]; then mkdir -P ${LANDO_MOUNT}/setup; fi

  if [ -z ${project_docroot}/core/lib/Drupal.php ]; then
    printout "WARNING" "Drupal is already installed." "This will not be a 'clean' build."
    printout " " "" "Composer Install will still run and may update existing files."
  fi

  # Install composer - using lock file if present.
  printout "INFO" "see ${SETUP_LOGS}/composer.log for output." "(or https://${LANDO_APP_NAME}.${LANDO_DOMAIN}/sites/default/files/setup/composer.log)"
  printout "INFO" "Executes: > composer install --prefer-dist --no-suggest --no-interaction"
  echo "Executes: > composer install --prefer-dist --no-suggest --no-interaction" > ${SETUP_LOGS}/composer.log
  cd ${LANDO_MOUNT} &&
    composer install --no-suggest --prefer-dist --no-interaction >> ${SETUP_LOGS}/composer.log &&
    composer drupal:scaffold >> ${SETUP_LOGS}/composer.log &&
    echo "DONE." >> ${SETUP_LOGS}/composer.log &&
    printout "SUCCESS" "Composer has loaded Drupal core, contrib modules and third-party packages/libraries."

  # Clone the private repo and merge with the main repo.
  clone_private_repo

  # Install Drupal.
  echo "==== Installing Drupal ===========" > ${SETUP_LOGS}/drush_site_install.log
  if [[ "${build_local_database_source}" == "initialize" ]]; then

    printout "INFO" "Build is now using settings file ${local_settings_file}"
    printout "" "" "... with ${lando_services_database_type=mysql} database '${lando_services_database_creds_database}' on '${lando_services_database_host}:${lando_services_database_portforward}' in container '${LANDO_APP_PROJECT}_database_1'"
    printout "INFO" "see ${SETUP_LOGS}/drush_site_install.log for output." "(or https://${LANDO_APP_NAME}.${LANDO_DOMAIN}/sites/default/files/setup/drush_site_install.log)"

    SITE_INSTALL=" site-install ${project_profile_name} \
      --db-url=${lando_services_database_creds_database}://${lando_services_database_creds_user}:${lando_services_database_creds_password}@${build_local_database_host}:${build_local_database_port}/${lando_services_database_creds_database} \
      --site-name=${lando_name} \
      --site-mail=${drupal_account_mail} \
      --account-name=${drupal_account_name} \
      --account-pass=${drupal_account_password} \
      --account-mail=${drupal_account_mail} \
      --sites-subdir=${drupal_multisite_name} \
      -vvv \
      -y"

    printout "INFO" "Installing Drupal with an initial database containing no content."
    echo "Executing: ${SITE_INSTALL}" >> ${SETUP_LOGS}/drush_site_install.log
    ${drush_cmd} ${SITE_INSTALL} >> ${SETUP_LOGS}/drush_site_install.log

    # If it failed then alert.
    if [[ $? -eq 0 ]]; then
        printout "SUCCESS" "Site is freshly installed with clean database."
    else
        printout "ERROR" "Fail - Site install failure" "Check ${SETUP_LOGS}/drush_site_install.log for issues."
        exit 0
    fi

    # If the system.site.yml has a UUID specified, then use that.
    if [[ -s ${LANDO_MOUNT}/config/default/system.site.yml ]]; then
        # Fetch site UUID from the configs in the (newly made) database.
        db_uuid=$(${drush_cmd} cget "system.site" uuid | grep -Eo "\s[0-9a-h\-]*")
        # Fetch the site UUID from the configuration file.
        yml_uuid=$(cat ${LANDO_MOUNT}/config/default/system.site.yml | grep "uuid:" | grep -Eo "\s[0-9a-h\-]*")

        if [[ "${db_uuid}" != "${yml_uuid}" ]]; then
            # The config UUID is different to the UUID in the database.  This will cause an issue when we import the
            # configurations, so we will change the db UUID to match the config files UUID and all should be good.
            ${drush_cmd} cset "system.site" yml_uuid -y
        fi
    fi

  elif [[ "${build_local_database_source}" == "sync" ]]; then
    # Grab a copy of the database from the desired(remote) acquia server.
    if [[ -z ${build_database_drush-alias} ]]; then build_database_drush-alias="@bostond8.test"; fi
    printout "INFO" "Copying database (and content) from ${build_database_drush-alias} into docker database container."

    # Drop the local DB, and then ...
    # ... download a backup from the remote server, and restore into the database container.
    ${drush_cmd} sql:drop --database=default -y > ${SETUP_LOGS}/drush_db-sync.log &&
        ${drush_cmd} sql:sync ${build_database_drush-alias} @self -y >> ${SETUP_LOGS}/drush_db-sync.log
    if [[ $? -eq 0 ]]; then
        printout "SUCCESS" "Site has database and content from remote environment."
    else
        printout "ERROR" "Fail - Database sync" "Check ${SETUP_LOGS}/drush_db-sync.log for issues."
        exit 0
    fi
  fi

  project_sync=${LANDO_MOUNT}/docroot/${build.local.config.sync}
  printout "INFO" "Import configuration from sync folder: '${project_sync}' into database"
  printout "INFO" "see ${SETUP_LOGS}/config-import.log for output." "(or https://${LANDO_APP_NAME}.${LANDO_DOMAIN}/sites/default/files/setup/config-import.log)"
  ${drush_cmd} cim sync -y > ${SETUP_LOGS}/config-import.log
  if [[ $? -eq 0 ]]; then
    printout "SUCCESS" "Config from the repo has been applied to the database."
  else
    # Sometimes there is an issue with configuration that cannot be applied to entities with content etc.
    # The work aound is to try a partial configuration import.
    echo "Retry partial cim." >> ${SETUP_LOGS}/config-import.log
    ${drush_cmd} cim sync --partial -y >> ${SETUP_LOGS}/config-import.log

    if [[ $? -eq 0 ]]; then
        printout "SUCCESS" "Config from the repo has been applied to the database."
    else
        echo "Retry partial cim (#2)." >> ${SETUP_LOGS}/config-import.log
        ${drush_cmd} cim sync --partial -y >> ${SETUP_LOGS}/config-import.log

        if [[ $? -eq 0 ]]; then
            printout "SUCCESS" "Config from the repo has been applied to the database."
        else
            # Uh oh!
            printout "ERROR" "Fail - Configuration import." "Check ${SETUP_LOGS}/config-import.log for issues."
            tail -250 ${SETUP_LOGS}/config-import.log
            exit 0
        fi
    fi
  fi

  printout "SUCCESS" "Drupal build finished"








  # Phing commands to complete the initial setup.  Output redirect so it can be printed at the end.
  # Capture the build info into a file to be printed at end of build process.
  cd ${LANDO_MOUNT} &&
    ./scripts/doit/branding.sh > ${LANDO_MOUNT}/setup/uli.log

  printf '\033[1;33mThe ${drupal.account.name} account password is reset to: ${drupal.account.password}.\033[0m' >> ${LANDO_MOUNT}/setup/uli.log
  cd ${LANDO_MOUNT}/docroot &&
    drush --root=/var/www/html/${site}${target_env}/docroot user:password admin "${drupal.account.password}"

  cd $LANDO_MOUNT &&
    phing -S -f ${LANDO_MOUNT}/build.xml update:user:loginadmin >> ${LANDO_MOUNT}/setup/uli.log
  cd $LANDO_MOUNT &&
    drush pmu acquia_connector &&
    drush pmu config_devel &&
    drush en config_devel >> ${LANDO_MOUNT}/setup/lando.log
  # Now setup the node container.
  printf "\033[1;32m[lando]\033[1;33m  Phing has finished building Drupal.\033[0m\n\n"
