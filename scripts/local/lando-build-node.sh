#!/bin/bash

###############################################################
#  These commands need to be run as normal user from lando.yml.
#
#  NOTE: THIS SCRIPT SHOULD BE RUN INSIDE THE CONTAINER.
#
#  These commands install the patterns repo, start the node service on port 80
#  and the start gulp to monitor for changes to files.
#
#  PRE-REQUISITES:
#     - docker container for node is created and started, and
#     - .lando.yml and .config.yml files are correctly configured.
#
#  Basic workflow:

#   1. Clone the patterns repo
#   2. Start npm
#   3. Run gulp
#   4. Run any finalization tasks to complete.
###############################################################

    # Include the utilities file/libraries.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    . "${LANDO_MOUNT}/hooks/common/cob_utilities.sh"

