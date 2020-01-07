#!/bin/bash

###############################################################
#  These commands need to be run as normal user from lando.yml.
#
#  NOTE: THIS SCRIPT SHOULD BE RUN INSIDE THE CONTAINER.
#
#  These commands install Drupal, sync down a database from Acquia
#  and update that Database with local & current repo settings.
#
#  PRE-REQUISITES:
#     - docker container for appserver is created and started, and
#     - main boston.gov repo is already cloned onto the host machine, and
#     - .lando.yml and .config.yml files are correctly configured.
#
#  Basic workflow:
#     1. Use composer to gather all Drupal core and contributed modules.
#     2. Clone the private repo and merge into the main repo.
#     3. Prepare/update settings.php and other settings files
#     4. Create the Drupal MySQL Database (initialize new or sync existing from remote)
#     5. Import configuration from main repo (already cloned locally)
#     6. Ensure the Drupal site is configured properly for develop activities
#     7. Run any finalization tasks to complete.
###############################################################

    # Include the utilities file/libraries.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    . "${LANDO_MOUNT}/hooks/common/cob_utilities.sh"

    printout "LANDO" "Project Event - post-start\n"
    printf "================================================================================\n"
    printout "SUCCESS" "Appserver and MySQL Docker containers are now started."
    printf "================================================================================\n\n"

    if [[ -e ${REPO_ROOT}/setup/uli.log ]]; then
        cat ${REPO_ROOT}/setup/uli.log && rm -f ${REPO_ROOT}/setup/uli.log;
    else
        . ${REPO_ROOT}/scripts/doit/branding.sh;
    fi

    # When the container is restarted, it seems the /etc/hosts file is rewritten and custom hosts mappings are lost.
    # Add in the correct entries.
    printf "\n%s  host.docker.internal\n" ${LANDO_HOST_IP} >> /etc/hosts
    printf "%s  docker.for.mac.localhost\n" ${LANDO_HOST_IP} >> /etc/hosts
