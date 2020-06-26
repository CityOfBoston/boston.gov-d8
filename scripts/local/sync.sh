#!/bin/bash
    # NOTE: This script is intended to be used inside a Lando managed container.

    target_env="local"
    ALIAS="@self"
    source_env="dev"
    if [[ -n "${1}" ]]; then
        source_env="${1}"
    fi
    if [[ -e "${drush_cmd}" ]]; then
        drush_cmd="/app/vendor/bin/drush  -r /app/docroot"
    fi

    . "/app/scripts/cob_build_utilities.sh"
    . "/app/scripts/deploy/cob_utilities.sh"

    printout "INFO" "Database will be copied from ${source_env} to local. It will be set up for development and updated with configuration from /app/config/default."

    # Download remote DB
    printf "       To speed up the import and reduce the local DB size, selected tables are truncated/omitted.\n"
    printf "       Refer to /app/drush/drush.yml for lists of tables managed by the drush sql:sync command.\n"
    $SOURCE="@bostond8.${source_env}"
    ${drush_cmd} -y sql:drop --database=default &&
        ${drush_cmd} -y sql:sync --skip-tables-key=common --structure-tables-key=common ${SOURCE} @self

    # Update database with local settings
    sync_db "${ALIAS}"

    # Enable/disable modules for local dev.
    devModules "${ALIAS}"

    # Run Additional local processes
    ${drush_cmd} user:password admin admin

    printout "INFO" "Admin password reset to 'admin' locally."
    printout "SUCCESS" "Database from staging copied to local.\n"
