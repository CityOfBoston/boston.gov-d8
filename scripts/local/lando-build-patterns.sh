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
    . "${LANDO_MOUNT}/hooks/common/cob_utilities.sh"

    printf "\n"
    printf "ref: $(basename "$0")\n"
    if [[ "${patterns_local_build}" != "true" ]] && [[ "${patterns_local_build}" != "True" ]] && [[ "${patterns_local_build}" != "TRUE" ]]; then
        printout "INFO" "Patterns library will not be deployed.."
        exit 0
    fi
    printf "\n${LightPurple}       ================================================================================${NC}\n"
    printout "STEP" "Building Patterns."
    printf "${LightPurple}       ================================================================================${NC}\n"

    # Clone the patterns repo into a folder within the Main boston.gov d8 repo.
    clone_patterns_repo

    # Install the patterns app.
    printout "INFO" "Building Patterns library."
    cd ${patterns_local_repo_local_dir} && npm install
    if [[ $? != 0 ]]; then
        printout "ERROR" "Patterns library NOT built or installed."
        exit 1
    fi

    # Run an initial build to be sure everything is there.
    printout "INFO" "Build Stuff."
    cd ${patterns_local_repo_local_dir} && npm run preinstall && npm run fractal-build && npm run gulp-build

    printout "SUCCESS" "Patterns library built."
