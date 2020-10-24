#!/bin/bash

    ###############################################################
    #  These commands need to be run as normal user from lando.yml.
    #
    #  NOTE: THIS SCRIPT SHOULD BE RUN INSIDE THE CONTAINER.
    #
    #  These commands start the webapp processes
    #
    ###############################################################

    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    target_env="local"

    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
    printf "\n"

    # Here we need to search for webapps, detect and start their key watch processes

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
  printf "\n"
