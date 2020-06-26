#!/bin/bash

###############################################################
#  These commands need to be run as normal user from lando.yml.
#
#  NOTE: THIS SCRIPT SHOULD BE RUN INSIDE THE CONTAINER.
#
#  These commands install the patterns repo, start the node service
#  and start gulp to monitor for changes to files.
#
#  PRE-REQUISITES:
#     - docker container for node is created and started, and
#     - .lando.yml and .config.yml files are correctly configured.
#
#  Basic workflow:

#   1. Clone the patterns repo
#   2. Install npm dependencies in package.json
###############################################################

    # Include the utilities file/libraries.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    . "${LANDO_MOUNT}/scripts/deploy/cob_utilities.sh"
    target_env="local"

    printf "\n"
    printf "[LANDO] starts <$(basename "$0")>\n"
    if [[ "${patterns_local_build}" != "true" ]] && [[ "${patterns_local_build}" != "True" ]] && [[ "${patterns_local_build}" != "TRUE" ]]; then
        printout "INFO" "Patterns library will not be deployed.."
        exit 0
    fi
    printf "\n${LightPurple}       ================================================================================${NC}\n"
    printout "STEP" "Building Patterns."
    printf "${LightPurple}       ================================================================================${NC}\n"

    printout "INFO" "Installing node dependencies for patterns app."
    cd ${patterns_local_repo_local_dir} && npm run preinstall && npm install && npm install -g gulp-cli@latest
    if [[ $? != 0 ]]; then
        printout "ERROR" "Patterns library NOT built or installed."
        exit 1
    fi

    # Install the patterns app.
    printout "INFO" "Building Patterns library."
    # Run an initial build to be sure everything is there.
    cd ${patterns_local_repo_local_dir} && npm run build

    printout "SUCCESS" "Patterns library built."
    printf "[LANDO] ends <$(basename "$0")>\n"
