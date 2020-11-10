#!/bin/bash

###############################################################
#  These commands need to be run as normal user from lando.yml.
#
#  NOTE: THIS SCRIPT SHOULD BE RUN INSIDE THE CONTAINER.
#
#  These commands install Start the npm processes: gulp and stylus.
#
#  PRE-REQUISITES:
#     - node:10 docker container for patterns is created and started, and
#     - main boston.gov repo is already cloned onto the host machine, and
#     - .lando.yml and .config.yml files are correctly configured.
#
#  Basic workflow:
#     1. Start the fractal service.
###############################################################

  printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
  printf "\n"

  . "${LANDO_MOUNT}/scripts/local/lando-patterns-post-start.sh"
  . "${LANDO_MOUNT}/scripts/local/lando-webapps-post-start.sh"

  printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
  printf "\n"
