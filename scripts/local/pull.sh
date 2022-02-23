#!/bin/bash

    # Include the utilities file/library.
    if [[ -e "${drush_cmd}" ]]; then
        drush_cmd="${LANDO_MOUNT}/vendor/bin/drush  -r ${LANDO_MOUNT}/docroot"
    fi
    if [[ -e "${drupal_cmd}" ]]; then
        drupal_cmd="${LANDO_MOUNT}/vendor/bin/drupal --root ${project_docroot}"
    fi

    . "/app/scripts/cob_build_utilities.sh"
    . "/app/scripts/deploy/cob_utilities.sh"

    target_env="local"

    printout "INFO" "Will pull latest code from public repo and merge in settings from private repo."

    # Pull current boston.gov branch from git.
    printout "ACTION" "Pulling current branch from Boston.gov-d8."
    cd /app/docroot &&
        git fetch --all >> /dev/null &&
        git pull --all >> /dev/null

    # Clone the private repo and merge with the main repo.
    clone_private_repo
    build_settings

    # Check if the patterns repo folder exists, if not, then have to clone repo.
    if [[ ! -d ${patterns_local_repo_local_dir} ]]; then
        # Clone the patterns repo and prepare build folders.
        clone_patterns_repo
    fi
    # Pull patterns current branch from git.
    printout "ACTION" "Pulling current branch from Patterns."
    cd /app/patterns &&
        git fetch --all >> /dev/null &&
        git pull --all >> /dev/null

    # Check for options/flags passed in.
    if [[ "${1}" != "--no-sync" ]]; then
      printout "ACTION" "Executing Config Import"
      importConfigs "@self" &&
        ${drush_cmd} updatedb -y &&
        printout "SUCCESS" "Boston.gov, Patterns and Private repos updated.\n" || printout "ERROR" "Problem importing configs or applying database updates.\n"
    else
      printout "SUCCESS" "Boston.gov, Patterns and Private repos updated (without config import).\n"
    fi


