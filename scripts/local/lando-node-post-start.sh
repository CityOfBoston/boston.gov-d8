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

  printf "\n"
  printf "[LANDO] starts <$(basename "$0")>\n"

  . "${LANDO_MOUNT}/scripts/lando/lando-patterns-post-start.sh"
  . "${LANDO_MOUNT}/scripts/lando/lando-webapps-post-start.sh"

  printf "[LANDO] ends <$(basename "$0")>\n"
