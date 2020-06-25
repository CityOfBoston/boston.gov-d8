#!/bin/bash

    site=drupal
    source_branch="current branch"
    target_env=local

    if [[ -e "${drush_cmd}" ]]; then
        drush_cmd="/app/vendor/bin/drush  -r /app/docroot"
    fi

    . "/app/scripts/cob_build_utilities.sh"
    . "/app/scripts/deploy/cob_utilities.sh"

    printout "INFO" "Database will be copied from staging to local, setup for development and updated with configuration in latest branch."

    # Download remote DB
    ${drush_cmd} sql:sync @bostond8.prod @self -y

    # Update database with local settings
    sync_db "@self"

    # Enable/disable modules for local dev.
    devModules "@self"

    # Run Additional local processes
    ${drush_cmd} user:password admin admin

    printout "INFO" "Admin password reset to 'admin' locally."
    printout "SUCCESS" "Database from staging copied to local.\n"
