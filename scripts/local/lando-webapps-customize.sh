#!/bin/bash

###############################################################
#  These commands need to be run as root/admin user from lando.yml.
#
#  Essentially these commands are installing packages we require
#  in the local docker node container for webapps.
###############################################################

    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    target_env="local"

    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
    printf "\n"
    printout "ACTION" "Checking and creating development directory links for WebApps."

    # Link the webapps folder from inside drupal into the root, so that
    # developers can find the modules more easily.
    if [[ ! -h ${REPO_ROOT}/${webapps_local_local_dir} ]]; then
      cd ${REPO_ROOT} &&
        ln -s ${webapps_local_source} ${webapps_local_local_dir}
    fi
    printout "INFO" "Webapps directory is mapped to ${webapps_local_local_dir}.\n"

    # Create a link for global.package file so it can be used by npm to set up the container
    # with a default set of node tools.
    # The package.json will only be visible inside containers, from the host the link will not resolve.
    # This is intentional, developers should not need to change this file.
    if [[ ! -h ${REPO_ROOT}/${webapps_local_local_dir}/package.json ]]; then
      ln -s "${REPO_ROOT}/scripts/webapp.default.package" "${REPO_ROOT}/${webapps_local_local_dir}/package.json"
    fi
    printout "INFO" "Global node package file mapped.\n"

    printout "SUCCESS" "Directory links created and/or verified.\n"

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
  printf "\n"
