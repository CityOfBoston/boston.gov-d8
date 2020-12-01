#!/bin/bash

###############################################################
#  These commands need to be run as normal user from lando.yml.
#
#  NOTE: THIS SCRIPT SHOULD BE RUN INSIDE THE CONTAINER.
#
#  PRE-REQUISITES:
#     - docker container for node is created and started, and
#     - .lando.yml and .config.yml files are correctly configured.
#
###############################################################
    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    target_env="local"

    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
    printf "\n"
    printf "${Blue}       ================================================================================${NC}\n"
    printout "STEP" "Creating WebApp dev environment."
    printf "${Blue}       ================================================================================${NC}\n"
    printout "INFO" "Webapp source files are found in ${REPO_ROOT}/${webapps_local_local_dir}."
    printout "INFO" "Webapps are built and tested in the node container."
    printout "INFO" "Webapps are automatically synchronized into the Drupal appserver container."

    # Global Dependencies.
    # Install a standard common set of dependencies required for the local build.
    # (This is all dependencies from the package.json file in the scripts folder).
    printout "ACTION" "Installing WebApp standard packages and node dependencies."
    (cd ${REPO_ROOT}/${webapps_local_local_dir} &&
      npm install &> ${setup_logs}/webapp_build.log &&
      printout "SUCCESS" "WebApp dev environment created.\n") || printout "ERROR" "WebApp dev environment NOT created.\n"

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
    printf "\n"