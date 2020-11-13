#!/bin/bash
  ###############################################################
  #  These commands need to be run as root/admin user from lando.yml.
  #
  #  Essentially these commands are installing packages we require
  #  in the local docker node container.
  ###############################################################

  # Include the utilities file/library.
  # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
  REPO_ROOT="${LANDO_MOUNT}"
  . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
  target_env="local"

  printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
  printf "\n"
  printf "${Blue}       ================================================================================${NC}\n"
  printout "STEP" "Prepare NodeJS env in node: Install patterns and webapps into the node container."
  printf "${Blue}       ================================================================================${NC}\n"

    # Copy the node.js executable file in the container to a location that can be seen on the host.
    # This way eslint can run from PHPStorm without needing to install node.js on the host.
    # In PHPStorm point the path for node in eslint settings dialog to /user/.node_js/node
    if [[ "${patterns_local_build}" != "true" ]] && [[ "${patterns_local_build}" != "True" ]] && [[ "${patterns_local_build}" != "TRUE" ]]; then
        printf "mkdir /user/.node_js\n"
        if [[ ! -d /user/.node_js ]]; then mkdir /user/.node_js; fi
        if [[ ! -e /user/.node_js/node ]]; then cp /usr/local/bin/node /user/.node_js/.; fi
        printout "INFO" "node.js executable is linked to /user/.node.js/ on the host PC (for IDE linting)"
    fi

  # Run the separate setup scripts for patterns and webapps.
  . "${LANDO_MOUNT}/scripts/local/lando-patterns-customize.sh"
  . "${LANDO_MOUNT}/scripts/local/lando-webapps-customize.sh"

  printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
  printf "\n"
