#!/bin/bash
    # NOTE: This script is intended to be used inside a Lando managed container.

    target_env="local"
    ALIAS="@self"
    source_env="dev"
    if [[ -n "${1}" ]]; then
        source_env="${1}"
    fi
    if [[ -e "${drush_cmd}" ]]; then
        drush_cmd="${LANDO_MOUNT}/vendor/bin/drush  -r ${LANDO_MOUNT}/docroot"
    fi
    if [[ -e "${drupal_cmd}" ]]; then
        drupal_cmd="${LANDO_MOUNT}/vendor/bin/drupal --root=${project_docroot}"
    fi

    . "/app/scripts/cob_build_utilities.sh"
    . "/app/scripts/deploy/cob_utilities.sh"

    printout "INFO" "Database will be copied from ${source_env} to local. It will be set up for development and updated with configuration from /app/config/default."

    # Download remote DB
    printf "       To speed up the import and reduce the local DB size, selected tables are truncated/omitted.\n"
    printf "       Refer to /app/drush/drush.yml for lists of tables managed by the drush sql:sync command.\n"
    SOURCE="@bostond8.${source_env}"
    ${drush_cmd} -y sql:drop --database=default &&
        ${drush_cmd} -y sql:sync --skip-tables-key=common --structure-tables-key=common ${SOURCE} @self

    # This should cause drupal to find new modules prior to trying to import their configs.
    ${drush_cmd} -y cache:rebuild
    printf " [action] Apply pending database updates etc.\n"
    ${drush_cmd} -y ${ALIAS} updatedb &> /dev/null &&
      printf " [success] Updates Completed.\n" || printf " [warning] Database updates from contributed modules were not applied.\n"

    # Update database with local configs and settings
    printf " [action] Update database (%s) on %s with configuration from updated code in %s.\n" "${site}" "${target_env}" "${source_branch}"
    importConfigs "${ALIAS}" &&
      printf " [success] Config Imported.\n" || printf "\n [warning] Problem with configuration sync.\n"

    # Set the website to use patterns library from appropriate location.
    printf " [action] Set ${target_env} site to use the correct patterns library.\n"
    setPatternsSource ${ALIAS}

    # For local instances, set the admin account (user=0) password to something simple to remember.
    setPassword "${ALIAS}" "admin"

    # Make sure this hasn't snuck in ...
    ${drush_cmd} ${ALIAS} state:set system.maintenance_mode 0

    printout "INFO" "Admin password reset to 'admin' locally."
    printout "SUCCESS" "Database from staging copied to local.\n"
