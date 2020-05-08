#!/bin/bash


    # Include the utilities file/library.
    if [[ -e "${drush_cmd}" ]]; then
        drush_cmd="/app/vendor/bin/drush  -r /app/docroot"
    fi
    . "/app/scripts/cob_build_utilities.sh"
    . "/app/hooks/common/cob_utilities.sh"

    printout "INFO" "Will pull latest code from public repo and merge in settings from private repo."

    # Pull current boston.gov branch from git.
    cd /app/docroot &&
        git fetch --all >> /dev/null &&
        git pull --all >> /dev/null

    # Clone the private repo and merge with the main repo.
    clone_private_repo

    # Check if the patterns repo folder exists, if not, then have to clone repo.
    if [[ ! -d ${patterns_local_repo_local_dir} ]]; then
        # Clone the patterns repo and prepare build folders.
        clone_patterns_repo
    fi
    # Pull patterns current branch from git.
    cd /app/patterns &&
        git fetch --all >> /dev/null &&
        git pull --all >> /dev/null

    # Check for options/flags passed in.
    if [[ "${1}" != "--no-sync" ]]; then
        ${drush_cmd} cim -y
        ${drush_cmd} updb -y
    fi

    printout "SUCCESS" "Boston.gov, Patterns and Private repos updated.\n"
