#!/bin/bash

  ###############################################################
  #  These commands need to be run as normal user from lando.yml.
  #
  #  These commands install Drupal, sync down a database from Acquia
  #  and update that Database with local & current repo settings.
  ###############################################################

  printout "INFO" "Installing Drupal and dependencies in appserver & database containers."

  # Include the utilities file/library.
  . "${LANDO_MOUNT}/scripts/local/lando_utilities.sh"
  # Include the cob_utilities file contained in Acquia hooks.
  . "${LANDO_MOUNT}/hooks/common/cob_utilities.sh"

  # Read in config and variables.
  eval $(parse_yaml "${LANDO_MOUNT}/scripts/local/.config.yml" "")
  eval $(parse_yaml "${LANDO_MOUNT}/.lando.yml" "lando_")

  # Create script variables
  target_env="local"
  setup_logs="${LANDO_MOUNT}/setup/"
  project_sync=${LANDO_MOUNT}/docroot/${build_local_config_sync}
  LANDO_APP_URL="https://${LANDO_APP_NAME}.${LANDO_DOMAIN}"

  # Manage the setup logs folder, and create a link to the folder that can be accessed from a browser.
  # The folder has been created and permissions set in lando-container-customize.sh
  rm -f ${project_docroot}/sites/default/files/setup &&
    ln -s ${setup_logs} ${project_docroot}/sites/default/files/setup

  if [ -z ${project_docroot}/core/lib/Drupal.php ]; then
    printout "WARNING" "Drupal is already installed." "This will not be a 'clean' build."
    printout " " "" "Composer Install will still run and may update existing files."
  fi

  # Install composer - using lock file if present.
  printout "INFO" "see ${setup_logs}/composer.log for output." "(or ${LANDO_APP_URL}/sites/default/files/setup/composer.log)"
  printout "INFO" "Executes: > composer install --prefer-dist --no-suggest --no-interaction"
  echo "Executes: > composer install --prefer-dist --no-suggest --no-interaction" > ${setup_logs}/composer.log
  cd ${LANDO_MOUNT} &&
    composer install --no-suggest --prefer-dist --no-interaction >> ${setup_logs}/composer.log &&
    composer drupal:scaffold >> ${setup_logs}/composer.log &&
    echo "DONE." >> ${setup_logs}/composer.log &&
    printout "SUCCESS" "Composer has loaded Drupal core, contrib modules and third-party packages/libraries."

  # Clone the private repo and merge with the main repo.
  # This function is contained in lando_utilities.sh.
  clone_private_repo

  # Create/update settings, private settings and local settings files.
  build_settings

  # Install Drupal.
  echo "==== Installing Drupal ===========" > ${setup_logs}/drush_site_install.log
  if [[ "${build_local_database_source}" == "initialize" ]]; then

    printout "INFO" "Build is now using settings file ${local_settings_file}"
    printout "" "" "... with ${lando_services_database_type=mysql} database '${lando_services_database_creds_database}' on '${lando_services_database_host}:${lando_services_database_portforward}' in container '${LANDO_APP_PROJECT}_database_1'"
    printout "INFO" "see ${setup_logs}/drush_site_install.log for output." "(or ${LANDO_APP_URL}/sites/default/files/setup/drush_site_install.log)"

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
    echo "Executing: ${SITE_INSTALL}" >> ${setup_logs}/drush_site_install.log
    ${drush_cmd} ${SITE_INSTALL} >> ${setup_logs}/drush_site_install.log

    # If it failed then alert.
    if [[ $? -eq 0 ]]; then
        printout "SUCCESS" "Site is freshly installed with clean database."
    else
        printout "ERROR" "Fail - Site install failure" "Check ${setup_logs}/drush_site_install.log for issues."
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
    ${drush_cmd} sql:drop --database=default -y > ${setup_logs}/drush_db-sync.log &&
        ${drush_cmd} sql:sync ${build_database_drush-alias} @self -y >> ${setup_logs}/drush_db-sync.log
    if [[ $? -eq 0 ]]; then
        printout "SUCCESS" "Site has database and content from remote environment."
    else
        printout "ERROR" "Fail - Database sync" "Check ${setup_logs}/drush_db-sync.log for issues."
        exit 0
    fi
  fi

  # Import configurations.
  printout "INFO" "Import configuration from sync folder: '${project_sync}' into database"
  printout "INFO" "see ${setup_logs}/config-import.log for output." "(or ${LANDO_APP_URL}/sites/default/files/setup/config-import.log)"
  ${drush_cmd} config-import sync -y > ${setup_logs}/config-import.log
  if [[ $? -eq 0 ]]; then
    printout "SUCCESS" "Config from the repo has been applied to the database."
  else
    # Sometimes there is an issue with configuration that cannot be applied to entities with content etc.
    # The work aound is to try a partial configuration import.
    printout "WARNING" "==== Config Import Errors ========"
    tail -100 ${setup_logs}/config-import.log
    printout "       " "=================================="
    printout "INFO" "Retry config import."
    printout "       " "=================================="
    echo "Retry partial cim." >> ${setup_logs}/config-import.log
    ${drush_cmd} config-import sync --partial -y >> ${setup_logs}/config-import.log

    if [[ $? -eq 0 ]]; then
        printout "SUCCESS" "Config from the repo has been applied to the database."
    else
        echo "Retry partial cim (#2)." >> ${setup_logs}/config-import.log
        ${drush_cmd} config-import sync --partial -y >> ${setup_logs}/config-import.log

        if [[ $? -eq 0 ]]; then
            printout "SUCCESS" "Config from the repo has been applied to the database."
        else
            # Uh oh!
            printout "ERROR" "Fail - Configuration import." "Check ${setup_logs}/config-import.log for issues."
            tail -250 ${setup_logs}/config-import.log
            exit 0
        fi
    fi
  fi

  # Enable and disable modules specific to developers.
  # This unction is contained in cob_utilities.sh
  devModules "@self"

  # Apply database updates and rebuild user acess settings on nodes.
  printout "INFO" "Apply database updates."
  ${drush_cmd} updb -y >> ${setup_logs}/config-import.log   &&
    ${drush_cmd} eval "node_access_rebuild();" >> ${setup_logs}/config-import.log  &&
    printout "SUCCESS" "Import configuration from sync folder: '${project_sync}' into database"
  if [[ $? -eq 0 ]]; then
    printout "WARNING" "Post config import non-fatal issues."
  fi

  # Update the drush.yml file.
  printout "INFO" "Update the drush config."
  drush_file=${LANDO_MOUNT}/drush/drush.yml
  rm -rf ${drush_file}
  printf "# Docs at https://github.com/drush-ops/drush/blob/master/examples/example.drush.yml\n\n" > ${drush_file}
  printf "options:\n  uri: '${lando.url.url}'\n  root: '${lando.url.localpath}'" >> ${drush_file}
  printout "SUCCESS" "File updated"

  # Phing commands to complete the initial setup.  Output redirect so it can be printed at the end.
  # Capture the build info into a file to be printed at end of build process.
  cd ${LANDO_MOUNT}/scripts/doit/branding.sh > ${setup_logs}/uli.log
  printf '\033[1;33mThe ${drupal_account_name} account password is reset to: ${drupal_account_password}.\033[0m' >> ${setup_logs}/uli.log
  ${drush_cmd} user:password ${drupal_account_name} "${drupal_account_password}" &>/dev/nul
  ${drush_cmd} user-login --name=${drupal_account_name} >> ${setup_logs}/uli.log

  printout "SUCCESS" "Drupal build finished"