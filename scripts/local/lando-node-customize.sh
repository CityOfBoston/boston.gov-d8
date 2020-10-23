#!/bin/bash
  ###############################################################
  #  These commands need to be run as root/admin user from lando.yml.
  #
  #  Essentially these commands are installing packages we require
  #  in the local docker node container.
  ###############################################################

  printf "\n"
  printout "SCRIPT" "starts <$(basename $BASH_SOURCE) >\n"

  # Include the utilities file/library.
  # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
  . "${LANDO_MOUNT}/scripts/local/lando-patterns-customize.sh"
  . "${LANDO_MOUNT}/scripts/local/lando-webapps-customize.sh"

  printout "SCRIPT" "ends <$(basename $BASH_SOURCE) >\n\n"
