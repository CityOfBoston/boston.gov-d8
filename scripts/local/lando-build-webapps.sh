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

    printf "\n"
    printout "SCRIPT" "starts <$(basename $BASH_SOURCE) >\n"

    printf "\n${LightPurple}       ================================================================================${NC}\n"
    printout "STEP" "Creating WebApp dev environment."
    printf "${LightPurple}       ================================================================================${NC}\n"
    printf "      Webapp source files are found in ${REPO_ROOT}/${webapps_local_local_dir}."
    printf "      Webapps are built and tested in the node container."
    printf "      Webapps are automatically synchronized into the Drupal appserver container."

    # Global Dependencies.
    # Install a standard common set of dependencies required for the local build.
    # (This is all dependencies from the package.json file in the scripts folder).
    printout "INFO" "Installing WebApp standard packages and node dependencies."
    cd ${REPO_ROOT}/${webapps_local_local_dir} &&
      npm install &> ${setup_logs}/webapp_build.log &&
      printout "SUCCESS" "WebApp dev environment setup."

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE) >\n"
