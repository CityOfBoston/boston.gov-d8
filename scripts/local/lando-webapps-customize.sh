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
    printf "[LANDO] starts <$(basename $BASH_SOURCE) >\n"

    printout "INFO" "Check and create development directory links for WebApps."

    # Link the webapps folder from inside drupal into the root, so that
    # developers can find the modules more easily.
    if [[ ! -h ${webapps_local_local_dir} ]]; then
      ln -s ${webapps_local_source} ${webapps_local_local_dir}
    printf "      Webapps directory is mapped to ${webapps_local_local_dir}.\n"

    # Create a link for global.package file so it can be used by npm to set up the container
    # with a default set of node tools.
    if [[ ! -h ${webapps_local_local_dir}/package.json ]]; then
      ln -s ${REPO_ROOT}/scripts/global.package ${webapps_local_local_dir}/package.json
    printf "      Global node package file mapped.\n"

    printout "SUCCESS" "Directory links created and/or verified.\n"

    printf "[LANDO] ends <$(basename $BASH_SOURCE) >\n\n"
