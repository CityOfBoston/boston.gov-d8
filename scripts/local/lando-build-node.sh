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

  printf "\n"
  printf "[LANDO] starts <$(basename $BASH_SOURCE) >\n"

  . "${LANDO_MOUNT}/scripts/local/lando-build-patterns.sh"
  . "${LANDO_MOUNT}/scripts/local/lando-build-webapps.sh"

  printf "[LANDO] ends <$(basename $BASH_SOURCE) >\n"
