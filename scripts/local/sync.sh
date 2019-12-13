#!/bin/bash

    site=drupal
    source_branch="current branch"
    target_env=local

    . "/app/hooks/common/cob_utilities.sh"

    printf "[info] Database will be copied from staging to local, setup for development and updated with configuration in latest branch.\n"

    # Download remote DB
    drush sql:sync @bostond8.test @self -y

    # Update database with local settings
    sync_db "@self"

    # Enable/disable modules for local dev.
    devModules "@self"

    # Run Additional local processes
    drush user:password admin admin

    printf "[info] Admin password reset to 'admin' locally.\n"
    printf "[success] Database from staging copied to local.\n"
