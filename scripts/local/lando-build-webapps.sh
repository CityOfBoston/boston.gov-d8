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
    printf "[LANDO] starts <$(basename $BASH_SOURCE) >\n"

    # Global Dependencies.
    # Install a standard common set of dependencies required for the local build.
    # (This is all dependencies from the package.json file in the scripts folder).
    cd ${REPO_ROOT}/scripts && npm install

    printf "[LANDO] ends <$(basename $BASH_SOURCE) >\n"
