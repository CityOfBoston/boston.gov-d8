#!/bin/bash
  ###############################################################
  #  These commands need to be run as root/admin user from lando.yml.
  #
  #  Essentially these commands are installing packages we require
  #  in the local docker node container.
  ###############################################################

  REPO_ROOT="${LANDO_MOUNT}"
  . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
  target_env="local"

  printf "\n"
  printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"

  # Include the utilities file/library.
  # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
  . "${LANDO_MOUNT}/scripts/local/lando-patterns-customize.sh"
  . "${LANDO_MOUNT}/scripts/local/lando-webapps-customize.sh"

  printout "SCRIPT" "ends <$(basename $BASH_SOURCE) >\n\n"
