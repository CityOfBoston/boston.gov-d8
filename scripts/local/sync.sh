#!/bin/bash

    site=drupal
    source_branch="current branch"
    target_env=local

    . "/app/hooks/common/cob_utilities.sh"

    printout "INFO" "Database will be copied from staging to local, setup for development and updated with configuration in latest branch."

    # Download remote DB
    drush sql:sync @bostond8.dev @self -y

    # Update database with local settings
    sync_db "@self"

    # Enable/disable modules for local dev.
    devModules "@self"

    # Run Additional local processes
    drush user:password admin admin

    printf "INFO" "Admin password reset to 'admin' locally."
    printf "SUCCESS" "Database from staging copied to local.\n"
