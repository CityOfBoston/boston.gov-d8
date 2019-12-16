#!/bin/bash

    printout "INFO" "Will pull latest code from public repo and merge in settings from private repo."

    # Include the utilities file/library.
    . "/app/scripts/local/lando_utilities.sh"

    # Pull develop branch from git.
    cd /app/docroot &&
        git fetch --all &&
        git pull --all

    # Clone the private repo and merge with the main repo.
    clone_private_repo

    # Check for options/flags passed in.
    if [[ "${1}" != "--no-sync" ]]; then
        ${drush_cmd} cim -y
        ${drush_cmd} updb -y
    fi

    printout "SUCCESS" "Public and private repos updated.\n"
