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

    printf "\n"
    printf "[LANDO] starts <$(basename "$0")>\n"

    # Link the webapps folder from inside drupal into the root, so that
    # developers can find the modules more easily.
    ln -s ${webapps_local_source} ${webapps_local_local_dir}

    # Create a link for global.package file so it can be used by npm to set up the container
    # with a default set of node tools.
    ln -s ${REPO_ROOT}/scripts/global.package ${webapps_local_local_dir}/package.json

    printout "INFO" "WebApp source files can be editted at ${webapps_local_local_dir}"

    printf "[LANDO] ends <$(basename "$0")>\n"
